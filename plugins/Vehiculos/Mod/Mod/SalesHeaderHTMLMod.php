<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2022-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Vehiculos\Model\MaquinaAT;

/**
 * Sales header modification to add vehicle selector in banner
 * Optimized for PHP 8.1+ with performance improvements
 */
class SalesHeaderHTMLMod implements SalesModInterface
{
    private static array $vehicleCache = [];
    public function apply(SalesDocument &$model, array $formData, User $user): void
    {
        // Apply after saving - ensure idmaquina persistence
        if (isset($formData['idmaquina'])) {
            $idmaquina = empty($formData['idmaquina']) ? null : (int) $formData['idmaquina'];
            $model->idmaquina = $idmaquina;

            // Direct SQL save for guaranteed persistence
            if ($idmaquina !== null && $model->primaryColumnValue()) {
                $db = new DataBase();
                $tableName = $model->tableName();
                $primaryColumn = $model->primaryColumn();
                $primaryValue = $model->primaryColumnValue();

                $sql = "UPDATE {$tableName} SET idmaquina = {$idmaquina} WHERE {$primaryColumn} = " . $db->var2str($primaryValue);
                $db->exec($sql);

                // Add vehicle info line if configured
                if (Tools::settings('servicios', 'document_machine', true)) {
                    $this->addVehicleInfoLine($model);
                }
            }
        }
    }

    public function applyBefore(SalesDocument &$model, array $formData, User $user): void
    {
        // Assign idmaquina before saving
        if (isset($formData['idmaquina'])) {
            if (!property_exists($model, 'idmaquina')) {
                $model->idmaquina = null;
            }
            $model->idmaquina = empty($formData['idmaquina']) ? null : (int) $formData['idmaquina'];
        }
    }

    /**
     * Add vehicle information line to document
     */
    private function addVehicleInfoLine(SalesDocument &$model): void
    {
        // Check if vehicle info line already exists
        foreach ($model->getLines() as $line) {
            if (str_contains($line->descripcion, '[VEHICULO]')) {
                return; // Already exists, don't duplicate
            }
        }

        $maquina = new MaquinaAT();
        if (!$maquina->loadFromCode($model->idmaquina)) {
            return;
        }

        // Create informative line at the beginning
        $newLine = $model->getNewLine();
        $newLine->cantidad = 0;
        $newLine->pvpunitario = 0;
        $newLine->codimpuesto = null;
        $newLine->iva = 0;

        $i18n = new Translator();
        $newLine->descripcion = '[VEHICULO] ' . $i18n->trans('vehicle') . ': ' . $maquina->nombre;

        if (!empty($maquina->matricula)) {
            $newLine->descripcion .= ' - ' . $i18n->trans('license-plate') . ': ' . $maquina->matricula;
        }

        if (!empty($maquina->bastidor)) {
            $newLine->descripcion .= ' - VIN: ' . $maquina->bastidor;
        }

        if (!empty($maquina->kilometros)) {
            $newLine->descripcion .= ' - ' . $i18n->trans('kilometers') . ': ' . number_format($maquina->kilometros, 0, ',', '.');
        }

        $newLine->orden = 0; // Put at the beginning
        $newLine->save();

        // Reorder other lines
        $orden = 1;
        foreach ($model->getLines() as $line) {
            if ($line->idlinea != $newLine->idlinea) {
                $line->orden = $orden++;
                $line->save();
            }
        }
    }

    public function assets(): void
    {
        // No additional assets required
    }

    public function newBtnFields(): array
    {
        return [];
    }

    /**
     * Add vehicle field to header banner (top row)
     */
    public function newFields(): array
    {
        return ['vehiculo'];
    }

    public function newModalFields(): array
    {
        return [];
    }

    /**
     * Render vehicle selector field for banner
     */
    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        if ($field === 'vehiculo') {
            return $this->renderVehicleSelector($i18n, $model);
        }
        return null;
    }

    /**
     * Render optimized vehicle selector with caching and modern styling
     */
    private function renderVehicleSelector(Translator $i18n, SalesDocument $model): string
    {
        $codcliente = $model->codcliente ?? '';
        $idmaquina = property_exists($model, 'idmaquina') ? ($model->idmaquina ?? '') : '';

        // Use cache for performance
        $cacheKey = $codcliente;
        if (!isset(self::$vehicleCache[$cacheKey])) {
            self::$vehicleCache[$cacheKey] = $this->loadVehicleOptions($codcliente, $i18n);
        }

        $options = self::$vehicleCache[$cacheKey];
        
        $html = '<div class="col-sm-3">';
        $html .= '<div class="form-group">';
        $html .= '<label for="doc-vehicle-select" class="text-info font-weight-bold">';
        $html .= '<i class="fas fa-car"></i> ' . $i18n->trans('vehicle');
        $html .= '</label>';
        
        $disabled = empty($codcliente) ? ' disabled' : '';
        $html .= '<select name="idmaquina" id="doc-vehicle-select" class="form-control form-control-sm"' . $disabled . '>';
        
        foreach ($options as $value => $text) {
            $selected = ($value == $idmaquina) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($text) . '</option>';
        }
        
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';

        // Add optimized JavaScript for dynamic loading
        $html .= $this->renderVehicleScript($codcliente);

        return $html;
    }

    /**
     * Load vehicle options with caching
     */
    private function loadVehicleOptions(string $codcliente, Translator $i18n): array
    {
        $options = ['' => '-- ' . $i18n->trans('select') . ' --'];

        if (!empty($codcliente)) {
            $maquinaModel = new MaquinaAT();
            $where = [new DataBaseWhere('codcliente', $codcliente)];
            $orderBy = ['matricula' => 'ASC', 'nombre' => 'ASC'];

            foreach ($maquinaModel->all($where, $orderBy, 0, 50) as $maquina) { // Limit to 50 for performance
                $displayInfo = $maquina->nombre;
                if (!empty($maquina->matricula)) {
                    $displayInfo = '[' . strtoupper($maquina->matricula) . '] ' . $displayInfo;
                }
                $options[$maquina->idmaquina] = $displayInfo;
            }
        }

        return $options;
    }

    /**
     * Render optimized JavaScript for vehicle selector
     */
    private function renderVehicleScript(string $currentCustomer): string
    {
        return '<script>
document.addEventListener("DOMContentLoaded", function() {
    const vehicleSelect = document.getElementById("doc-vehicle-select");
    const customerSelect = document.querySelector("select[name=codcliente]");
    let originalCustomer = "' . addslashes($currentCustomer) . '";
    
    if (customerSelect && vehicleSelect) {
        customerSelect.addEventListener("change", function() {
            const newCustomer = this.value;
            if (newCustomer !== originalCustomer) {
                vehicleSelect.value = "";
                vehicleSelect.disabled = !newCustomer;
                
                // Trigger form recalculation to update vehicle list
                if (newCustomer && typeof salesFormAction === "function") {
                    salesFormAction("set-customer", "0");
                }
            }
        });
        
        // Enable/disable based on current customer
        vehicleSelect.disabled = !originalCustomer;
    }
});
</script>';
    }
}
