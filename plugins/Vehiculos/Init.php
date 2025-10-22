<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
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

use FacturaScripts\Core\Base\AjaxForms\SalesHeaderHTML;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Role;
use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Controller\SendTicket;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\StockMovementManager;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;

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
        // Vehicle management extensions
        $this->loadExtension(new Extension\Controller\EditCliente());
        $this->loadExtension(new Extension\Controller\EditFacturaCliente());
        $this->loadExtension(new Extension\Model\FacturaCliente());

        // Register vehicle selector in sales header (banner)
        SalesHeaderHTML::addMod(new Mod\SalesHeaderHTMLMod());

        // PlantillasPDF integration for vehicle information in PDFs
        if (Plugins::isEnabled('PlantillasPDF')) {
            // Integración con PlantillasPDF: añadir export con datos de vehículo en documentos de venta
            // Usar Dinamic para resolver dinámicamente la clase del plugin
            $exportClass = '\\FacturaScripts\\Dinamic\\Lib\\Export\\VehiculosPlantillasPDFFacturaExport';

            // Registrar el export personalizado para cada tipo de documento
            ExportManager::addOptionModel($exportClass, 'PDF', 'FacturaCliente');
            ExportManager::addOptionModel($exportClass, 'PDF', 'AlbaranCliente');
            ExportManager::addOptionModel($exportClass, 'PDF', 'PedidoCliente');
            ExportManager::addOptionModel($exportClass, 'PDF', 'PresupuestoCliente');
        }
    }

    public function uninstall(): void
    {
    }

    public function update(): void
    {
        new Model\Vehiculo();
        new FacturaCliente();

        $this->setupSettings();
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

    private function setupSettings(): void
    {
        $defaults = [
            'print_vehicle_in_invoice' => true,
            'print_vehicle_kilometers' => true,
            'print_vehicle_fuel' => true,
            'print_vehicle_color' => false,
            'vehicle_required_in_invoice' => false,
            'document_vehicle_line' => true
        ];

        foreach ($defaults as $key => $value) {
            Tools::settings('vehiculos', $key, $value);
        }

        Tools::settingsSave();
    }

}
