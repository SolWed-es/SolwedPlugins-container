<?php
/**
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Controller\EditContacto as ParentController;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Tools;

use FacturaScripts\Dinamic\Model\CrmInteres;
use FacturaScripts\Dinamic\Model\CrmLista;
use FacturaScripts\Dinamic\Model\CrmOportunidadEstado;

/**
 * Description of EditContacto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditContacto extends ParentController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();

        $this->createViewCrmNotes();

        // show interest tab only if there are interests
        $interest = new CrmInteres();
        if ($interest->count() > 0) {
            $this->createViewCrmInterests();
        }

        // show lists tab only if there are lists
        $list = new CrmLista();
        if ($list->count() > 0) {
            $this->createViewCrmLists();
        }

        $this->createViewCrmOpportunities();
    }

    protected function createViewCrmInterests(string $viewName = 'EditCrmInteresContacto'): void
    {
        $this->addEditListView($viewName, 'CrmInteresContacto', 'interests', 'fa-solid fa-heart')
            ->setInline(true)
            ->disableColumn('contact');
    }

    protected function createViewCrmLists(string $viewName = 'EditCrmListaContacto'): void
    {
        $this->addEditListView($viewName, 'CrmListaContacto', 'lists', 'fa-solid fa-notes-medical')
            ->setInline(true)
            ->disableColumn('contact');
    }

    protected function createViewCrmNotes(string $viewName = 'EditCrmNota'): void
    {
        $this->addEditListView($viewName, 'CrmNota', 'notes', 'fa-regular fa-sticky-note')
            ->disableColumn('contact');
    }

    protected function createViewCrmOpportunities(string $viewName = 'ListCrmOportunidad'): void
    {
        $this->addListView($viewName, 'CrmOportunidad', 'opportunities', 'fa-solid fa-trophy')
            ->addOrderBy(['fecha'], 'date', 2)
            ->addOrderBy(['neto'], 'net')
            ->addSearchFields(['descripcion', 'observaciones'])
            ->disableColumn('contact');

        $this->setTabColors($viewName);
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $idcontacto = $this->getViewModelValue($mainViewName, 'idcontacto');

        switch ($viewName) {
            case 'EditCrmInteresContacto':
            case 'EditCrmListaContacto':
            case 'ListCrmOportunidad':
                $where = [new DataBaseWhere('idcontacto', $idcontacto)];
                $view->loadData('', $where);
                break;

            case 'EditCrmNota':
                $where = [new DataBaseWhere('idcontacto', $idcontacto)];
                $view->loadData('', $where, ['fecha' => 'DESC']);
                $this->views[$viewName]->model->nick = $this->user->nick;
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function setTabColors(string $viewName): void
    {
        $crmOpoEstado = new CrmOportunidadEstado();
        foreach ($crmOpoEstado->all([], [], 0, 0) as $estado) {
            if (empty($estado->color)) {
                continue;
            }

            $row = $this->tab($viewName)->getRow('status');
            if (empty($row)) {
                continue;
            }

            $row->options[] = [
                'tag' => 'option',
                'children' => [],
                'color' => $estado->color,
                'fieldname' => 'idestado',
                'text' => $estado->id,
                'title' => $estado->nombre
            ];
        }
    }
}
