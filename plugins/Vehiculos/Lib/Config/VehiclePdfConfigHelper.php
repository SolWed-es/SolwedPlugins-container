<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Helper para configuración de vehículos en AdminPlantillasPDF
 * Compatible con PHP 8.1+
 */

namespace FacturaScripts\Plugins\Vehiculos\Lib\Config;

use FacturaScripts\Core\Tools;

/**
 * Clase helper para configuración de vehículos en PDFs
 * Refactorizada para evitar ejecución en scope global y mejorar seguridad
 */
final class VehiclePdfConfigHelper
{
    private const VEHICLE_FIELDS = [
        'showvehiclenexttocustomer',
        'vehicleposition',
        'vehiclemaxdisplay',
        'showvehiclecompact',
        'showvehiclespecifications',
        'vehicletextsize',
        'showlicenseplate',
        'showvin',
        'showmanufacturer',
        'showcolor',
        'showfueltype',
        'showkilometers',
        'vehiclebordercolor',
        'vehiclebackgroundcolor',
        'vehicleheadercolor',
        'vehicletextcolor'
    ];

    private const NUMERIC_FIELDS = ['vehiclemaxdisplay', 'vehicletextsize'];
    private const COLOR_FIELDS = ['vehiclebordercolor', 'vehiclebackgroundcolor', 'vehicleheadercolor', 'vehicletextcolor'];

    /**
     * Procesar configuraciones de vehículos con sanitización mejorada
     */
    public static function processConfig(array $postData): bool
    {
        if (empty($postData) || !isset($postData['action']) || $postData['action'] !== 'save') {
            return false;
        }

        $saved = false;
        foreach (self::VEHICLE_FIELDS as $field) {
            if (!isset($postData[$field])) {
                continue;
            }

            $value = self::sanitizeFieldValue($field, $postData[$field]);

            Tools::settings('plantillaspdf', $field, $value);
            $saved = true;
        }

        if ($saved) {
            Tools::settingsSave();
            Tools::log()->info('Vehicle PDF configurations saved successfully');
        }

        return $saved;
    }

