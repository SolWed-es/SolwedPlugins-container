<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2020-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Vehiculos;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\AjaxForms\SalesHeaderHTML;
use FacturaScripts\Core\Model\Role;
use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Dinamic\Model\FacturaCliente;

/**
 * Description of Init
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class Init extends InitClass
{
    const ROLE_NAME = 'Vehiculos';

    public function init(): void
    {
        // Vehicle management extensions - CONTROLLERS
        $this->loadExtension(new Extension\Controller\EditCliente());
        $this->loadExtension(new Extension\Controller\EditFacturaCliente());
        $this->loadExtension(new Extension\Controller\EditAlbaranCliente());
        $this->loadExtension(new Extension\Controller\EditPedidoCliente());
        $this->loadExtension(new Extension\Controller\EditPresupuestoCliente());

        // Vehicle management extensions - MODELS
        $this->loadExtension(new Extension\Model\FacturaCliente());
        $this->loadExtension(new Extension\Model\AlbaranCliente());
        $this->loadExtension(new Extension\Model\PedidoCliente());
        $this->loadExtension(new Extension\Model\PresupuestoCliente());

        // Register vehicle selector in sales header
        SalesHeaderHTML::addMod(new Mod\SalesHeaderHTMLMod());

        // PlantillasPDF integration for vehicle information in PDFs
        /*if (Plugins::isEnabled('PlantillasPDF')) {
            // Register extension for all PlantillasPDF templates using the official hook system
            $extension = new Extension\PlantillasPDF\BaseTemplateExtension();

            // Register for each template (Template1 through Template5)
            foreach (['Template1', 'Template2', 'Template3', 'Template4', 'Template5'] as $templateName) {
                $templateClass = '\\FacturaScripts\\Dinamic\\Lib\\PlantillasPDF\\' . $templateName;
                if (class_exists($templateClass)) {
                    // Priority 200 to ensure Vehiculos extension runs with high priority
                    $templateClass::addExtension($extension, 200);
                }
            }
        }*/
    }

    public function uninstall(): void
    {
    }

    public function update(): void
    {
        new Model\Vehiculo();
        new FacturaCliente();

        $this->createRoleForPlugin();
    }

    private function createRoleForPlugin(): void
    {
        $dataBase = new DataBase();
        $dataBase->beginTransaction();

        // creates the role if not exists
        $role = new Role();
        if (false === $role->loadFromCode(self::ROLE_NAME)) {
            $role->codrole = $role->descripcion = self::ROLE_NAME;
            if (false === $role->save()) {
                // rollback and exit on fail
                $dataBase->rollback();
                return;
            }
        }

        // Permisos para controladores de vehículos
    $nameControllers = ['EditVehiculo', 'ListVehiculo'];
        foreach ($nameControllers as $nameController) {
            $roleAccess = new RoleAccess();
            $where = [
                new DataBaseWhere('codrole', self::ROLE_NAME),
                new DataBaseWhere('pagename', $nameController)
            ];
            if ($roleAccess->loadFromCode('', $where)) {
                // permission exists? Then skip
                continue;
            }

            // creates the permission if not exists
            $roleAccess->allowdelete = true;
            $roleAccess->allowupdate = true;
            $roleAccess->codrole = self::ROLE_NAME;
            $roleAccess->pagename = $nameController;
            $roleAccess->onlyownerdata = false;
            if (false === $roleAccess->save()) {
                // rollback and exit on fail
                $dataBase->rollback();
                return;
            }
        }

        // without problems = Commit
        $dataBase->commit();
    }
}
