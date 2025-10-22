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

namespace FacturaScripts\Plugins\Vehiculos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;

/**
 * Description of EditCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCliente
{
    protected function createViews(): Closure
    {
        return function () {
            $this->createViewsVehicles();
        };
    }

    protected function createViewsVehicles(): Closure
    {
    return function ($viewName = 'ListVehiculo') {
            $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');

            // Cambiado de 'machines' a 'Vehiculos' (clave existente en traducciones) e icono a coche
            $this->addListView($viewName, 'Vehiculo', 'Vehiculos', 'fa-solid fa-car')
                ->addOrderBy(['idmaquina'], 'code', 2)
                ->addOrderBy(['fecha_primera_matriculacion'], 'date')
                ->addOrderBy(['marca'], 'brand')
                ->addOrderBy(['modelo'], 'model')
                ->addOrderBy(['matricula'], 'license-plate')
                ->addSearchFields(['marca', 'modelo', 'matricula', 'bastidor'])
                ->addFilterPeriod('fecha_primera_matriculacion', 'date', 'fecha_primera_matriculacion')
                ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
                ->addFilterSelect('codagente', 'agent', 'codagente', $agents)
                ->disableColumn('customer');
        };
    }


    protected function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName === 'ListVehiculo') {
                $codcliente = $this->getViewModelValue($this->getMainViewName(), 'codcliente');
                $where = [new DataBaseWhere('codcliente', $codcliente)];
                $view->loadData('', $where);
            }
        };
    }
}
