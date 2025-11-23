<?php
namespace FacturaScripts\Plugins\Vehiculos\Model;

/**
 * @deprecated since version 1.1. Use Vehiculo instead. This class will be removed in version 2.0
 *
 * Alias de compatibilidad hacia atrás para Vehiculo
 * Toda la lógica está en Vehiculo
 *
 * IMPORTANTE: Esta clase se mantiene únicamente para compatibilidad hacia atrás.
 * Por favor, actualice su código para usar el modelo Vehiculo directamente:
 *
 * Antes: $maquina = new MaquinaAT();
 * Ahora:  $vehiculo = new Vehiculo();
 */
class MaquinaAT extends Vehiculo
{
    // Alias de compatibilidad - No agregar nueva funcionalidad aquí
}
