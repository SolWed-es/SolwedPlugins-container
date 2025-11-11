<?php

namespace FacturaScripts\Plugins\DescuentoGlobal\Extension\Model;

use Closure;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;

class Variante
{
    public function saveBefore(): Closure
    {
        return function (): void {
            $margin = (float) $this->margen;
            if ($margin >= 0) {
                return;
            }

            $cost = (float) $this->coste;
            $newPrice = $cost * (100 + $margin) / 100;
            $this->precio = round($newPrice, DinProducto::ROUND_DECIMALS);
        };
    }
}
