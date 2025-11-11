<?php

namespace FacturaScripts\Plugins\DescuentoGlobal\Extension\Controller;

use Closure;
use FacturaScripts\Dinamic\Lib\AssetManager;

class EditProducto
{
    public function createViews(): Closure
    {
        return function (): void {
            $view = $this->views['EditVariante'] ?? null;
            if ($view) {
                $column = $view->columnForName('margin');
                if ($column && isset($column->widget) && property_exists($column->widget, 'min')) {
                    $column->widget->min = '';
                }
            }

            AssetManager::addJs('Plugins/DescuentoGlobal/Assets/JS/descuentoglobal.js', 3);
        };
    }
}
