<?php
/**
 * This file is part of HHRRSolwedTurnosSemana plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedTurnosSemana Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list shift week days configuration
 *
 * @author José Ferrán
 */
class ListShiftWeekDay extends ListController
{
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'rrhh';
        $data['title'] = 'daily-schedules';
        $data['icon'] = 'fas fa-calendar-week';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewShiftWeekDays();
    }

    /**
     * Create the main view
     */
    private function createViewShiftWeekDays()
    {
        $this->addView('ListShiftWeekDay', 'ShiftWeekDay', 'daily-schedules', 'fas fa-calendar-week');
        $this->addSearchFields('ListShiftWeekDay', ['dayofweek']);
        $this->addOrderBy('ListShiftWeekDay', ['idshift'], 'shift');
        $this->addOrderBy('ListShiftWeekDay', ['dayofweek'], 'day-of-week');

        // Filters
        $this->addFilterSelect('ListShiftWeekDay', 'idshift', 'shift', 'idshift');
        $this->addFilterNumber('ListShiftWeekDay', 'dayofweek', 'day-of-week', 'dayofweek', 1, 7);
    }
}