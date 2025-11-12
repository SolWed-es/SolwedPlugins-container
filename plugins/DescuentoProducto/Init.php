<?php

namespace FacturaScripts\Plugins\DescuentoProducto;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Plugins\DescuentoProducto\Extension\Controller\EditProducto as EditProductoExtension;
use FacturaScripts\Plugins\DescuentoProducto\Extension\Model\Variante as VarianteExtension;
use FacturaScripts\Plugins\DescuentoProducto\SalesLineModifier;
use FacturaScripts\Core\Migrations;

class Init extends InitClass
{
    public function init(): void
    {
        $this->registerExtensions();
        $this->registerSalesLineModifier();
    }

    public function uninstall(): void
    {
    }

    public function update(): void
    {
        $this->registerExtensions();
        $this->registerSalesLineModifier();
        $this->runMigrations();
    }

    private function registerExtensions(): void
    {
        $this->loadExtension(new EditProductoExtension());
        $this->loadExtension(new VarianteExtension());
    }

    private function registerSalesLineModifier(): void
    {
        // Registrar el modificador de líneas de venta
        $modifier = new SalesLineModifier();
        \FacturaScripts\Dinamic\Lib\AjaxForms\SalesLineHTML::addMod($modifier);
    }

    private function runMigrations(): void
    {
        $migration = new Migrations\AddDescuentoColumn();
        Migrations::runPluginMigration($migration);
    }
}
