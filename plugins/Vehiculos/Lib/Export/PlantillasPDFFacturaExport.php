<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
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

namespace FacturaScripts\Plugins\Vehiculos\Lib\Export;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Vehiculos\Lib\Config\Settings;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;
use FacturaScripts\Plugins\PlantillasPDF\Lib\Export\PDFExport;

/**
 * PlantillasPDF extension to add vehicle data in invoices
 * Optimized for PHP 8.1+ with performance improvements and modern syntax
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PlantillasPDFFacturaExport extends PDFExport
{
    private ?Vehiculo $vehicleCache = null;

    /**
     * Add PDF page with model data, including vehicle information if applicable
     */
    public function addModelPage($model, $columns, string $title = ''): bool
    {
        // Call parent method to generate base PDF
        $result = parent::addModelPage($model, $columns, $title);

        // Añade datos de vehículo siempre que exista uno asociado (config deshabilitada por requerimiento)
        if ($this->hasVehicle($model)) {
            $this->addVehicleData($model);
        }

        return $result;
    }

    /**
     * Determine if vehicle data should be displayed
     * Optimized to avoid unnecessary queries
     */
    private function hasVehicle($model): bool
    {
        // Intento rápido de detección sin consultas: si tiene método o propiedad relevante
        if (method_exists($model, 'getVehiculo')) {
            return true;
        }
        if (property_exists($model, 'idmaquina') && !empty($model->idmaquina)) {
            return true;
        }
        if (property_exists($model, 'codcliente') && !empty($model->codcliente)) {
            return true;
        }
        return false;
    }

    /**
     * Add vehicle data to PDF with performance caching
     */
    private function addVehicleData($model): void
    {
        $vehicle = $this->getVehicle($model);
        if ($vehicle === null) {
            return;
        }

        // Prepare vehicle data optimally
        $vehicleData = $this->prepareVehicleData($vehicle);

        if (empty($vehicleData)) {
            return;
        }

        // Agregar sección de vehículo al PDF
        $this->addVehicleSection($vehicleData);
    }

    /**
     * Obtiene el vehículo con caché para optimización
     *
     * @param FacturaCliente $model
    * @return Vehiculo|null
     */
    private function getVehicle($model): ?Vehiculo
    {
        if ($this->vehicleCache !== null) {
            return $this->vehicleCache;
        }

        // 1) Método directo del modelo si existe
        if (method_exists($model, 'getVehiculo')) {
            try {
                $veh = $model->getVehiculo();
                if ($veh instanceof Vehiculo) {
                    return $this->vehicleCache = $veh;
                }
            } catch (\Throwable $e) {
                // Continuar con otras opciones
            }
        }

        // 2) Cargar por idmaquina si existe
        if (property_exists($model, 'idmaquina') && !empty($model->idmaquina)) {
            $tmp = new Vehiculo();
            if ($tmp->loadFromCode((int)$model->idmaquina)) {
                return $this->vehicleCache = $tmp;
            }
        }

        // 3) Fallback: primer vehículo del cliente
        if (property_exists($model, 'codcliente') && !empty($model->codcliente)) {
            try {
                $vehicles = (new Vehiculo())->all(
                    [new DataBaseWhere('codcliente', $model->codcliente)],
                    ['matricula' => 'ASC', 'marca' => 'ASC', 'modelo' => 'ASC'],
                    0,
                    1
                );
                if (!empty($vehicles)) {
                    return $this->vehicleCache = $vehicles[0];
                }
            } catch (\Throwable $e) {
                // ignorar
            }
        }

        return null;
    }

    /**
     * Prepara los datos del vehículo en formato estructurado
     * Optimizado para evitar consultas innecesarias
     *
    * @param Vehiculo $vehicle
     * @return array
     */
    private function prepareVehicleData(Vehiculo $vehicle): array
    {
        $data = [];

        // Obtener marca y modelo
        $marca = trim((string)$vehicle->marca);
        $modelo = trim((string)$vehicle->modelo);

        $matricula = trim((string)$vehicle->matricula);

        if ($marca !== '') {
            $data['Marca'] = ['title' => 'Marca', 'value' => $marca];
        }
        if ($modelo !== '') {
            $data['Modelo'] = ['title' => 'Modelo', 'value' => $modelo];
        }
        if ($matricula !== '') {
            $data['Matrícula'] = ['title' => 'Matrícula', 'value' => strtoupper($matricula)];
        }

        return $data;
    }

    /**
     * Agrega la sección del vehículo al PDF usando el template
     *
     * @param array $vehicleData
     */
    private function addVehicleSection(array $vehicleData): void
    {
        // Render compacto en una sola fila usando clases existentes (sin estilos nuevos)
        $this->addVehicleTable($vehicleData);
    }

    /**
     * Método alternativo para agregar como tabla simple (más compatible)
     *
     * @param array $vehicleData
     */
    private function addVehicleTable(array $vehicleData): void
    {
        // Construir una única fila horizontal Marca | Modelo | Matrícula
        // con clases ya definidas: table-big y table-border
        $order = ['Marca', 'Modelo', 'Matrícula'];
        $cells = [];
        foreach ($order as $key) {
            if (isset($vehicleData[$key]) && $vehicleData[$key]['value'] !== '') {
                $title = htmlspecialchars($vehicleData[$key]['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $value = htmlspecialchars($vehicleData[$key]['value'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $cells[] = '<td><b>' . $title . ':</b> ' . $value . '</td>';
            }
        }
        if (empty($cells)) {
            return;
        }

        $html = '<table class="table-big table-border"><tr>' . implode('', $cells) . '</tr></table>';
        $this->template->writeHTML($html);
    }

    /**
     * Configura el template respetando la plantilla del usuario.
     * Usa la extensión de Vehículos si está disponible, sino usa el template del sistema.
     */
    protected function setTemplate()
    {
        // Obtener la plantilla configurada en el sistema
        $templateName = $this->toolBox()->appSettings()->get('plantillaspdf', 'template', 'template1');

        // Construir el nombre de clase de la extensión del plugin para esta plantilla
        $className = '\\FacturaScripts\\Plugins\\Vehiculos\\Lib\\PlantillasPDF\\' . ucfirst($templateName);

        if (class_exists($className)) {
            // Si existe extensión de Vehículos para esta plantilla, usar la extensión
            $this->template = new $className();
            return;
        }

        // Si no existe extensión para esta plantilla, usar el template del sistema
        parent::setTemplate();
    }
}
