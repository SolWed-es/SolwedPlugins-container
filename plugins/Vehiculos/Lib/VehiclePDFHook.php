<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Simple hook para inyectar configuraciones de vehículos en AdminPlantillasPDF
 * Compatible con PHP 8.1+
 */

namespace FacturaScripts\Plugins\Vehiculos\Lib;

use FacturaScripts\Core\Tools;

/**
 * Hook simple que se ejecuta después de cargar AdminPlantillasPDF
 * para añadir configuraciones de vehículos
 */
class VehiclePDFHook
{
    /**
     * Inyecta HTML adicional en AdminPlantillasPDF
     */
    public static function injectInAdminPlantillasPDF(): string
    {
        // Solo ejecutar si estamos en AdminPlantillasPDF
        if (!self::isAdminPlantillasPDFPage()) {
            return '';
        }

        $i18n = Tools::lang();
        
        // Cargar valores actuales de configuración
        $vehicleConfig = self::loadVehicleConfig();
        
        $html = '
        <!-- Configuraciones de Vehículos inyectadas por Servicios Plugin -->
        <div class="col-12 mt-4">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-car fa-fw"></i> ' . $i18n->trans('vehicle-pdf-settings') . '
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <p class="text-muted">' . $i18n->trans('vehicle-position-settings-desc') . '</p>
                        </div>
                    </div>
                    
                    <!-- Opciones principales -->
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input type="checkbox" name="showvehiclenexttocustomer" value="1" class="form-check-input" id="showvehiclenexttocustomer"' . 
                                ($vehicleConfig['showvehiclenexttocustomer'] ? ' checked' : '') . '>
                                <label class="form-check-label" for="showvehiclenexttocustomer">
                                    <i class="fa-solid fa-car fa-fw text-success"></i> ' . $i18n->trans('show-vehicle-next-to-customer') . '
                                </label>
                                <small class="form-text text-muted">' . $i18n->trans('show-vehicle-next-to-customer-desc') . '</small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input type="checkbox" name="showvehiclecompact" value="1" class="form-check-input" id="showvehiclecompact"' . 
                                ($vehicleConfig['showvehiclecompact'] ? ' checked' : '') . '>
                                <label class="form-check-label" for="showvehiclecompact">
                                    <i class="fa-solid fa-compress fa-fw text-info"></i> ' . $i18n->trans('show-vehicle-compact') . '
                                </label>
                                <small class="form-text text-muted">' . $i18n->trans('show-vehicle-compact-desc') . '</small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input type="checkbox" name="showvehiclespecifications" value="1" class="form-check-input" id="showvehiclespecifications"' . 
                                ($vehicleConfig['showvehiclespecifications'] ? ' checked' : '') . '>
                                <label class="form-check-label" for="showvehiclespecifications">
                                    <i class="fa-solid fa-cogs fa-fw text-warning"></i> ' . $i18n->trans('show-vehicle-specifications') . '
                                </label>
                                <small class="form-text text-muted">' . $i18n->trans('show-vehicle-specifications-desc') . '</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <!-- Configuraciones detalladas -->
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="vehiclemaxdisplay">' . $i18n->trans('vehicle-max-display') . '</label>
                                <input type="number" name="vehiclemaxdisplay" id="vehiclemaxdisplay" class="form-control" 
                                       min="1" max="5" value="' . $vehicleConfig['vehiclemaxdisplay'] . '">
                                <small class="form-text text-muted">' . $i18n->trans('vehicle-max-display-desc') . '</small>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="vehicletextsize">' . $i18n->trans('vehicle-text-size') . '</label>
                                <input type="number" name="vehicletextsize" id="vehicletextsize" class="form-control" 
                                       min="8" max="16" value="' . $vehicleConfig['vehicletextsize'] . '">
                                <small class="form-text text-muted">Tamaño en píxeles (8-16px)</small>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="vehicleposition">' . $i18n->trans('vehicle-position') . '</label>
                                <select name="vehicleposition" id="vehicleposition" class="form-control">
                                    <option value="separate-section"' . ($vehicleConfig['vehicleposition'] === 'separate-section' ? ' selected' : '') . '>' . $i18n->trans('separate-section') . '</option>
                                    <option value="below"' . ($vehicleConfig['vehicleposition'] === 'below' ? ' selected' : '') . '>' . $i18n->trans('below') . '</option>
                                    <option value="right"' . ($vehicleConfig['vehicleposition'] === 'right' ? ' selected' : '') . '>' . $i18n->trans('right') . '</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campos a mostrar -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">' . $i18n->trans('vehicle-fields-settings') . '</h6>
                        </div>
                        ' . self::generateFieldCheckboxes($vehicleConfig, $i18n) . '
                    </div>
                    
                    <hr class="my-3">
                    
                    <!-- Configuración de colores -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">' . $i18n->trans('vehicle-styling-settings') . '</h6>
                        </div>
                        ' . self::generateColorInputs($vehicleConfig, $i18n) . '
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Auto-guardar configuraciones cuando cambien
        document.addEventListener("DOMContentLoaded", function() {
            // Añadir listeners para auto-guardar
            const vehicleInputs = document.querySelectorAll("input[name^=\"vehicle\"], input[name^=\"show\"], select[name^=\"vehicle\"]");
            vehicleInputs.forEach(input => {
                input.addEventListener("change", function() {
                    // Marcar el formulario como modificado
                    const form = document.querySelector("form");
                    if (form) {
                        const hiddenModified = document.createElement("input");
                        hiddenModified.type = "hidden";
                        hiddenModified.name = "vehicle_settings_modified";
                        hiddenModified.value = "1";
                        form.appendChild(hiddenModified);
                    }
                });
            });
        });
        </script>
        ';
        
        return $html;
    }

