<?php
/**
 * This file is part of HHRRSolwedDestinos plugin for FacturaScripts.
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
namespace FacturaScripts\Plugins\HHRRSolwedDestinos\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Plugins\HumanResources\Model\Employee;

/**
 * Model for employee shift assignments
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EmployeeShift extends ModelClass
{
    use ModelTrait;

    /**
     * Employee ID (Foreign Key)
     *
     * @var int
     */
    public $idemployee;

    /**
     * Shift ID (Foreign Key)
     *
     * @var int
     */
    public $idshift;

    /**
     * Assignment date
     *
     * @var string
     */
    public $assignment_date;

    /**
     * Start date
     *
     * @var string
     */
    public $start_date;

    /**
     * End date
     *
     * @var string
     */
    public $end_date;

    /**
     * Notes
     *
     * @var string
     */
    public $notes;

    /**
     * Active status
     *
     * @var bool
     */
    public $active;

    /**
     * Creation date
     *
     * @var string
     */
    public $creation_date;

    /**
     * Last nick
     *
     * @var string
     */
    public $last_nick;

    /**
     * Last update
     *
     * @var string
     */
    public $last_update;

    /**
     * Nick
     *
     * @var string
     */
    public $nick;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->active = true;
        $this->assignment_date = date('Y-m-d');
    }

    /**
     * Returns the name of the column that is the model's primary key.
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     */
    public static function tableName(): string
    {
        return 'rrhh_employeesshifts';
    }

    /**
     * Returns the name of the controller for this model.
     */
    public function modelClassName(): string
    {
        return 'EmployeeShift';
    }

    /**
     * Returns the employee associated with this assignment
     *
     * @return Employee|null
     */
    public function getEmployee()
    {
        if (empty($this->idemployee)) {
            return null;
        }

        $employee = new Employee();
        return $employee->get($this->idemployee);
    }

    /**
     * Returns the shift associated with this assignment
     *
     * @return Shift|null
     */
    public function getShift()
    {
        if (empty($this->idshift)) {
            return null;
        }

        $shift = new Shift();
        return $shift->get($this->idshift);
    }

    /**
     * Check if the assignment is currently active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if (!$this->active) {
            return false;
        }

        $today = date('Y-m-d');
        
        // Check if today is within the assignment period
        if (!empty($this->start_date) && $today < $this->start_date) {
            return false;
        }
        
        if (!empty($this->end_date) && $today > $this->end_date) {
            return false;
        }
        
        return true;
    }

    /**
     * Check for overlapping shift assignments for the same employee
     *
     * @return bool
     */
    public function checkOverlappingAssignments(): bool
    {
        if (empty($this->idemployee)) {
            return false;
        }

        $where = [
            new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('idemployee', $this->idemployee),
            new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('active', true)
        ];

        // Exclude current record if editing
        if (!empty($this->id)) {
            $where[] = new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('id', $this->id, '!=');
        }

        // Check for date overlap
        $assignments = $this->all($where);
        
        foreach ($assignments as $assignment) {
            if ($this->datesOverlap($assignment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if two assignments have overlapping dates
     *
     * @param EmployeeShift $other
     * @return bool
     */
    private function datesOverlap(EmployeeShift $other): bool
    {
        $start1 = $this->start_date ?? '1900-01-01';
        $end1 = $this->end_date ?? '2099-12-31';
        $start2 = $other->start_date ?? '1900-01-01';
        $end2 = $other->end_date ?? '2099-12-31';

        return ($start1 <= $end2) && ($start2 <= $end1);
    }

    /**
     * Validate the model data
     *
     * @return bool
     */
    public function test(): bool
    {
        if (empty($this->idemployee)) {
            $this->toolBox()->i18nLog()->warning('employee-required');
            return false;
        }

        if (empty($this->idshift)) {
            $this->toolBox()->i18nLog()->warning('shift-required');
            return false;
        }

        if (empty($this->assignment_date)) {
            $this->toolBox()->i18nLog()->warning('assignment-date-required');
            return false;
        }

        // Validate date format
        if (!strtotime($this->assignment_date)) {
            $this->toolBox()->i18nLog()->warning('invalid-date-format');
            return false;
        }

        if (!empty($this->start_date) && !strtotime($this->start_date)) {
            $this->toolBox()->i18nLog()->warning('invalid-start-date-format');
            return false;
        }

        if (!empty($this->end_date) && !strtotime($this->end_date)) {
            $this->toolBox()->i18nLog()->warning('invalid-end-date-format');
            return false;
        }

        // Validate date order
        if (!empty($this->start_date) && !empty($this->end_date) && $this->start_date > $this->end_date) {
            $this->toolBox()->i18nLog()->warning('end-date-before-start-date');
            return false;
        }

        // Check for overlapping assignments
        if ($this->checkOverlappingAssignments()) {
            $this->toolBox()->i18nLog()->warning('overlapping-assignments');
            return false;
        }

        return parent::test();
    }
}