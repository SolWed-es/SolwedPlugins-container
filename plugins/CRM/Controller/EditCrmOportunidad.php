<?php
/**
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;

/**
 * Description of EditCrmOportunidad
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCrmOportunidad extends EditController
{
    use DocFilesTrait;

    public function getModelClassName(): string
    {
        return 'CrmOportunidad';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'opportunity';
        $data['icon'] = 'fa-solid fa-trophy';
        return $data;
    }

    protected function createEstimationAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->error('permission-denied');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $mainView = $this->getMainViewName();
        $contact = $this->views[$mainView]->model->getContacto();
        if (false === $contact->exists()) {
            Tools::log()->error('contact-not-found');
            return;
        }

        $customer = $contact->getCustomer();
        if (false === $customer->exists()) {
            Tools::log()->error('customer-not-found');
            return;
        }

        $presupuesto = new PresupuestoCliente();
        $presupuesto->setSubject($customer);
        $presupuesto->codagente = $this->views[$mainView]->model->codagente;
        if (false === $presupuesto->save()) {
            Tools::log()->error('record-save-error');
            return;
        }

        $this->views[$mainView]->model->coddivisa = $presupuesto->coddivisa;
        $this->views[$mainView]->model->idpresupuesto = $presupuesto->primaryColumnValue();
        $this->views[$mainView]->model->neto = $presupuesto->neto;
        $this->views[$mainView]->model->netoeuros = empty($presupuesto->tasaconv) ? 0 : round($presupuesto->neto / $presupuesto->tasaconv, 5);
        $this->views[$mainView]->model->tasaconv = $presupuesto->tasaconv;
        $this->views[$mainView]->model->save();

        $this->redirect($presupuesto->url());
    }

    protected function createViews()
    {
        parent::createViews();

        $this->setTabsPosition('bottom');

        $this->createViewEstimations();
        $this->createViewNotes();
        $this->createViewDocFiles();
    }

    protected function createViewEstimations(string $viewName = 'ListPresupuestoCliente'): void
    {
        $this->addListView($viewName, 'PresupuestoCliente', 'estimations', 'fa-solid fa-copy')
            ->setSettings('checkBoxes', false)
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false);
    }

    protected function createViewNotes(string $viewName = 'EditCrmNota'): void
    {
        $this->addEditListView($viewName, 'CrmNota', 'notes', 'fa-solid fa-sticky-note')
            ->disableColumn('contact')
            ->disableColumn('opportunity');
    }

    /**
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        if ($action == 'create-estimation') {
            $this->createEstimationAction();
            return;
        }

        parent::execAfterAction($action);
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'add-file':
                if (false === $this->addFileAction()) {
                    return false;
                }
                $mainView = $this->getMainViewName();
                $this->views[$mainView]->model->notifyNewFile($this->user->nick);
                return true;

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'unlink-file':
                return $this->unlinkFileAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $code = $this->request->get('code');

        switch ($viewName) {
            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $code);
                break;

            case $this->getMainViewName():
                parent::loadData($viewName, $view);
                // set user nick
                if (false === $view->model->exists()) {
                    $view->model->nick = $this->user->nick;
                }

                // disable columns if not editable
                if (false === $view->model->editable) {
                    $view->disableColumn('agent', false, 'true');
                    $view->disableColumn('approximate-price', false, 'true');
                    $view->disableColumn('assigned', false, 'true');
                    $view->disableColumn('contact', false, 'true');
                    $view->disableColumn('description', false, 'true');
                    $view->disableColumn('interest', false, 'true');
                    $view->disableColumn('observations', false, 'true');
                }
                if (false === empty($view->model->idpresupuesto)) {
                    $view->disableColumn('approximate-price', false, 'true');
                }
                break;

            case 'EditCrmNota':
                $id = $this->getViewModelValue($this->getMainViewName(), 'id');
                $where = [new DataBaseWhere('idoportunidad', $id)];
                $view->loadData('', $where, ['fecha' => 'DESC', 'id' => 'DESC']);
                // set user nick
                if (false === $view->model->exists()) {
                    $view->model->idcontacto = $this->getViewModelValue($this->getMainViewName(), 'idcontacto');
                    $view->model->idinteres = $this->getViewModelValue($this->getMainViewName(), 'idinteres');
                    $view->model->nick = $this->user->nick;
                }
                break;

            case 'ListPresupuestoCliente':
                $id = $this->getViewModelValue($this->getMainViewName(), 'idpresupuesto');
                if (empty($id)) {
                    $this->addButton($viewName, [
                        'action' => 'create-estimation',
                        'color' => 'success',
                        'icon' => 'fa-solid fa-plus',
                        'label' => 'create-estimation'
                    ]);
                    break;
                }

                $where = [new DataBaseWhere('idpresupuesto', $id)];
                $view->loadData('', $where);
                break;
        }
    }
}
