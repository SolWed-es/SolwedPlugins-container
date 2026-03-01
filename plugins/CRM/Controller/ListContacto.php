<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Description of ListContacto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListContacto extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'contacts';
        $data['icon'] = 'fa-solid fa-users';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewContacts();
        $this->createViewLists();
        $this->createViewsCampaign();
    }

    protected function createViewsCampaign(string $viewName = 'ListCrmCampaign'): void
    {
        $this->addView($viewName, 'CrmCampaign', 'campaign', 'fa-solid fa-envelope-open-text')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['num_emails'], 'emails')
            ->addOrderBy(['num_sent'], 'sent')
            ->addOrderBy(['creation_date'], 'creation-date')
            ->addOrderBy(['last_update'], 'last-update', 2)
            ->addSearchFields(['name', 'subject']);
    }

    protected function createViewContacts(string $viewName = 'ListCrmContacto'): void
    {
        $this->addView($viewName, 'Contacto', 'contacts', 'fa-solid fa-users')
            ->addOrderBy(['email'], 'email')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['empresa'], 'company')
            ->addOrderBy(['fechaalta'], 'creation-date', 2)
            ->addSearchFields(['nombre', 'apellidos', 'email', 'empresa', 'observaciones', 'telefono1', 'telefono2']);

        // filters
        $this->createViewContactsFilters($viewName);
    }

    protected function createViewContactsFilters(string $viewName): void
    {
        $i18n = Tools::lang();
        $agentes = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $fuentes = $this->codeModel->all('crm_fuentes2', 'id', 'nombre');
        $countries = $this->codeModel->all('paises', 'codpais', 'nombre');
        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        $cargoValues = $this->codeModel->all('contactos', 'cargo', 'cargo');

        $this->listView($viewName)
            ->addFilterSelectWhere('status', [
                ['label' => $i18n->trans('all'), 'where' => []],
                ['label' => '------', 'where' => []],
                ['label' => $i18n->trans('customers'), 'where' => [new DataBaseWhere('codcliente', null, 'IS NOT')]],
                ['label' => $i18n->trans('not-customers'), 'where' => [new DataBaseWhere('codcliente', null, 'IS')]],
                ['label' => $i18n->trans('suppliers'), 'where' => [new DataBaseWhere('codproveedor', null, 'IS NOT')]],
                ['label' => $i18n->trans('not-suppliers'), 'where' => [new DataBaseWhere('codproveedor', null, 'IS')]],
                [
                    'label' => $i18n->trans('neither-customer-neither-supplier'),
                    'where' => [
                        new DataBaseWhere('codproveedor', null, 'IS'),
                        new DataBaseWhere('codcliente', null, 'IS'),
                    ]
                ],
            ])
            ->addFilterPeriod('fechaalta', 'creation-date', 'fechaalta')
            ->addFilterSelect('codagente', 'agent', 'codagente', $agentes)
            ->addFilterSelect('idfuente', 'source', 'idfuente', $fuentes)
            ->addFilterSelect('codpais', 'country', 'codpais', $countries);

        if (count($provinces) >= CodeModel::ALL_LIMIT) {
            $this->addFilterAutocomplete($viewName, 'provincia', 'province', 'provincia', 'contactos', 'provincia');
        } else {
            $this->addFilterSelect($viewName, 'provincia', 'province', 'provincia', $provinces);
        }

        if (count($cities) >= CodeModel::ALL_LIMIT) {
            $this->addFilterAutocomplete($viewName, 'ciudad', 'city', 'ciudad', 'contactos', 'ciudad');
        } else {
            $this->addFilterSelect($viewName, 'ciudad', 'city', 'ciudad', $cities);
        }

        $this->listView($viewName)
            ->addFilterSelect('cargo', 'position', 'cargo', $cargoValues)
            ->addFilterSelectWhere('verified', [
                ['label' => $i18n->trans('all'), 'where' => []],
                ['label' => '------', 'where' => []],
                ['label' => $i18n->trans('verified'), 'where' => [new DataBaseWhere('verificado', true)]],
                ['label' => $i18n->trans('not-verified'), 'where' => [new DataBaseWhere('verificado', false)]]
            ])
            ->addFilterSelectWhere('allow-marketing', [
                ['label' => $i18n->trans('all'), 'where' => []],
                ['label' => '------', 'where' => []],
                ['label' => $i18n->trans('allow-marketing'), 'where' => [new DataBaseWhere('admitemarketing', true)]],
                ['label' => $i18n->trans('not-allow-marketing'), 'where' => [new DataBaseWhere('admitemarketing', false)]]
            ])
            ->addFilterCheckbox('has-observations', 'has-observations', 'observaciones', '!=', '');
    }

    protected function createViewLists(string $viewName = 'ListCrmLista'): void
    {
        $this->addView($viewName, 'CrmLista', 'lists', ' fa-solid fa-users-rectangle')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['numcontactos'], 'contacts')
            ->addOrderBy(['fecha'], 'date')
            ->addSearchFields(['nombre']);
    }
}