    /**
     * Genera checkboxes para campos de vehículos
     */
    private static function generateFieldCheckboxes(array $config, $i18n): string
    {
        $fields = [
            'showlicenseplate' => 'show-license-plate',
            'showvin' => 'show-vin', 
            'showmanufacturer' => 'show-manufacturer',
            'showcolor' => 'show-color',
            'showfueltype' => 'show-fuel-type',
            'showkilometers' => 'show-kilometers'
        ];

        $html = '';
        foreach ($fields as $fieldName => $label) {
            $checked = $config[$fieldName] ? ' checked' : '';
            $html .= '
                <div class="col-sm-2">
                    <div class="form-check">
                        <input type="checkbox" name="' . $fieldName . '" value="1" class="form-check-input" id="' . $fieldName . '"' . $checked . '>
                        <label class="form-check-label" for="' . $fieldName . '">' . $i18n->trans($label) . '</label>
                    </div>
                </div>
            ';
        }

        return $html;
    }

    /**
     * Genera inputs de color
     */
    private static function generateColorInputs(array $config, $i18n): string
    {
        $colors = [
            'vehiclebordercolor' => 'vehicle-border-color',
            'vehiclebackgroundcolor' => 'vehicle-background-color',
            'vehicleheadercolor' => 'vehicle-header-color',
            'vehicletextcolor' => 'vehicle-text-color'
        ];

        $html = '';
        foreach ($colors as $fieldName => $label) {
            $value = $config[$fieldName];
            $html .= '
                <div class="col-sm-3">
                    <div class="form-group">
                        <label for="' . $fieldName . '">' . $i18n->trans($label) . '</label>
                        <input type="color" name="' . $fieldName . '" id="' . $fieldName . '" class="form-control" value="' . $value . '">
                    </div>
                </div>
            ';
        }

        return $html;
    }

    /**
     * Verifica si estamos en la página AdminPlantillasPDF
     */
    private static function isAdminPlantillasPDFPage(): bool
    {
        return isset($_GET['page']) && $_GET['page'] === 'AdminPlantillasPDF';
    }

    /**
     * Carga la configuración actual de vehículos
     */
    private static function loadVehicleConfig(): array
    {
        return [
            'showvehiclenexttocustomer' => Tools::settings('plantillaspdf', 'showvehiclenexttocustomer', false),
            'vehicleposition' => Tools::settings('plantillaspdf', 'vehicleposition', 'separate-section'),
            'vehiclemaxdisplay' => Tools::settings('plantillaspdf', 'vehiclemaxdisplay', 3),
            'showvehiclecompact' => Tools::settings('plantillaspdf', 'showvehiclecompact', false),
            'showvehiclespecifications' => Tools::settings('plantillaspdf', 'showvehiclespecifications', true),
            'vehicletextsize' => Tools::settings('plantillaspdf', 'vehicletextsize', 12),
            'showlicenseplate' => Tools::settings('plantillaspdf', 'showlicenseplate', true),
            'showvin' => Tools::settings('plantillaspdf', 'showvin', true),
            'showmanufacturer' => Tools::settings('plantillaspdf', 'showmanufacturer', true),
            'showcolor' => Tools::settings('plantillaspdf', 'showcolor', true),
            'showfueltype' => Tools::settings('plantillaspdf', 'showfueltype', true),
            'showkilometers' => Tools::settings('plantillaspdf', 'showkilometers', true),
            'vehiclebordercolor' => Tools::settings('plantillaspdf', 'vehiclebordercolor', '#333333'),
            'vehiclebackgroundcolor' => Tools::settings('plantillaspdf', 'vehiclebackgroundcolor', '#ffffff'),
            'vehicleheadercolor' => Tools::settings('plantillaspdf', 'vehicleheadercolor', '#f8f9fa'),
            'vehicletextcolor' => Tools::settings('plantillaspdf', 'vehicletextcolor', '#000000')
        ];
    }
}
