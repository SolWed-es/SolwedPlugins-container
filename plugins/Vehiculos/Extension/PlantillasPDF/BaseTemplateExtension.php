<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Extension for PlantillasPDF BaseTemplate to inject vehicle information into PDFs
 * Uses the official ExtensionsTrait hook system
 *
 * Requires PlantillasPDF plugin to be enabled
 */

namespace FacturaScripts\Plugins\Vehiculos\Extension\PlantillasPDF;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;

/**
 * Extension for PlantillasPDF templates to add vehicle information
 * Uses the getObservations hook to display vehicle data as a separate section
 *
 * Format: "Vehículo: Matrícula: ABC1234 | Marca: SEAT | Modelo: LEON"
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class BaseTemplateExtension
{
    /**
     * Cache to prevent duplicate processing when getObservations is called multiple times
     * @var array
     */
    private static $processedModels = [];

    /**
     * Hook: getObservations
     * Prepends vehicle information to the document observations as a separate section
     *
     * This hook is called by BaseTemplate::getObservations() which:
     * 1. Calls pipe('getObservations', $model)
     * 2. If we return non-null, assigns $model->observaciones = our return value
     * 3. Then applies nl2br() and Shortcode::render()
     *
     * PROBLEM: getObservations() can be called MULTIPLE times for the same model
     * (e.g., Template1.php calls it twice on lines 467 and 469)
     *
     * SOLUTION: Use caching to process each model only once
     *
     * @return Closure
     */
    public function getObservations(): Closure
    {
        // Capturar referencia al cache FUERA del Closure (donde self funciona correctamente)
        $cache = &self::$processedModels;

        return function ($model) use (&$cache): ?string {
            // Create a unique key for this model instance
            $modelKey = spl_object_id($model);

            // If we've already processed this model, return null to keep the modified value
            if (isset($cache[$modelKey])) {
                return null;
            }

            // Mark this model as processed
            $cache[$modelKey] = true;

            // Verificar que el modelo tiene el campo idmaquina y no está vacío
            if (empty($model->idmaquina)) {
                return null; // No vehicle assigned, return null to keep original observations
            }

            // Cargar el vehículo desde la base de datos
            $vehiculo = new Vehiculo();
            if (false === $vehiculo->loadFromCode($model->idmaquina)) {
                return null; // Vehicle not found, return null
            }

            // Formatear la información del vehículo en formato horizontal
            $lang = Tools::lang();
            $vehicleParts = [];

            if (!empty($vehiculo->matricula)) {
                $vehicleParts[] = $lang->trans('license-plate') . ': ' . strtoupper($vehiculo->matricula);
            }

            $make = trim((string)$vehiculo->marca);
            $modelStr = trim((string)$vehiculo->modelo);
            if ($make !== '' || $modelStr !== '') {
                $vehicleParts[] = $lang->trans('brand') . ': ' . $make . ' ' . $lang->trans('model') . ': ' . $modelStr;
            }

            if (empty($vehicleParts)) {
                return null; // No vehicle info to display
            }

            // Formato horizontal: una sola línea con campos separados por pipes
            // Prepend "Vehículo:" label to make it a separate section heading
            $vehicleInfo = $lang->trans('vehiculo') . ': ' . implode(' | ', $vehicleParts);

            // Combinar con observaciones existentes del documento
            $observaciones = trim((string)($model->observaciones ?? ''));
            $result = ('' === $observaciones) ? $vehicleInfo : $vehicleInfo . "\n\n" . $observaciones;

            return $result;
        };
    }
}
