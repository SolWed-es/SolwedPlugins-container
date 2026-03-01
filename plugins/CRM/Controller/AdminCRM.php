<?php
/**
 * Copyright (C) 2022-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Dinamic\Lib\ExtendedController\BaseView;
use FacturaScripts\Dinamic\Lib\ExtendedController\PanelController;

/**
 * Description of AdminCRM
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class AdminCRM extends PanelController
{
    private const VIEW_LIST_SOURCES = 'ListCrmFuente';
    private const VIEW_LIST_INTERESTS = 'ListCrmInteres';
    private const VIEW_EDIT_POSITIONS = 'EditCrmPosition';
    private const VIEW_EDIT_STATUS = 'EditCrmOportunidadEstado';

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'crm';
        $data['icon'] = 'fa-solid fa-user-tag';
        return $data;
    }

    /**
     * Inserts the views or tabs to display.
     */
    protected function createViews()
    {
        $this->setTemplate('EditSettings');

        $this->createViewSources();
        $this->createViewInterests();
        $this->createViewStatus();
        $this->createViewPositions();
    }

    protected function createViewInterests(string $viewName = self::VIEW_LIST_INTERESTS): void
    {
        $this->addListView($viewName, 'CrmInteres', 'interests', ' fa-solid fa-heart')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['numcontactos'], 'contacts')
            ->addOrderBy(['descripcion'], 'description')
            ->addSearchFields(['nombre', 'descripcion']);
    }

    protected function createViewPositions(string $viewName = self::VIEW_EDIT_POSITIONS): void
    {
        $this->addEditListView($viewName, 'CrmPosition', 'positions', 'fa-solid fa-building-user')
            ->setInLine(true);
    }

    protected function createViewSources(string $viewName = self::VIEW_LIST_SOURCES): void
    {
        $this->addListView($viewName, 'CrmFuente', 'sources', 'fa-solid fa-file-import')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['numcontactos'], 'contacts')
            ->addOrderBy(['descripcion'], 'description')
            ->addSearchFields(['nombre', 'descripcion']);
    }

    protected function createViewStatus(string $viewName = self::VIEW_EDIT_STATUS): void
    {
        $this->addEditListView($viewName, 'CrmOportunidadEstado', 'states', 'fa-solid fa-tags')
            ->setInLine(true);
    }

    /**
     * Loads the data to display.
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case self::VIEW_EDIT_POSITIONS:
                $view->loadData('', [], ['name' => 'DESC']);
                break;

            case self::VIEW_EDIT_STATUS:
                $view->loadData('', [], ['orden' => 'ASC']);
                break;

            case self::VIEW_LIST_SOURCES:
            case self::VIEW_LIST_INTERESTS:
                $view->loadData();
                break;
        }

        $this->hasData = true;
    }
}
