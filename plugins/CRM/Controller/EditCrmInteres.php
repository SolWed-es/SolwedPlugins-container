<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\CRM\Model\CrmInteresContacto;

/**
 * Description of EditCrmInteres
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCrmInteres extends EditController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'interest';
        $data['icon'] = 'fa-solid fa-heart';
        return $data;
    }

    public function getModelClassName(): string
    {
        return 'CrmInteres';
    }

    protected function addContactAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->error('permission-denied');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $codes = $this->request->request->getArray('codes');
        if (is_array($codes)) {
            $num = 0;
            foreach ($codes as $code) {
                $relation = new CrmInteresContacto();
                $relation->idcontacto = $code;
                $relation->idinteres = $this->request->query('code');
                if ($relation->save()) {
                    $num++;
                }
            }

            Tools::log()->notice('items-added-correctly', ['%num%' => $num]);
        }
    }

    protected function createViews()
    {
        parent::createViews();

        $this->setTabsPosition('bottom');

        $this->createViewContacts();
        $this->createViewNewContacts();
        $this->createViewNotes();
        $this->createViewOpportunities();

        // needed dependency
        new CrmInteresContacto();
    }

    protected function createViewCommon(string $viewName): void
    {
        $this->listView($viewName)
            ->addSearchFields(['nombre', 'apellidos', 'email', 'empresa', 'observaciones', 'telefono1', 'telefono2'])
            ->setSettings($viewName, 'btnNew', false)
            ->setSettings($viewName, 'btnDelete', false);

        // filters
        $i18n = Tools::lang();
        $values = [
            ['label' => $i18n->trans('all'), 'where' => []],
            ['label' => $i18n->trans('customers'), 'where' => [new DataBaseWhere('codcliente', null, 'IS NOT')]],
            ['label' => $i18n->trans('not-customers'), 'where' => [new DataBaseWhere('codcliente', null, 'IS')]],
        ];
        $agentes = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $fuentes = $this->codeModel->all('crm_fuentes2', 'id', 'nombre');
        $countries = $this->codeModel->all('paises', 'codpais', 'nombre');
        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        $cargoValues = $this->codeModel->all('contactos', 'cargo', 'cargo');

        $this->listView($viewName)
            ->addFilterSelectWhere('status', $values)
            ->addFilterSelect('codagente', 'agent', 'codagente', $agentes)
            ->addFilterSelect('idfuente', 'source', 'idfuente', $fuentes)
            ->addFilterSelect('codpais', 'country', 'codpais', $countries)
            ->addFilterSelect('provincia', 'province', 'provincia', $provinces)
            ->addFilterSelect('ciudad', 'city', 'ciudad', $cities)
            ->addFilterSelect('cargo', 'position', 'cargo', $cargoValues)
            ->addFilterCheckbox('verificado', 'verified', 'verificado')
            ->addFilterCheckbox('admitemarketing', 'allow-marketing', 'admitemarketing');
    }

    protected function createViewContacts(string $viewName = 'ListCrmContacto'): void
    {
        $this->addListView($viewName, 'Contacto', 'contacts', 'fa-solid fa-users')
            ->addOrderBy(['email'], 'email')
            ->addOrderBy(['empresa'], 'company')
            ->addOrderBy(['fechaalta'], 'creation-date')
            ->addOrderBy(['nombre'], 'name', 1)
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);

        $this->createViewCommon($viewName);

        // add action button
        $this->addButton($viewName, [
            'action' => 'remove-contact',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fa-solid fa-user-minus',
            'label' => 'remove-from-list',
        ]);
    }

    protected function createViewNewContacts(string $viewName = 'ListCrmContacto-new'): void
    {
        $this->addListView($viewName, 'Contacto', 'add', 'fa-solid fa-user-plus')
            ->addOrderBy(['email'], 'email')
            ->addOrderBy(['empresa'], 'company')
            ->addOrderBy(['fechaalta'], 'creation-date')
            ->addOrderBy(['nombre'], 'name', 1)
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);

        $this->createViewCommon($viewName);

        // add action button
        $this->addButton($viewName, [
            'action' => 'add-contact',
            'color' => 'success',
            'icon' => 'fa-solid fa-user-plus',
            'label' => 'add',
        ]);
    }

    protected function createViewNotes(string $viewName = 'ListCrmNota'): void
    {
        $this->addListView($viewName, 'CrmNota', 'notes', 'fa-regular fa-sticky-note')
            ->addOrderBy(['fecha'], 'date', 2)
            ->addOrderBy(['fechaaviso'], 'notice-date')
            ->addSearchFields(['observaciones'])
            ->disableColumn('interest');
    }

    protected function createViewOpportunities(string $viewName = 'ListCrmOportunidad'): void
    {
        $this->addListView($viewName, 'CrmOportunidad', 'oportunities', 'fa-solid fa-trophy')
            ->addOrderBy(['fecha'], 'date', 2)
            ->addOrderBy(['neto'], 'net')
            ->addSearchFields(['descripcion', 'observaciones'])
            ->disableColumn('interest');
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'add-contact':
                $this->addContactAction();
                return true;

            case 'remove-contact':
                $this->removeContactAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $id = $this->getViewModelValue($mainViewName, 'id');
        $sqlIn = 'select idcontacto from crm_intereses_contactos where idinteres = ' . $this->dataBase->var2str($id);

        switch ($viewName) {
            case 'ListCrmContacto':
                $where = [new DataBaseWhere('idcontacto', $sqlIn, 'IN')];
                $view->loadData('', $where);
                break;

            case 'ListCrmContacto-new':
                $where = [new DataBaseWhere('idcontacto', $sqlIn, 'NOT IN')];
                $view->loadData('', $where);
                break;

            case 'ListCrmNota':
            case 'ListCrmOportunidad':
                $where = [new DataBaseWhere('idinteres', $id)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function removeContactAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->error('permission-denied');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $codes = $this->request->request->getArray('codes');
        if (is_array($codes)) {
            $num = 0;
            foreach ($codes as $code) {
                $relation = new CrmInteresContacto();
                $where = [
                    new DataBaseWhere('idinteres', $this->request->query('code')),
                    new DataBaseWhere('idcontacto', $code)
                ];
                if ($relation->loadWhere($where) && $relation->delete()) {
                    $num++;
                }
            }

            Tools::log()->notice('items-removed-correctly', ['%num%' => $num]);
        }
    }
}