    /**
     * Sanitizar valor según tipo de campo
     */
    private static function sanitizeFieldValue(string $field, mixed $value): mixed
    {
        // Campos numéricos
        if (in_array($field, self::NUMERIC_FIELDS, true)) {
            return max(1, (int) $value);
        }

        // Campos booleanos (todos los que empiezan con 'show')
        if (str_starts_with($field, 'show')) {
            return in_array($value, ['1', 'true', true, 1], true);
        }

        // Campos de color
        if (in_array($field, self::COLOR_FIELDS, true)) {
            $sanitized = trim((string) $value);
            // Validar formato hexadecimal
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $sanitized)) {
                return $sanitized;
            }
            return '#000000'; // Default si el formato es inválido
        }

        // Otros campos de texto
        return Tools::noHtml(trim((string) $value));
    }

    /**
     * Obtener configuración actual con valores por defecto
     */
    public static function getConfig(): array
    {
        return [
            'showvehiclenexttocustomer' => Tools::settings('plantillaspdf', 'showvehiclenexttocustomer', false),
            'vehicleposition' => Tools::settings('plantillaspdf', 'vehicleposition', 'separate-section'),
            'vehiclemaxdisplay' => (int) Tools::settings('plantillaspdf', 'vehiclemaxdisplay', 3),
            'showvehiclecompact' => Tools::settings('plantillaspdf', 'showvehiclecompact', false),
            'showvehiclespecifications' => Tools::settings('plantillaspdf', 'showvehiclespecifications', true),
            'vehicletextsize' => (int) Tools::settings('plantillaspdf', 'vehicletextsize', 12),
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

    /**
     * Generar JavaScript para la interfaz con sanitización mejorada
     */
    public static function generateScript(): string
    {
        $config = self::getConfig();
        // Escapar JSON correctamente para evitar XSS
        $configJson = json_encode($config, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return <<<SCRIPT
<script>
(function() {
    'use strict';

    document.addEventListener("DOMContentLoaded", function() {
        if (!window.location.href.includes("AdminPlantillasPDF")) {
            return;
        }

        const mainForm = document.querySelector("form");
        if (!mainForm) {
            return;
        }

        const vehicleSection = document.createElement("div");
        vehicleSection.className = "col-12 mt-4";
        vehicleSection.innerHTML = `
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-car fa-fw"></i> Configuración PDF Vehículos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <p class="text-muted">Configurar cómo aparece la información de vehículos en los PDFs</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showvehiclenexttocustomer" name="showvehiclenexttocustomer" value="1">
                                <label class="form-check-label" for="showvehiclenexttocustomer">Mostrar junto al cliente</label>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showvehiclecompact" name="showvehiclecompact" value="1">
                                <label class="form-check-label" for="showvehiclecompact">Mostrar compacto</label>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showvehiclespecifications" name="showvehiclespecifications" value="1">
                                <label class="form-check-label" for="showvehiclespecifications">Mostrar especificaciones</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <label for="vehiclemaxdisplay" class="form-label">Máximo vehículos</label>
                            <input type="number" class="form-control" id="vehiclemaxdisplay" name="vehiclemaxdisplay" min="1" max="10">
                        </div>
                        <div class="col-sm-3">
                            <label for="vehicletextsize" class="form-label">Tamaño texto</label>
                            <input type="number" class="form-control" id="vehicletextsize" name="vehicletextsize" min="8" max="16">
                        </div>
                        <div class="col-sm-3">
                            <label for="vehicleposition" class="form-label">Posición</label>
                            <select class="form-control" id="vehicleposition" name="vehicleposition">
                                <option value="separate-section">Sección separada</option>
                                <option value="after-customer">Después del cliente</option>
                                <option value="before-lines">Antes de líneas</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12"><h6 class="text-muted">Campos a mostrar:</h6></div>
                        <div class="col-sm-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showlicenseplate" name="showlicenseplate" value="1">
                                <label class="form-check-label" for="showlicenseplate">Matrícula</label>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showvin" name="showvin" value="1">
                                <label class="form-check-label" for="showvin">Bastidor</label>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showmanufacturer" name="showmanufacturer" value="1">
                                <label class="form-check-label" for="showmanufacturer">Fabricante</label>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showcolor" name="showcolor" value="1">
                                <label class="form-check-label" for="showcolor">Color</label>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showfueltype" name="showfueltype" value="1">
                                <label class="form-check-label" for="showfueltype">Combustible</label>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showkilometers" name="showkilometers" value="1">
                                <label class="form-check-label" for="showkilometers">Kilómetros</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12"><h6 class="text-muted">Colores:</h6></div>
                        <div class="col-sm-3">
                            <label for="vehiclebordercolor" class="form-label">Borde</label>
                            <input type="color" class="form-control form-control-color" id="vehiclebordercolor" name="vehiclebordercolor">
                        </div>
                        <div class="col-sm-3">
                            <label for="vehiclebackgroundcolor" class="form-label">Fondo</label>
                            <input type="color" class="form-control form-control-color" id="vehiclebackgroundcolor" name="vehiclebackgroundcolor">
                        </div>
                        <div class="col-sm-3">
                            <label for="vehicleheadercolor" class="form-label">Cabecera</label>
                            <input type="color" class="form-control form-control-color" id="vehicleheadercolor" name="vehicleheadercolor">
                        </div>
                        <div class="col-sm-3">
                            <label for="vehicletextcolor" class="form-label">Texto</label>
                            <input type="color" class="form-control form-control-color" id="vehicletextcolor" name="vehicletextcolor">
                        </div>
                    </div>
                </div>
            </div>
        `;

        const submitButtons = mainForm.querySelector(".text-right, .form-actions, button[type=submit], input[type=submit]");
        if (submitButtons && submitButtons.parentNode) {
            submitButtons.parentNode.insertBefore(vehicleSection, submitButtons);
        } else {
            mainForm.appendChild(vehicleSection);
        }

        // Cargar configuración actual
        const config = $configJson;
        Object.keys(config).forEach(function(key) {
            const element = document.getElementById(key);
            if (element) {
                if (element.type === "checkbox") {
                    element.checked = !!config[key];
                } else {
                    element.value = config[key];
                }
            }
        });
    });
})();
</script>
SCRIPT;
    }
}
