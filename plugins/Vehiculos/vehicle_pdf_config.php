<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Archivo independiente para manejar configuraciones de vehículos en AdminPlantillasPDF
 * Compatible con PHP 8.1+
 */

// Solo ejecutar si estamos en AdminPlantillasPDF
if (!defined('FS_FOLDER') || !isset($_GET['page']) || $_GET['page'] !== 'AdminPlantillasPDF') {
    return;
}

use FacturaScripts\Core\Tools;

/**
 * Procesar configuraciones de vehículos si se envía el formulario
 */
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'save') {
    $vehicleFields = [
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

    $saved = false;
    foreach ($vehicleFields as $field) {
        if (isset($_POST[$field])) {
            $value = $_POST[$field];
            
            // Validación básica según el tipo de campo
            if (in_array($field, ['vehiclemaxdisplay', 'vehicletextsize'])) {
                $value = (int) $value;
            } elseif (str_starts_with($field, 'show')) {
                $value = $value === '1' || $value === 'true' || $value === true;
            } else {
                $value = (string) $value;
            }
            
            Tools::settings('plantillaspdf', $field, $value);
            $saved = true;
        }
    }
    
    if ($saved) {
        Tools::settingsSave();
        Tools::log()->info('Vehicle PDF configurations saved successfully');
    }
}

/**
 * Cargar configuraciones actuales
 */
$vehicleConfig = [
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

/**
 * Script para inyectar configuraciones de vehículos
 */
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Verificar si estamos en AdminPlantillasPDF
    if (!window.location.href.includes("AdminPlantillasPDF")) {
        return;
    }
    
    console.log("Servicios Plugin: Añadiendo configuraciones de vehículos");
    
    // Buscar el formulario principal
    const mainForm = document.querySelector("form");
    if (!mainForm) {
        console.log("No se encontró el formulario principal");
        return;
    }
    
    // Crear la sección de configuraciones de vehículos
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
                
                <!-- Opciones principales -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <div class="form-check">
                            <input type="checkbox" name="showvehiclenexttocustomer" value="1" class="form-check-input" id="showvehiclenexttocustomer">
                            <label class="form-check-label" for="showvehiclenexttocustomer">
                                <i class="fa-solid fa-car fa-fw text-success"></i> Mostrar vehículo junto al cliente
                            </label>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-check">
                            <input type="checkbox" name="showvehiclecompact" value="1" class="form-check-input" id="showvehiclecompact">
                            <label class="form-check-label" for="showvehiclecompact">
                                <i class="fa-solid fa-compress fa-fw text-info"></i> Formato compacto
                            </label>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-check">
                            <input type="checkbox" name="showvehiclespecifications" value="1" class="form-check-input" id="showvehiclespecifications">
                            <label class="form-check-label" for="showvehiclespecifications">
                                <i class="fa-solid fa-cogs fa-fw text-warning"></i> Mostrar especificaciones
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Configuraciones detalladas -->
                <div class="row mb-3">
                    <div class="col-sm-3">
                        <label for="vehiclemaxdisplay">Máximo vehículos a mostrar</label>
                        <input type="number" name="vehiclemaxdisplay" id="vehiclemaxdisplay" class="form-control" min="1" max="5" value="3">
                    </div>
                    <div class="col-sm-3">
                        <label for="vehicletextsize">Tamaño del texto</label>
                        <input type="number" name="vehicletextsize" id="vehicletextsize" class="form-control" min="8" max="16" value="12">
                    </div>
                    <div class="col-sm-3">
                        <label for="vehicleposition">Posición del vehículo</label>
                        <select name="vehicleposition" id="vehicleposition" class="form-control">
                            <option value="separate-section">Sección separada</option>
                            <option value="below">Debajo</option>
                            <option value="right">A la derecha</option>
                        </select>
                    </div>
                </div>
                
                <!-- Campos de vehículo -->
                <div class="row mb-3">
                    <div class="col-12"><h6 class="text-muted">Campos a mostrar:</h6></div>
                    <div class="col-sm-2">
                        <div class="form-check">
                            <input type="checkbox" name="showlicenseplate" value="1" class="form-check-input" id="showlicenseplate" checked>
                            <label class="form-check-label" for="showlicenseplate">Matrícula</label>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-check">
                            <input type="checkbox" name="showvin" value="1" class="form-check-input" id="showvin" checked>
                            <label class="form-check-label" for="showvin">VIN/Bastidor</label>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-check">
                            <input type="checkbox" name="showmanufacturer" value="1" class="form-check-input" id="showmanufacturer" checked>
                            <label class="form-check-label" for="showmanufacturer">Fabricante</label>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-check">
                            <input type="checkbox" name="showcolor" value="1" class="form-check-input" id="showcolor" checked>
                            <label class="form-check-label" for="showcolor">Color</label>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-check">
                            <input type="checkbox" name="showfueltype" value="1" class="form-check-input" id="showfueltype" checked>
                            <label class="form-check-label" for="showfueltype">Combustible</label>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-check">
                            <input type="checkbox" name="showkilometers" value="1" class="form-check-input" id="showkilometers" checked>
                            <label class="form-check-label" for="showkilometers">Kilómetros</label>
                        </div>
                    </div>
                </div>
                
                <!-- Colores -->
                <div class="row">
                    <div class="col-12"><h6 class="text-muted">Colores:</h6></div>
                    <div class="col-sm-3">
                        <label for="vehiclebordercolor">Color del borde</label>
                        <input type="color" name="vehiclebordercolor" id="vehiclebordercolor" class="form-control" value="#333333">
                    </div>
                    <div class="col-sm-3">
                        <label for="vehiclebackgroundcolor">Color de fondo</label>
                        <input type="color" name="vehiclebackgroundcolor" id="vehiclebackgroundcolor" class="form-control" value="#ffffff">
                    </div>
                    <div class="col-sm-3">
                        <label for="vehicleheadercolor">Color del encabezado</label>
                        <input type="color" name="vehicleheadercolor" id="vehicleheadercolor" class="form-control" value="#f8f9fa">
                    </div>
                    <div class="col-sm-3">
                        <label for="vehicletextcolor">Color del texto</label>
                        <input type="color" name="vehicletextcolor" id="vehicletextcolor" class="form-control" value="#000000">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Insertar antes de los botones de submit
    const submitButtons = mainForm.querySelector(".text-right, .form-actions, button[type=submit], input[type=submit]");
    if (submitButtons) {
        submitButtons.parentNode.insertBefore(vehicleSection, submitButtons);
    } else {
        mainForm.appendChild(vehicleSection);
    }
    
    // Cargar valores actuales
    const config = <?php echo json_encode($vehicleConfig); ?>;
    Object.keys(config).forEach(function(key) {
        const element = document.getElementById(key);
        if (element) {
            if (element.type === "checkbox") {
                element.checked = config[key];
            } else {
                element.value = config[key];
            }
        }
    });
    
    console.log("Configuraciones de vehículos añadidas correctamente");
});
</script>