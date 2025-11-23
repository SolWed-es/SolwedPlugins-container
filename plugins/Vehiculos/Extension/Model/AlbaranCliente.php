<?php

/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Vehiculos\Extension\Model;

use Closure;
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;
use FacturaScripts\Core\Tools;

/**
 * Extension for AlbaranCliente model to add vehicle/machine support
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class AlbaranCliente
{
    /**
     * Identificador del vehículo asociado
     * @var int|null
     */
    public ?int $idmaquina = null;

    public function loadFromData(array $data = [], array $exclude = []): Closure
    {
        return function (array $data = [], array $exclude = []) {
            // Asegurar que idmaquina se carga desde los datos de la BD
            if (isset($data['idmaquina'])) {
                $this->idmaquina = empty($data['idmaquina']) ? null : (int)$data['idmaquina'];
            } else {
                $this->idmaquina = null;
            }
        };
    }

    public function clear(): Closure
    {
        return function () {
            $this->idmaquina = null;
        };
    }

    /**
     * Get the vehicle associated with this delivery note
     *
     * @return Vehiculo|null
     */
    public function getVehiculo(): Closure
    {
        return function () {
            if (empty($this->idmaquina)) {
                return null;
            }

            $vehiculo = new Vehiculo();
            if ($vehiculo->loadFromCode($this->idmaquina)) {
                return $vehiculo;
            }

            return null;
        };
    }

    public function test(): Closure
    {
        return function () {
            $vehicleRequired = Tools::settings('vehiculos', 'vehicle_required_in_delivery', false);
            if ($vehicleRequired && empty($this->idmaquina) && !empty($this->codcliente)) {
                Tools::log()->warning('vehicle-required');
                return false;
            }

            // Validar que el vehículo pertenezca al cliente seleccionado
            if (!empty($this->idmaquina) && !empty($this->codcliente)) {
                $vehiculo = new Vehiculo();
                if ($vehiculo->loadFromCode($this->idmaquina)) {
                    if (!empty($vehiculo->codcliente) && $vehiculo->codcliente !== $this->codcliente) {
                        Tools::log()->warning('vehicle-does-not-belong-to-customer');
                        $this->idmaquina = null;
                        return false;
                    }
                }
            }

            return true;
        };
    }
}
