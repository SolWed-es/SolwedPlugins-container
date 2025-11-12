<?php

namespace FacturaScripts\Plugins\DescuentoProducto;

use FacturaScripts\Core\Contract\SalesLineModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Dinamic\Model\Variante;
use FacturaScripts\Core\Base\DataBase;

class SalesLineModifier implements SalesLineModInterface
{
    public function apply(SalesDocument &$model, array &$lines, array $formData): void
    {
        // Aplicar descuento a todas las líneas existentes
        foreach ($lines as &$line) {
            $this->applyDiscountToLine($line);
        }
    }

    public function applyToLine(array $formData, SalesDocumentLine &$line, string $id): void
    {
        // Aplicar descuento cuando se añade una nueva línea
        $this->applyDiscountToLine($line);
    }

    public function assets(): void
    {
        // No necesitamos assets adicionales para este modificador
    }

    public function getFastLine(SalesDocument $model, array $formData): ?SalesDocumentLine
    {
        // No necesitamos línea rápida especial
        return null;
    }

    public function map(array $lines, SalesDocument $model): array
    {
        // Aplicar descuento a las líneas mapeadas
        foreach ($lines as &$line) {
            $this->applyDiscountToLine($line);
        }
        return $lines;
    }

    public function newFields(): array
    {
        // No añadimos nuevos campos a las líneas
        return [];
    }

    public function newModalFields(): array
    {
        // No añadimos campos modales
        return [];
    }

    public function newTitles(): array
    {
        // No añadimos nuevos títulos
        return [];
    }

    public function renderField(string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $field): ?string
    {
        // No renderizamos campos adicionales
        return null;
    }

    public function renderTitle(SalesDocument $model, string $field): ?string
    {
        // No renderizamos títulos adicionales
        return null;
    }

    /**
     * Aplica el descuento configurado en el producto a la línea de venta
     */
    private function applyDiscountToLine(SalesDocumentLine &$line): void
    {
        if (empty($line->referencia)) {
            return;
        }

        // Obtener el descuento directamente desde la base de datos
        $descuento = $this->getDescuentoFromDatabase($line->referencia);

        // Si no hay descuento configurado, no hacer nada
        if ($descuento <= 0) {
            return;
        }

        // Aplicar el descuento en el campo dtopor (descuento porcentual)
        $line->dtopor = $descuento;

        // Recalcular el total de la línea con el descuento aplicado
        $line->pvptotal = $line->pvpunitario * $line->cantidad * (100 - $line->dtopor) / 100;
    }

    /**
     * Obtiene el descuento de una variante directamente desde la base de datos
     */
    private function getDescuentoFromDatabase(string $referencia): float
    {
        try {
            $db = new DataBase();
            $db->connect();

            $sql = "SELECT descuento FROM variantes WHERE referencia = " . $db->var2str($referencia);
            $result = $db->select($sql);

            if (!empty($result) && isset($result[0]['descuento'])) {
                return (float) $result[0]['descuento'];
            }
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el proceso
            error_log("DescuentoProducto: Error obteniendo descuento para {$referencia}: " . $e->getMessage());
        }

        return 0.0;
    }
}
