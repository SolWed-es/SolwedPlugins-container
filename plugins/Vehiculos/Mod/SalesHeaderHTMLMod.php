<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Vehiculos\Mod;

use FacturaScripts\Core\Contract\SalesModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;

/**
 * Mod to add vehicle selector to sales documents
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SalesHeaderHTMLMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData): void
    {
        // Aplicar el idmaquina desde el formulario
        if (isset($formData['idmaquina'])) {
            $model->idmaquina = empty($formData['idmaquina']) ? null : (int)$formData['idmaquina'];
        }
    }

    public function applyBefore(SalesDocument &$model, array $formData): void
    {
        // No se necesita procesar nada antes
    }

    public function assets(): void
    {
        // No se necesitan assets adicionales
    }

    public function newBtnFields(): array
    {
        return [];
    }

    public function newFields(): array
    {
        // Agregar el campo de vehículo en la primera fila del header
        return ['idmaquina'];
    }

    public function newModalFields(): array
    {
        return [];
    }

    public function renderField(SalesDocument $model, string $field): ?string
    {
        return match ($field) {
            'idmaquina' => $this->vehicleSelector($model),
            default => null,
        };
    }

    private function vehicleSelector(SalesDocument $model): string
    {
        // Si no hay cliente seleccionado, no mostrar el selector
        if (empty($model->codcliente)) {
            return '';
        }

        // Atributos del campo
        $attributes = $model->editable ? '' : 'disabled';

        // Cargar vehículos del cliente
        $vehicles = $this->getCustomerVehicles($model->codcliente);

        // Crear las opciones del selector
        $options = '<option value="">-- ' . Tools::lang()->trans('select') . ' --</option>';
        foreach ($vehicles as $vehicle) {
            $selected = ($model->idmaquina == $vehicle->idmaquina) ? 'selected' : '';
            $display = $this->getVehicleDisplay($vehicle);
            $options .= '<option value="' . $vehicle->idmaquina . '" ' . $selected . '>'
                      . htmlspecialchars($display, ENT_QUOTES, 'UTF-8')
                      . '</option>';
        }

        // URL para crear/editar vehículo
        $editUrl = 'EditVehiculo';
        if ($model->idmaquina) {
            $editUrl .= '?code=' . $model->idmaquina;
        }

        // Generar HTML del campo
        return '<div class="col-sm-6">'
            . '<div class="mb-3">'
            . '<a href="' . $editUrl . '" target="_blank">'
            . '<i class="fa-solid fa-car fa-fw"></i> ' . Tools::lang()->trans('vehiculo')
            . '</a>'
            . '<select name="idmaquina" class="form-select" ' . $attributes . '>'
            . $options
            . '</select>'
            . '</div>'
            . '</div>';
    }

    /**
     * Obtener vehículos del cliente
     */
    private function getCustomerVehicles(string $codcliente): array
    {
        if (empty($codcliente)) {
            return [];
        }

        try {
            $vehicleModel = new Vehiculo();
            $where = [new DataBaseWhere('codcliente', $codcliente)];
            return $vehicleModel->all($where, ['matricula' => 'ASC', 'marca' => 'ASC', 'modelo' => 'ASC'], 0, 0);
        } catch (\Throwable $e) {
            Tools::log()->warning('Error loading vehicles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener texto de visualización del vehículo
     */
    private function getVehicleDisplay(Vehiculo $vehicle): string
    {
        // Usar el método getDisplayInfo si existe
        if (method_exists($vehicle, 'getDisplayInfo')) {
            return $vehicle->getDisplayInfo();
        }

        // Construir manualmente
        $parts = [];

        if (!empty($vehicle->matricula)) {
            $parts[] = strtoupper($vehicle->matricula);
        }

        if (!empty($vehicle->marca)) {
            $parts[] = $vehicle->marca;
        }

        if (!empty($vehicle->modelo)) {
            $parts[] = $vehicle->modelo;
        }

        return !empty($parts) ? implode(' - ', $parts) : 'ID: ' . $vehicle->idmaquina;
    }
}
