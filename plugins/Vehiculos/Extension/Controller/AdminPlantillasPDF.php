<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Extensión del controlador AdminPlantillasPDF para añadir configuración de vehículos
 * Compatible con PHP 8.1+
 * REFACTORIZADO: Usa VehiclePdfConfigHelper para mejor separación de responsabilidades
 */

namespace FacturaScripts\Plugins\Vehiculos\Extension\Controller;

use Closure;
use FacturaScripts\Plugins\Vehiculos\Lib\Config\VehiclePdfConfigHelper;

/**
 * Extensión del controlador AdminPlantillasPDF
 * Añade configuración para mostrar información de vehículos en PDFs
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class AdminPlantillasPDF
{
    /**
     * Hook para procesar configuraciones antes de ejecutar la acción
     */
    public function execPreviousAction(): Closure
    {
        return function(string $action) {
            // Procesar configuración de vehículos si es guardado
            if ($action === 'save' && !empty($_POST)) {
                VehiclePdfConfigHelper::processConfig($_POST);
            }
        };
    }

    /**
     * Añadir JavaScript para la interfaz de configuración
     */
    public function addAssets(): Closure
    {
        return function() {
            echo VehiclePdfConfigHelper::generateScript();
        };
    }
}