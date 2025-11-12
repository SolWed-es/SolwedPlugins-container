<?php

namespace FacturaScripts\Plugins\DescuentoProducto\Extension\Model;

use Closure;

class Variante
{
    public function save(): Closure
    {
        return function (): bool {
            // Asegurar que descuento tenga un valor válido antes de guardar
            if (!isset($this->descuento)) {
                $this->descuento = 0.0;
            }

            $this->descuento = (float) $this->descuento;

            // Validar que el descuento esté entre 0 y 100
            if ($this->descuento < 0 || $this->descuento > 100) {
                return false;
            }

            return true;
        };
    }

    public function test(): Closure
    {
        return function (): bool {
            // Validar descuento si existe
            if (isset($this->descuento)) {
                $descuento = (float) $this->descuento;
                if ($descuento < 0 || $descuento > 100) {
                    return false;
                }
            }

            return true;
        };
    }

    public function clear(): Closure
    {
        return function (): void {
            // Inicializar descuento en clear
            $this->descuento = 0.0;
        };
    }
}
