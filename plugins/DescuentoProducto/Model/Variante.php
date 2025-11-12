<?php

namespace FacturaScripts\Plugins\DescuentoProducto\Model;

use FacturaScripts\Core\Model\Variante as BaseVariante;

/**
 * Variante con soporte para descuento
 */
class Variante extends BaseVariante
{
    /**
     * Descuento porcentual (0-100)
     *
     * @var float
     */
    public $descuento;

    public function clear(): void
    {
        parent::clear();
        $this->descuento = 0.0;
    }

    public function save(): bool
    {
        // Debug: Registrar el intento de guardado
        error_log("DescuentoProducto: Intentando guardar variante {$this->referencia} con descuento {$this->descuento}");

        // Asegurar que descuento tenga un valor válido
        $this->descuento = $this->descuento ?: 0.0;
        $this->descuento = (float) $this->descuento;

        // Validar que el descuento esté entre 0 y 100
        if ($this->descuento < 0 || $this->descuento > 100) {
            error_log("DescuentoProducto: Descuento inválido: {$this->descuento}");
            return false;
        }

        $result = parent::save();

        if ($result) {
            error_log("DescuentoProducto: Variante guardada exitosamente con descuento {$this->descuento}");
        } else {
            error_log("DescuentoProducto: Error al guardar variante con descuento {$this->descuento}");
        }

        return $result;
    }

    public function test(): bool
    {
        // Validar descuento
        if ($this->descuento < 0 || $this->descuento > 100) {
            return false;
        }

        return parent::test();
    }

    /**
     * Devuelve el precio con descuento aplicado
     */
    public function getPrecioConDescuento(): float
    {
        if ($this->descuento <= 0) {
            return $this->precio;
        }

        return $this->precio * (100 - $this->descuento) / 100;
    }
}
