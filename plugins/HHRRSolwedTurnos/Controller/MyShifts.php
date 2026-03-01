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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\HumanResources\Model\Employee;
use FacturaScripts\Plugins\HHRRSolwedTurnos\Model\EmployeeShift;
use FacturaScripts\Plugins\HHRRSolwedTurnos\Model\Shift;

/**
 * Controller for employees to view their own shifts
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class MyShifts extends Controller
{
    /**
     * Current employee
     *
     * @var Employee
     */
    protected $employee;

    /**
     * Current month for calendar
     *
     * @var string
     */
    protected $currentMonth;

    /**
     * Current year for calendar
     *
     * @var string
     */
    protected $currentYear;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'rrhh';
        $data['ordernum'] = 102;
        $data['title'] = 'turnos-mis-turnos';
        $data['icon'] = 'fas fa-calendar-alt';
        return $data;
    }

    /**
     * Get translations for the template
     *
     * @return array
     */
    public function getTranslations(): array
    {
        // Load translations from plugin files
        $lang = $this->toolBox()->appSettings()->get('default', 'lang', 'es_ES');
        $pluginTranslations = $this->loadPluginTranslations($lang);

        // Ensure all required translations are available
        $requiredTranslations = [
            'my-shifts',
            'previous-month',
            'next-month',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
            'today',
            'shift',
            'no-shifts-assigned',
            'contact-admin-for-shift-assignment',
            'shift-details',
            'shift-location',
            'shift-number',
            'assignment-date',
            'start-date',
            'end-date',
            'active',
            'notes',
            'yes',
            'no',
            'no-date',
            'employee-name',
            'exit-time',
            'entry-time',
            'location'
        ];

        // Default Spanish translations as fallback
        $fallbackTranslations = [
            'my-shifts' => 'Mis Turnos',
            'previous-month' => 'Mes Anterior',
            'next-month' => 'Mes Siguiente',
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
            'today' => 'Hoy',
            'shift' => 'Turno',
            'no-shifts-assigned' => 'No hay turnos asignados',
            'contact-admin-for-shift-assignment' => 'Contacte al administrador para asignación de turnos',
            'shift-details' => 'Detalles de Turnos',
            'shift-location' => 'Ubicación del Turno',
            'shift-number' => 'Número de Turno',
            'assignment-date' => 'Fecha de Asignación',
            'start-date' => 'Fecha Inicio',
            'end-date' => 'Fecha Fin',
            'active' => 'Activo',
            'notes' => 'Notas',
            'yes' => 'Sí',
            'no' => 'No',
            'no-date' => 'Sin fecha',
            'employee-name' => 'Nombre del Empleado',
            'exit-time' => 'Hora Salida',
            'entry-time' => 'Hora Entrada',
            'location' => 'Ubicación'
        ];

        // Merge plugin translations with fallback
        $translations = array_merge($fallbackTranslations, $pluginTranslations);

        // Ensure all required keys have values
        foreach ($requiredTranslations as $key) {
            if (!isset($translations[$key])) {
                $translations[$key] = $fallbackTranslations[$key] ?? $key;
            }
        }

        return $translations;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param mixed $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Check if user is an employee
        $this->employee = $this->getEmployeeFromUser($user);
        if (empty($this->employee)) {
            Tools::log()->warning('user-not-employee');
            $this->redirect('Dashboard');
            return;
        }

        // Set current month and year
        $this->currentMonth = $this->request->get('month', date('m'));
        $this->currentYear = $this->request->get('year', date('Y'));

        // Handle actions
        $action = $this->request->get('action', '');
        switch ($action) {
            // Future actions can be added here
        }
    }

    /**
     * Get employee from user
     *
     * @param User $user
     * @return Employee|null
     */
    protected function getEmployeeFromUser($user): ?Employee
    {
        $employee = new Employee();
        $where = [new DataBaseWhere('email', $user->email)];
        if ($employee->loadFromCode('', $where)) {
            return $employee;
        }

        // Try by nick
        $where = [new DataBaseWhere('nick', $user->nick)];
        if ($employee->loadFromCode('', $where)) {
            return $employee;
        }

        return null;
    }

    /**
     * Get employee shifts for current month
     *
     * @return array
     */
    public function getEmployeeShifts(): array
    {
        if (empty($this->employee)) {
            return [];
        }

        $employeeShift = new EmployeeShift();
        $where = [
            new DataBaseWhere('idemployee', $this->employee->id),
            new DataBaseWhere('active', true)
        ];

        // Filter by current month/year
        $startDate = $this->currentYear . '-' . str_pad($this->currentMonth, 2, '0', STR_PAD_LEFT) . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $where[] = new DataBaseWhere('start_date', $endDate, '<=', 'OR');
        $where[] = new DataBaseWhere('start_date', null, 'IS', 'OR');
        $where[] = new DataBaseWhere('end_date', $startDate, '>=', 'OR');
        $where[] = new DataBaseWhere('end_date', null, 'IS', 'OR');

        $shifts = $employeeShift->all($where, ['assignment_date' => 'ASC']);

        // Load calculated fields
        foreach ($shifts as $shift) {
            $employee = $shift->getEmployee();
            $shift->employee_name = $employee ? $employee->nombre : 'Employee #' . $shift->idemployee;
            
            $shiftData = $shift->getShift();
            if ($shiftData) {
                $shift->shift_location = $shiftData->location;
                $shift->shift_number = $shiftData->shift_number;
            } else {
                $shift->shift_location = 'Shift #' . $shift->idshift;
                $shift->shift_number = 0;
            }
        }

        return $shifts;
    }

    /**
     * Get available shifts for requests
     *
     * @return array
     */
    public function getAvailableShifts(): array
    {
        $shift = new Shift();
        return $shift->all([], ['location' => 'ASC']);
    }

    /**
     * Get calendar data for current month
     *
     * @return array
     */
    public function getCalendarData(): array
    {
        $shifts = $this->getEmployeeShifts();
        $calendar = [];

        // Create calendar structure
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->currentMonth, $this->currentYear);
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $this->currentYear . '-' . str_pad($this->currentMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $calendar[$date] = [];
        }

        // Add shifts to calendar
        foreach ($shifts as $shift) {
            $startDate = $shift->start_date ?: $this->currentYear . '-' . str_pad($this->currentMonth, 2, '0', STR_PAD_LEFT) . '-01';
            $endDate = $shift->end_date ?: $this->currentYear . '-' . str_pad($this->currentMonth, 2, '0', STR_PAD_LEFT) . '-' . cal_days_in_month(CAL_GREGORIAN, $this->currentMonth, $this->currentYear);

            $start = max(strtotime($startDate), strtotime($this->currentYear . '-' . str_pad($this->currentMonth, 2, '0', STR_PAD_LEFT) . '-01'));
            $end = min(strtotime($endDate), strtotime($this->currentYear . '-' . str_pad($this->currentMonth, 2, '0', STR_PAD_LEFT) . '-' . cal_days_in_month(CAL_GREGORIAN, $this->currentMonth, $this->currentYear)));

            for ($timestamp = $start; $timestamp <= $end; $timestamp += 86400) {
                $date = date('Y-m-d', $timestamp);
                if (isset($calendar[$date])) {
                    $calendar[$date][] = $shift;
                }
            }
        }

        return $calendar;
    }

    /**
     * Handle shift request action
     */

    /**
     * Get navigation data for month/year selector
     *
     * @return array
     */
    public function getNavigationData(): array
    {
        $prevMonth = $this->currentMonth - 1;
        $prevYear = $this->currentYear;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $nextMonth = $this->currentMonth + 1;
        $nextYear = $this->currentYear;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        return [
            'current' => [
                'month' => $this->currentMonth,
                'year' => $this->currentYear,
                'monthName' => date('F', mktime(0, 0, 0, $this->currentMonth, 1))
            ],
            'prev' => [
                'month' => $prevMonth,
                'year' => $prevYear
            ],
            'next' => [
                'month' => $nextMonth,
                'year' => $nextYear
            ]
        ];
    }

    /**
     * Load plugin translations manually
     *
     * @param string $lang
     * @return array
     */
    private function loadPluginTranslations(string $lang): array
    {
        $translationFile = FS_FOLDER . '/Plugins/HHRRSolwedTurnos/Data/Lang/' . $lang . '.json';

        if (file_exists($translationFile)) {
            $content = file_get_contents($translationFile);
            $translations = json_decode($content, true);
            return is_array($translations) ? $translations : [];
        }

        return [];
    }
}