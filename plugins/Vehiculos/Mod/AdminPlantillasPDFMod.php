<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Modificador dinámico para inyectar configuraciones de vehículos en AdminPlantillasPDF
 * Compatible con PHP 8.1+
 */

namespace FacturaScripts\Plugins\Vehiculos\Mod;

use FacturaScripts\Core\Base\ToolBox;

/**
 * Modificador que inyecta dinámicamente las configuraciones de vehículos
 * en el AdminPlantillasPDF independientemente del orden de carga
 */
class AdminPlantillasPDFMod
{
    /**
     * Inyecta JavaScript y HTML para añadir configuraciones de vehículos
     */
    public static function inject(): string
    {
        $i18n = new ToolBox();
        
        return '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Verificar si estamos en AdminPlantillasPDF
            if (!document.querySelector("form[action*=\"AdminPlantillasPDF\"]")) {
                return;
            }

            // Buscar el grupo de opciones
            const optionsGroup = document.querySelector(".card-body .row").closest(".card");
            if (!optionsGroup) {
                return;
            }
            
            // Crear el HTML para las nuevas opciones de vehículos
            const vehicleOptionsHTML = `
                <div class="col-sm-3">
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="showvehiclenexttocustomer" value="1" class="form-check-input" id="showvehiclenexttocustomer">
                            <label class="form-check-label" for="showvehiclenexttocustomer">
                                <i class="fa-solid fa-car fa-fw" aria-hidden="true"></i> ' . $i18n->i18n()->trans('show-vehicle-next-to-customer') . '
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="showvehiclecompact" value="1" class="form-check-input" id="showvehiclecompact">
                            <label class="form-check-label" for="showvehiclecompact">
                                <i class="fa-solid fa-compress fa-fw" aria-hidden="true"></i> ' . $i18n->i18n()->trans('show-vehicle-compact') . '
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="showvehiclespecifications" value="1" class="form-check-input" id="showvehiclespecifications">
                            <label class="form-check-label" for="showvehiclespecifications">
                                <i class="fa-solid fa-cogs fa-fw" aria-hidden="true"></i> ' . $i18n->i18n()->trans('show-vehicle-specifications') . '
                            </label>
                        </div>
                    </div>
                </div>
            `;
            
            // Buscar la última fila de opciones
            const lastOptionRow = optionsGroup.querySelector(".card-body .row:last-child");
            if (lastOptionRow) {
                // Crear una nueva fila para las opciones de vehículos
                const newRow = document.createElement("div");
                newRow.className = "row";
                newRow.innerHTML = vehicleOptionsHTML;
                
                // Insertar después de la última fila
                lastOptionRow.parentNode.insertBefore(newRow, lastOptionRow.nextSibling);
            }
            
            // Añadir un nuevo grupo completo para configuraciones avanzadas
            addVehicleAdvancedGroup();
        });
        
        function addVehicleAdvancedGroup() {
            // Buscar el contenedor principal donde añadir el nuevo grupo
            const mainContainer = document.querySelector(".tab-content .tab-pane.active .row");
            if (!mainContainer) {
                return;
            }
            
            const vehicleGroupHTML = `
                <div class="col-12">
                    <div class="card border-success shadow mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fa-solid fa-car fa-fw"></i> ' . $i18n->i18n()->trans('vehicle-pdf-settings') . '
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="vehiclemaxdisplay">' . $i18n->i18n()->trans('vehicle-max-display') . '</label>
                                        <input type="number" name="vehiclemaxdisplay" id="vehiclemaxdisplay" class="form-control" min="1" max="5" value="3">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="vehicletextsize">' . $i18n->i18n()->trans('vehicle-text-size') . '</label>
                                        <input type="number" name="vehicletextsize" id="vehicletextsize" class="form-control" min="8" max="16" value="12">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="vehicleposition">' . $i18n->i18n()->trans('vehicle-position') . '</label>
                                        <select name="vehicleposition" id="vehicleposition" class="form-control">
                                            <option value="separate-section">' . $i18n->i18n()->trans('separate-section') . '</option>
                                            <option value="below">' . $i18n->i18n()->trans('below') . '</option>
                                            <option value="right">' . $i18n->i18n()->trans('right') . '</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="showlicenseplate" value="1" class="form-check-input" id="showlicenseplate" checked>
                                        <label class="form-check-label" for="showlicenseplate">' . $i18n->i18n()->trans('show-license-plate') . '</label>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="showvin" value="1" class="form-check-input" id="showvin" checked>
                                        <label class="form-check-label" for="showvin">' . $i18n->i18n()->trans('show-vin') . '</label>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="showmanufacturer" value="1" class="form-check-input" id="showmanufacturer" checked>
                                        <label class="form-check-label" for="showmanufacturer">' . $i18n->i18n()->trans('show-manufacturer') . '</label>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="showcolor" value="1" class="form-check-input" id="showcolor" checked>
                                        <label class="form-check-label" for="showcolor">' . $i18n->i18n()->trans('show-color') . '</label>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="showfueltype" value="1" class="form-check-input" id="showfueltype" checked>
                                        <label class="form-check-label" for="showfueltype">' . $i18n->i18n()->trans('show-fuel-type') . '</label>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="showkilometers" value="1" class="form-check-input" id="showkilometers" checked>
                                        <label class="form-check-label" for="showkilometers">' . $i18n->i18n()->trans('show-kilometers') . '</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="vehiclebordercolor">' . $i18n->i18n()->trans('vehicle-border-color') . '</label>
                                        <input type="color" name="vehiclebordercolor" id="vehiclebordercolor" class="form-control" value="#333333">
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="vehiclebackgroundcolor">' . $i18n->i18n()->trans('vehicle-background-color') . '</label>
                                        <input type="color" name="vehiclebackgroundcolor" id="vehiclebackgroundcolor" class="form-control" value="#ffffff">
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="vehicleheadercolor">' . $i18n->i18n()->trans('vehicle-header-color') . '</label>
                                        <input type="color" name="vehicleheadercolor" id="vehicleheadercolor" class="form-control" value="#f8f9fa">
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="vehicletextcolor">' . $i18n->i18n()->trans('vehicle-text-color') . '</label>
                                        <input type="color" name="vehicletextcolor" id="vehicletextcolor" class="form-control" value="#000000">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Añadir el nuevo grupo al final
            const vehicleGroupContainer = document.createElement("div");
            vehicleGroupContainer.innerHTML = vehicleGroupHTML;
            mainContainer.appendChild(vehicleGroupContainer.firstElementChild);

            // Cargar valores existentes desde localStorage o configuración
            loadVehicleConfigValues();
        }
        
        function loadVehicleConfigValues() {
            // Cargar valores desde la configuración actual o localStorage
            const savedConfig = localStorage.getItem("vehiclePDFConfig");
            if (savedConfig) {
                const config = JSON.parse(savedConfig);
                Object.keys(config).forEach(key => {
                    const element = document.getElementById(key);
                    if (element) {
                        if (element.type === "checkbox") {
                            element.checked = config[key];
                        } else {
                            element.value = config[key];
                        }
                    }
                });
            }
            
            // Guardar cambios en localStorage
            document.querySelectorAll("input[name^=\"vehicle\"], input[name^=\"show\"]").forEach(input => {
                input.addEventListener("change", function() {
                    saveVehicleConfig();
                });
            });
        }
        
        function saveVehicleConfig() {
            const config = {};
            document.querySelectorAll("input[name^=\"vehicle\"], input[name^=\"show\"], select[name^=\"vehicle\"]").forEach(input => {
                if (input.type === "checkbox") {
                    config[input.name] = input.checked;
                } else {
                    config[input.name] = input.value;
                }
            });

            localStorage.setItem("vehiclePDFConfig", JSON.stringify(config));
        }
        </script>
        ';
    }
}
