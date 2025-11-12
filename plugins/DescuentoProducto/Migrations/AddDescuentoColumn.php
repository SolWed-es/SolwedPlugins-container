<?php

namespace FacturaScripts\Plugins\DescuentoProducto\Migrations;

use FacturaScripts\Core\Template\MigrationClass;

class AddDescuentoColumn extends MigrationClass
{
    const MIGRATION_NAME = 'add_descuento_column_v1.0.0';

    public function run(): void
    {
        if (!$this->db()->tableExists('variantes')) {
            return;
        }

        // Verificar si la columna ya existe
        $columns = $this->db()->getColumns('variantes');
        if (in_array('descuento', array_column($columns, 'name'))) {
            return; // La columna ya existe
        }

        // Añadir la columna descuento
        $sql = "ALTER TABLE variantes ADD COLUMN descuento DECIMAL(5,2) DEFAULT 0.00 NOT NULL";
        $this->db()->exec($sql);
    }
}
