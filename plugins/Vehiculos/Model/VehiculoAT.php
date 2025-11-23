<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Vehiculos\Model;

/**
 * @deprecated since version 1.1. Use Vehiculo instead. This class will be removed in version 2.0
 *
 * Alias de compatibilidad hacia atrás para Vehiculo
 * Toda la lógica está en Vehiculo
 *
 * IMPORTANTE: Esta clase se mantiene únicamente para compatibilidad hacia atrás.
 * Por favor, actualice su código para usar el modelo Vehiculo directamente:
 *
 * Antes: $vehiculoAT = new VehiculoAT();
 * Ahora:  $vehiculo = new Vehiculo();
 *
 * NOTA: Esta clase proporciona compatibilidad con el campo 'fecha_matriculacion'
 * que ahora se llama 'fecha_primera_matriculacion' en el modelo Vehiculo.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class VehiculoAT extends Vehiculo
{
    /**
     * Getter para fecha_matriculacion (alias de fecha_primera_matriculacion para compatibilidad)
     */
    public function __get($name)
    {
        if ($name === 'fecha_matriculacion') {
            return $this->fecha_primera_matriculacion ?? null;
        }
        return parent::__get($name);
    }

    /**
     * Setter para fecha_matriculacion (alias de fecha_primera_matriculacion para compatibilidad)
     */
    public function __set($name, $value)
    {
        if ($name === 'fecha_matriculacion') {
            $this->fecha_primera_matriculacion = $value;
            return;
        }
        parent::__set($name, $value);
    }
}
