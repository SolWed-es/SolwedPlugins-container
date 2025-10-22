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
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Plugins\Vehiculos\Model\MaquinaAT;
use FacturaScripts\Plugins\PlantillasPDF\Lib\Export\PDFExport;

/**
 * PlantillasPDF extension to add vehicle data in invoices
 * Optimized for PHP 8.1+ with performance improvements and modern syntax
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PlantillasPDFFacturaExport extends PDFExport
{
    private ?MaquinaAT $vehicleCache = null;

    /**
     * Add PDF page with model data, including vehicle information if applicable
     */
    public function addModelPage($model, $columns, string $title = ''): bool
    {
        // Call parent method to generate base PDF
        $result = parent::addModelPage($model, $columns, $title);

        // Add vehicle data if exists and configured
        if ($this->shouldShowVehicleData($model)) {
            $this->addVehicleData($model);
        }

        return $result;
    }

    /**
     * Determine if vehicle data should be displayed
     * Optimized to avoid unnecessary queries
     */
    private function shouldShowVehicleData($model): bool
    {
        // Check global configuration
        if (false === Tools::settings('servicios', 'print_vehicle_in_invoice', true)) {
            return false;
        }

        // Check if model has getMaquina method (extension active)
        if (!method_exists($model, 'getMaquina')) {
            return false;
        }

        // Check if there's an associated vehicle
        return !empty($model->idmaquina);
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
     * @return MaquinaAT|null
     */
    private function getVehicle($model): ?MaquinaAT
    {
        if ($this->vehicleCache !== null) {
            return $this->vehicleCache;
        }

        try {
            $this->vehicleCache = $model->getMaquina();
            return $this->vehicleCache;
        } catch (\Exception $e) {
            Tools::log()->error('Error al cargar vehículo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prepara los datos del vehículo en formato estructurado
     * Optimizado para evitar consultas innecesarias
     *
     * @param MaquinaAT $vehicle
     * @return array
     */
    private function prepareVehicleData(MaquinaAT $vehicle): array
    {
        $data = [];

        // Datos básicos (sin consultas adicionales)
        if (!empty($vehicle->nombre)) {
            $data[Tools::lang()->trans('name')] = [
                'title' => Tools::lang()->trans('name'),
                'value' => $vehicle->nombre,
            ];
        }

        if (!empty($vehicle->matricula)) {
            $data[Tools::lang()->trans('license-plate')] = [
                'title' => Tools::lang()->trans('license-plate'),
                'value' => strtoupper($vehicle->matricula),
            ];
        }

        if (!empty($vehicle->bastidor)) {
            $data[Tools::lang()->trans('vin')] = [
                'title' => Tools::lang()->trans('vin'),
                'value' => strtoupper($vehicle->bastidor),
            ];
        }

        // Fabricante (con caché optimizado en el modelo)
        if (!empty($vehicle->codfabricante)) {
            $fabricante = $vehicle->getFabricante();
            if (!empty($fabricante->nombre)) {
                $data[Tools::lang()->trans('manufacturer')] = [
                    'title' => Tools::lang()->trans('manufacturer'),
                    'value' => $fabricante->nombre,
                ];
            }
        }

        // Datos adicionales opcionales según configuración
        if (Tools::settings('servicios', 'print_vehicle_kilometers', true) && !empty($vehicle->kilometros)) {
            $data[Tools::lang()->trans('kilometers')] = [
                'title' => Tools::lang()->trans('kilometers'),
                'value' => number_format($vehicle->kilometros, 0, ',', '.') . ' km',
            ];
        }

        if (Tools::settings('servicios', 'print_vehicle_fuel', true) && !empty($vehicle->combustible)) {
            $data[Tools::lang()->trans('fuel-type')] = [
                'title' => Tools::lang()->trans('fuel-type'),
                'value' => $vehicle->combustible,
            ];
        }

        if (Tools::settings('servicios', 'print_vehicle_color', false) && !empty($vehicle->color)) {
            $data[Tools::lang()->trans('color')] = [
                'title' => Tools::lang()->trans('color'),
                'value' => $vehicle->color,
            ];
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
        // Agregar espacio antes de la sección
        $this->template->writeHTML('<br/>');

        // Agregar título de sección
        $sectionTitle = '<h3 style="background-color: #f5f5f5; padding: 8px; margin: 10px 0; border-left: 4px solid #007bff;">'
            . Tools::lang()->trans('vehicle-details')
            . '</h3>';
        $this->template->writeHTML($sectionTitle);

        // Agregar tabla con datos del vehículo usando el método del template
        $this->template->addDualColumnTable($vehicleData);
    }

    /**
     * Método alternativo para agregar como tabla simple (más compatible)
     *
     * @param array $vehicleData
     */
    private function addVehicleTable(array $vehicleData): void
    {
        $html = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="2" style="background-color: #f5f5f5; padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: bold;">';
        $html .= Tools::lang()->trans('vehicle-details');
        $html .= '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($vehicleData as $item) {
            $html .= '<tr>';
            $html .= '<td style="padding: 6px 8px; border: 1px solid #ddd; width: 40%; background-color: #fafafa; font-weight: 600;">';
            $html .= htmlspecialchars($item['title']);
            $html .= '</td>';
            $html .= '<td style="padding: 6px 8px; border: 1px solid #ddd; width: 60%;">';
            $html .= htmlspecialchars($item['value']);
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $this->template->writeHTML($html);
    }
}
