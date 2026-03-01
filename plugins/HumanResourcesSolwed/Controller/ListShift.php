<?php
/**
 * This file is part of HumanResourcesSolwed plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HumanResourcesSolwed Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list Shift.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListShift extends ListController
{
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'turnos';
        $pagedata['icon'] = 'fas fa-clock';
        $pagedata['menu'] = 'rrhh';
        $pagedata['ordernum'] = 100;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewShifts();
    }

    /**
     * Create shifts view
     *
     * @param string $viewName
     */
    protected function createViewShifts(string $viewName = 'ListShift')
    {
        $this->addView($viewName, 'Shift', 'turnos', 'fas fa-clock');
        $this->addOrderBy($viewName, ['location', 'shift_number'], 'location');
        $this->addOrderBy($viewName, ['shift_number'], 'shift-number');

        // Add search fields
        $this->addSearchFields($viewName, ['location', 'shift_number', 'notes']);

        // Add filters
        $this->addFilterSelect($viewName, 'location', 'location', 'location');
        $this->addFilterCheckbox($viewName, 'active', 'active');
    }
}
