<?php

namespace FacturaScripts\Plugins\DescuentoProducto\Extension\Controller;

use Closure;
use FacturaScripts\Dinamic\Lib\AssetManager;

class EditProducto
{
    public function createViews(): Closure
    {
        return function (): void {
            // Cargar el JavaScript para cálculos en tiempo real
            // El campo descuento ahora se añade vía XMLView/EditVariante.xml
            AssetManager::addJs('Plugins/DescuentoProducto/Assets/JS/descuentoproducto.js', 3);
        };
    }
}
