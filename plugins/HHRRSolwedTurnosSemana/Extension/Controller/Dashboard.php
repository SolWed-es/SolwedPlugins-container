<?php
/**
 * This file is part of HHRRSolwedTurnosSemana plugin for FacturaScripts.
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

namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\HumanResources\Model\Employee;

/**
 * Extension for Dashboard controller to add shift-related menu items
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Dashboard
{
    /**
     * Add menu items based on user permissions
     */
    public function createViews()
    {
        // This method will be called by the Dashboard controller
        $this->addShiftMenuItems();
    }

    /**
     * Add shift-related menu items based on user type
     */
    protected function addShiftMenuItems()
    {
        $user = $this->user ?? null;
        if (empty($user)) {
            return;
        }

        // Check if user is an employee
        $employee = new Employee();
        $where = [new DataBaseWhere('codcontacto', $user->codcontacto)];
        $isEmployee = $employee->loadFromCode('', $where);

        if ($isEmployee) {
            // Add "My Shifts" menu item for employees
            $this->addMenuItem('rrhh', [
                'name' => 'Mis turnos',
                'title' => 'Mis turnos',
                'icon' => 'fas fa-calendar-alt',
                'url' => 'MyShifts'
            ]);
        }

        // Check if user has admin permissions for HR
        if ($user->admin || $this->hasHRPermissions($user)) {
            // Admin menu items can be added here in the future
        }
    }

    /**
     * Check if user has HR permissions
     *
     * @param User $user
     * @return bool
     */
    protected function hasHRPermissions($user): bool
    {
        // Check if user has permissions for HR controllers
        $hrControllers = ['ListEmployee', 'EditEmployee', 'ListShift', 'EditShift'];
        
        foreach ($hrControllers as $controller) {
            if ($user->can($controller)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add menu item (placeholder method)
     *
     * @param string $menu
     * @param array $item
     */
    protected function addMenuItem(string $menu, array $item)
    {
        // This would be implemented by the actual Dashboard extension
        // For now, it's a placeholder
    }
}