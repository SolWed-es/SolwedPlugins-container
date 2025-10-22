<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Vehiculos\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Vehicle edit controller - optimized for PHP 8.1+
 * Handles vehicle data entry and modification
 * 
 * Main controller for vehicles (previously MaquinaAT)
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditVehiculoAT extends EditController
{
    public function getModelClassName(): string
    {
        return 'VehiculoAT';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'vehiculo';
        $data['icon'] = 'fa-solid fa-car';
        $data['showonmenu'] = false;
        return $data;
    }

    protected function createViews(): void
    {
        parent::createViews();
        $this->setTabsPosition('bottom');
        
        // Vista principal del vehículo
        $this->createViewsVehicle();
    }
    
    /**
     * Create vehicle main view
     */
    protected function createViewsVehicle(string $viewName = 'EditVehiculoAT'): void
    {
        $this->addEditView($viewName, 'VehiculoAT', 'vehiculo', 'fa-solid fa-car');
        
        // Configurar campos obligatorios
        $this->views[$viewName]->disableColumn('nombre'); // Campo legacy
    }
}
