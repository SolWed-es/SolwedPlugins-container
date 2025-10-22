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
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;

/**
 * Utilidad para agregar información del vehículo a las exportaciones PDF
 * Compatible con PlantillasPDF plugin
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class VehicleDataPDFExport
{
    /**
     * Obtiene los datos del vehículo formateados para mostrar en PDF
     *
     * @param int|null $idmaquina
     * @return array
     */
    public static function getVehicleData(?int $idmaquina): array
    {
        if (empty($idmaquina)) {
            return [];
        }

    $maquina = new Vehiculo();
        if (!$maquina->loadFromCode($idmaquina)) {
            return [];
        }

        $data = [];

        // Datos básicos del vehículo
        if (!empty($maquina->marca)) {
            $data[Tools::lang()->trans('marca')] = [
                'title' => Tools::lang()->trans('marca'),
                'value' => $maquina->marca,
            ];
        }

        if (!empty($maquina->modelo)) {
            $data[Tools::lang()->trans('modelo')] = [
                'title' => Tools::lang()->trans('modelo'),
                'value' => $maquina->modelo,
            ];
        }

        if (!empty($maquina->matricula)) {
            $data[Tools::lang()->trans('license-plate')] = [
                'title' => Tools::lang()->trans('license-plate'),
                'value' => $maquina->matricula,
            ];
        }

        if (!empty($maquina->bastidor)) {
            $data[Tools::lang()->trans('bastidor')] = [
                'title' => Tools::lang()->trans('bastidor'),
                'value' => $maquina->bastidor,
            ];
        }

        if (!empty($maquina->numserie)) {
            $data[Tools::lang()->trans('numserie')] = [
                'title' => Tools::lang()->trans('numserie'),
                'value' => $maquina->numserie,
            ];
        }

        if (!empty($maquina->kilometros)) {
            $data[Tools::lang()->trans('kilometers')] = [
                'title' => Tools::lang()->trans('kilometers'),
                'value' => number_format($maquina->kilometros, 0, ',', '.') . ' km',
            ];
        }

        if (!empty($maquina->combustible)) {
            $data[Tools::lang()->trans('fuel-type')] = [
                'title' => Tools::lang()->trans('fuel-type'),
                'value' => $maquina->combustible,
            ];
        }

        if (!empty($maquina->color)) {
            $data[Tools::lang()->trans('color')] = [
                'title' => Tools::lang()->trans('color'),
                'value' => $maquina->color,
            ];
        }

        if (!empty($maquina->fecha_matriculacion)) {
            $data[Tools::lang()->trans('registration-date')] = [
                'title' => Tools::lang()->trans('registration-date'),
                'value' => date('d-m-Y', strtotime($maquina->fecha_matriculacion)),
            ];
        }

        return $data;
    }

    /**
     * Genera una tabla HTML con los datos del vehículo
     *
     * @param int|null $idmaquina
     * @return string
     */
    public static function getVehicleHtmlTable(?int $idmaquina): string
    {
        $data = self::getVehicleData($idmaquina);
        if (empty($data)) {
            return '';
        }

        $html = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
        $html .= '<thead><tr><th colspan="2" style="background-color: #f0f0f0; padding: 8px; text-align: left; border: 1px solid #ddd;">';
        $html .= '<strong>' . Tools::lang()->trans('vehicle-details') . '</strong>';
        $html .= '</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($data as $item) {
            $html .= '<tr>';
            $html .= '<td style="padding: 6px 8px; border: 1px solid #ddd; width: 40%; background-color: #f9f9f9;"><strong>' . $item['title'] . '</strong></td>';
            $html .= '<td style="padding: 6px 8px; border: 1px solid #ddd; width: 60%;">' . $item['value'] . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Obtiene un resumen breve del vehículo para líneas de documento
     *
     * @param int|null $idmaquina
     * @return string
     */
    public static function getVehicleSummary(?int $idmaquina): string
    {
        if (empty($idmaquina)) {
            return '';
        }

    $maquina = new Vehiculo();
        if (!$maquina->loadFromCode($idmaquina)) {
            return '';
        }

        $parts = [];

        if (!empty($maquina->marca)) {
            $parts[] = $maquina->marca;
        }

        if (!empty($maquina->modelo)) {
            $parts[] = $maquina->modelo;
        }

        if (!empty($maquina->matricula)) {
            $parts[] = Tools::lang()->trans('license-plate') . ': ' . $maquina->matricula;
        }

        if (!empty($maquina->bastidor)) {
            $parts[] = Tools::lang()->trans('bastidor') . ': ' . $maquina->bastidor;
        }

        return implode(' | ', $parts);
    }
}
