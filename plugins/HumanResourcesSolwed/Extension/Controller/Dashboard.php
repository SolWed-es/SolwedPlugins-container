<?php
/**
 * This file is part of HumanResourcesSolwed plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HumanResourcesSolwed Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\HumanResources\Model\Employee;

/**
 * Extension for Dashboard controller to add shift-related menu items
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
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
