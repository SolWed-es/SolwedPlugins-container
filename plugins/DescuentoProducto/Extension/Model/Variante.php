<?php

namespace FacturaScripts\Plugins\DescuentoProducto\Extension\Model;

use Closure;

class Variante
{
    public function clear(): Closure
    {
        return function (): void {
            $this->descuento = 0.0;
        };
    }

    public function __get($name)
    {
        return function ($name) {
            if ($name === 'descuento') {
                return $this->attributes['descuento'] ?? 0.0;
            }
            return null;
        };
    }

    public function __set($name, $value): void
    {
        if ($name === 'descuento') {
            $this->attributes['descuento'] = (float) $value;
        }
    }

    public function save(): Closure
    {
        return function (): bool {
            $this->descuento = $this->descuento ?: 0.0;

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
            if ($this->descuento < 0 || $this->descuento > 100) {
                return false;
            }

            return true;
        };
    }

    public function getPrecioConDescuento(): Closure
    {
        return function (): float {
            if ($this->descuento <= 0) {
                return $this->precio;
            }

            return $this->precio * (100 - $this->descuento) / 100;
        };
    }
}
