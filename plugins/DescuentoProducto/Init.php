<?php

namespace FacturaScripts\Plugins\DescuentoProducto;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Plugins\DescuentoProducto\Extension\Controller\EditProducto as EditProductoExtension;
use FacturaScripts\Plugins\DescuentoProducto\SalesLineModifier;
use FacturaScripts\Core\Migrations;

class Init extends InitClass
{
    public function init(): void
    {
        $this->ensureDatabaseSchema();
        $this->registerExtensions();
        $this->registerSalesLineModifier();
    }

    public function uninstall(): void
    {
        // Aquí podríamos eliminar la columna descuento si fuera necesario
    }

    public function update(): void
    {
        $this->ensureDatabaseSchema();
        $this->registerExtensions();
        $this->registerSalesLineModifier();
        $this->runMigrations();
    }

    private function registerExtensions(): void
    {
        $this->loadExtension(new EditProductoExtension());
        // Nota: Usamos un modelo personalizado en lugar de extensión
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

    /**
     * Asegura que la estructura de base de datos necesaria existe
     */
    private function ensureDatabaseSchema(): void
    {
        // Forzar la carga del modelo Variante para que se verifique la tabla
        $variante = new \FacturaScripts\Plugins\DescuentoProducto\Model\Variante();
        $variante->install();

        // Verificar y crear la columna descuento si no existe
        $this->ensureDescuentoColumn();
    }

    /**
     * Asegura que la columna descuento existe en la tabla variantes
     */
    private function ensureDescuentoColumn(): void
    {
        // Usar el método de base de datos de FacturaScript
        $db = new \FacturaScripts\Core\Base\DataBase();
        $db->connect();

        // Verificar si la tabla variantes existe
        if (!$db->tableExists('variantes')) {
            return;
        }

        // Verificar si la columna descuento ya existe
        $columns = $db->getColumns('variantes');
        $columnExists = false;

        foreach ($columns as $column) {
            if ($column['name'] === 'descuento') {
                $columnExists = true;
                break;
            }
        }

        // Si no existe, crearla
        if (!$columnExists) {
            $sql = "ALTER TABLE variantes ADD COLUMN descuento DECIMAL(5,2) DEFAULT 0.00 NOT NULL";
            if ($db->exec($sql)) {
                // Log de éxito
                error_log("DescuentoProducto: Columna 'descuento' creada exitosamente en tabla variantes");
            } else {
                // Log de error
                error_log("DescuentoProducto: Error al crear columna 'descuento' en tabla variantes");
            }
        }
    }
}
