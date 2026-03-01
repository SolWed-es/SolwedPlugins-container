<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;

use FacturaScripts\Plugins\CRM\Model\CrmOportunidad;

/**
 * Description of EditCrmNota
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCrmNota extends EditController
{
    use DocFilesTrait;

    public function getModelClassName(): string
    {
        return 'CrmNota';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'note';
        $data['icon'] = 'fa-regular fa-sticky-note';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();

        $this->setTabsPosition('bottom');

        $this->createViewEstimations();
        $this->createViewDocFiles();
    }

    protected function createViewEstimations(string $viewName = 'ListPresupuestoCliente'): void
    {
        $this->addListView($viewName, 'PresupuestoCliente', 'estimations', 'fa-solid fa-copy')
            ->setSettings('checkBoxes', false)
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false);
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
                return $this->addFileAction();

            case 'delete-file':
                return $this->deleteFileAction();

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
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $code);
                break;

            case 'ListPresupuestoCliente':
                $opportunity = new CrmOportunidad();
                $id = $this->views[$mvn]->model->idoportunidad;
                if ($this->views[$mvn]->model->tipodocumento === 'presupuesto de cliente') {
                    $where = [new DataBaseWhere('codigo', $this->views[$mvn]->model->documento)];
                    $view->loadData('', $where);
                } elseif (empty($id) || false === $opportunity->loadFromCode($id)) {
                    break;
                } elseif ($opportunity->idpresupuesto) {
                    $where = [new DataBaseWhere('idpresupuesto', $opportunity->idpresupuesto)];
                    $view->loadData('', $where);
                }
                break;

            case $mvn:
                parent::loadData($viewName, $view);
                if (false === $view->model->exists()) {
                    $view->model->nick = $this->user->nick;
                }
                if (empty($view->model->idoportunidad)) {
                    $this->views[$mvn]->disableColumn('opportunity', true);
                }
                break;
        }
    }
}
