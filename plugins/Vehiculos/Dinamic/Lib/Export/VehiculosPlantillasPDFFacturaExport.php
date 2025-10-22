<?php
/**
 * Dinamic wrapper para la clase PlantillasPDFFacturaExport del plugin Vehículos
 * Permite que FacturaScripts resuelva dinámicamente la clase mediante Dinamic namespace
 */

namespace FacturaScripts\Dinamic\Lib\Export;

use FacturaScripts\Plugins\Vehiculos\Lib\Export\PlantillasPDFFacturaExport;

class VehiculosPlantillasPDFFacturaExport extends PlantillasPDFFacturaExport
{
    // Esta clase simplemente extiende la original del plugin
    // Permite que sea resuelta dinámicamente por FacturaScripts
}
