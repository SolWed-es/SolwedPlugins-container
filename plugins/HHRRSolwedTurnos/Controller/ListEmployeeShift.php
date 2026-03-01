<?php
/**
 * This file is part of HHRRSolwedTurnos plugin for FacturaScripts.
 *
 * Copyright (C) 2024 José Ferrán
 *
 * This plugin is developed by José Ferrán based on the excellent work of the
 * Human Resources plugin created by Carlos García Gómez <carlos@facturascripts.com>
 * and Jose Antonio Cuello Principal <yopli2000@gmail.com>.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\HHRRSolwedTurnos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list employee shift assignments
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListEmployeeShift extends ListController
{

    private const VIEW_EMPLOYEE_SHIFTS = 'ListEmployeeShift';

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'turnos-asignaciones';
        $pagedata['icon'] = 'fas fa-user-clock';
        $pagedata['menu'] = 'rrhh';
        $pagedata['ordernum'] = 101;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewEmployeeShifts();
    }

    /**
     * Create employee shifts view
     *
     * @param string $viewName
     */
    private function createViewEmployeeShifts(string $viewName = self::VIEW_EMPLOYEE_SHIFTS)
    {
        $this->addView($viewName, 'EmployeeShift', 'employee-shifts', 'fas fa-user-clock');
        
        // Add search fields
        $this->addSearchFields($viewName, ['employees.nombre', 'rrhh_shifts.location', 'notes']);
        
        // Add order options
        $this->addOrderBy($viewName, ['assignment_date'], 'assignment-date', 2);
        $this->addOrderBy($viewName, ['start_date'], 'start-date');
        $this->addOrderBy($viewName, ['end_date'], 'end-date');
        $this->addOrderBy($viewName, ['employees.nombre'], 'employee');
        $this->addOrderBy($viewName, ['rrhh_shifts.location'], 'shift');
        $this->addOrderBy($viewName, ['active'], 'active');

        // Filters
        $this->addFilterAutocomplete($viewName, 'idemployee', 'employee', 'idemployee', 'rrhh_employees', 'id', 'nombre');
        $this->addFilterAutocomplete($viewName, 'idshift', 'shift', 'idshift', 'rrhh_shifts', 'id', 'location');
        $this->addFilterCheckbox($viewName, 'active', 'active');
        $this->addFilterPeriod($viewName, 'assignment_date', 'assignment-date', 'assignment_date');
        $this->addFilterPeriod($viewName, 'start_date', 'start-date', 'start_date');
        $this->addFilterPeriod($viewName, 'end_date', 'end-date', 'end_date');
    }

    /**
     * Runs the actions that alter the data before reading it.
     *
     * @param string $action
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        return parent::execPreviousAction($action);
    }

    /**
     * Load data for views
     *
     * @param string $viewName
     * @param mixed $view
     */
    protected function loadData($viewName = '', $view = null)
    {
        switch ($viewName) {
            case self::VIEW_EMPLOYEE_SHIFTS:
                parent::loadData($viewName, $view);
                $this->loadCalculatedFields($viewName);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Load calculated fields for employee shift data
     *
     * @param string $viewName
     */
    private function loadCalculatedFields(string $viewName)
    {
        $view = $this->views[$viewName];
        
        foreach ($view->cursor as $model) {
            if ($model instanceof \FacturaScripts\Plugins\HHRRSolwedTurnos\Model\EmployeeShift) {
                // Load employee name
                $employee = $model->getEmployee();
                $model->employee_name = $employee ? $employee->nombre : 'Employee #' . $model->idemployee;
                
                // Load shift location
                $shift = $model->getShift();
                if ($shift) {
                    $model->shift_location = $shift->location;
                    $model->shift_number = $shift->shift_number;
                } else {
                    $model->shift_location = 'Shift #' . $model->idshift;
                    $model->shift_number = 0;
                }
            }
        }
    }
}