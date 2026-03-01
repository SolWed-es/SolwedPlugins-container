<?php
/**
 * This file is part of HumanResourcesSolwed plugin for FacturaScripts.
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
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit employee shift assignments
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditEmployeeShift extends EditController
{

    private const VIEW_CONFLICTS = 'ListConflicts';

    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'EmployeeShift';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'employee-shift';
        $pagedata['icon'] = 'fas fa-user-clock';
        $pagedata['menu'] = 'rrhh';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('top');

        // Add a tab for potential conflicts
        $this->createViewConflicts();
    }

    /**
     * Create conflicts view
     *
     * @param string $viewName
     */
    private function createViewConflicts(string $viewName = self::VIEW_CONFLICTS)
    {
        $this->addListView($viewName, 'EmployeeShift', 'potential-conflicts', 'fas fa-exclamation-triangle');

        // Configure the conflicts view
        $this->views[$viewName]->addOrderBy(['assignment_date'], 'assignment-date');
        $this->views[$viewName]->addOrderBy(['start_date'], 'start-date');

        // Add filters for conflicts
        $this->views[$viewName]->addFilterAutocomplete('idemployee', 'employee', 'idemployee', 'rrhh_employees', 'id', 'nombre');
        $this->views[$viewName]->addFilterPeriod('start_date', 'start-date', 'start_date');
        $this->views[$viewName]->addFilterPeriod('end_date', 'end-date', 'end_date');
    }

    /**
     * Load data for views
     *
     * @param string $viewName
     * @param mixed $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case self::VIEW_CONFLICTS:
                $this->loadConflictsData($viewName, $view);
                break;

            case 'EditEmployeeShift':
                parent::loadData($viewName, $view);
                // Ensure calculated fields are loaded for the main model
                if ($this->getModel()->exists()) {
                    $employee = $this->getModel()->getEmployee();
                    $this->getModel()->employee_name = $employee ? $employee->nombre : 'Employee #' . $this->getModel()->idemployee;

                    $shift = $this->getModel()->getShift();
                    if ($shift) {
                        $this->getModel()->shift_location = $shift->location;
                        $this->getModel()->shift_number = $shift->shift_number;
                    } else {
                        $this->getModel()->shift_location = 'Shift #' . $this->getModel()->idshift;
                        $this->getModel()->shift_number = 0;
                    }
                }
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Load conflicts data
     *
     * @param string $viewName
     * @param mixed $view
     */
    private function loadConflictsData(string $viewName, $view)
    {
        if ($this->getModel()->exists()) {
            $employeeId = $this->getModel()->idemployee;
            $startDate = $this->getModel()->start_date;
            $endDate = $this->getModel()->end_date;
            $assignmentId = $this->getModel()->id;

            $where = [
                new DataBaseWhere('idemployee', $employeeId),
                new DataBaseWhere('active', true)
            ];

            // Exclude current record
            if (!empty($assignmentId)) {
                $where[] = new DataBaseWhere('id', $assignmentId, '!=');
            }

            // Check for date overlaps
            if (!empty($startDate) || !empty($endDate)) {
                if (!empty($startDate) && !empty($endDate)) {
                    // Both dates specified - check for any overlap
                    $where[] = new DataBaseWhere('start_date', $endDate, '<=', 'OR');
                    $where[] = new DataBaseWhere('end_date', $startDate, '>=', 'OR');
                } elseif (!empty($startDate)) {
                    // Only start date - check if conflicts with open-ended or future assignments
                    $where[] = new DataBaseWhere('end_date', $startDate, '>=', 'OR');
                    $where[] = new DataBaseWhere('end_date', null, 'IS', 'OR');
                } elseif (!empty($endDate)) {
                    // Only end date - check if conflicts with past or open-ended assignments
                    $where[] = new DataBaseWhere('start_date', $endDate, '<=', 'OR');
                    $where[] = new DataBaseWhere('start_date', null, 'IS', 'OR');
                }
            }

            $view->loadData('', $where);

            // Load calculated fields for conflicts
            foreach ($view->cursor as $model) {
                if ($model instanceof \FacturaScripts\Plugins\HumanResourcesSolwed\Model\EmployeeShift) {
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
}
