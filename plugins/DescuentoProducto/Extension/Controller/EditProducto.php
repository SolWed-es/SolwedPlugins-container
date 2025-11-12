<?php

namespace FacturaScripts\Plugins\DescuentoProducto\Extension\Controller;

use Closure;
use FacturaScripts\Dinamic\Lib\AssetManager;

class EditProducto
{
    public function createViews(): Closure
    {
        return function (): void {
            $view = $this->views['EditVariante'] ?? null;
            if ($view) {
                // Añadir el campo descuento a la vista de edición de variante
                $column = $view->columnForName('precio');
                if ($column) {
                    // Crear nueva columna para descuento después del precio
                    $descuentoColumn = clone $column;
                    $descuentoColumn->name = 'descuento';
                    $descuentoColumn->title = 'Descuento (%)';
                    $descuentoColumn->display = 'right';
                    $descuentoColumn->orderBy = false;

                    // Configurar el widget
                    if (isset($descuentoColumn->widget)) {
                        $descuentoColumn->widget->fieldName = 'descuento';
                        $descuentoColumn->widget->type = 'number';
                        $descuentoColumn->widget->decimal = 2;
                        $descuentoColumn->widget->min = 0;
                        $descuentoColumn->widget->max = 100;
                        $descuentoColumn->widget->step = 0.01;
                        $descuentoColumn->widget->required = false;
                    }

                    // Insertar la columna después del precio
                    $columns = $view->getColumns();
                    $precioIndex = -1;
                    foreach ($columns as $index => $col) {
                        if ($col->name === 'precio') {
                            $precioIndex = $index;
                            break;
                        }
                    }

                    if ($precioIndex >= 0) {
                        array_splice($columns, $precioIndex + 1, 0, [$descuentoColumn]);
                        $view->setColumns($columns);
                    }
                }
            }

            // Cargar el JavaScript para cálculos en tiempo real
            AssetManager::addJs('Plugins/DescuentoProducto/Assets/JS/descuentoproducto.js', 3);
        };
    }
}
