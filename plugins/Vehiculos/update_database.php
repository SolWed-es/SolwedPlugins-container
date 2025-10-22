<?php
/**
 * Script para actualizar la base de datos y añadir columnas idmaquina
 * Ejecutar: php Plugins/Servicios/update_database.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\PluginManager;

// Inicializar entorno
define('FS_FOLDER', __DIR__ . '/../..');
define('FS_ROUTE', '/');

// Cargar configuración
if (!file_exists(FS_FOLDER . '/config.php')) {
    die("Error: No se encuentra config.php. Ejecuta FacturaScripts primero.\n");
}

require_once FS_FOLDER . '/config.php';

echo "=== Actualizando Base de Datos del Plugin Servicios ===\n\n";

$db = new DataBase();
if (!$db->connect()) {
    die("Error: No se pudo conectar a la base de datos\n");
}

echo "✓ Conectado a la base de datos\n\n";

// Determinar tipo de base de datos
$engine = strtolower(FS_DB_TYPE);

echo "Tipo de BD: " . $engine . "\n\n";

$queries = [];

if ($engine === 'postgresql') {
    // PostgreSQL
    $queries = [
        "ALTER TABLE facturascli ADD COLUMN IF NOT EXISTS idmaquina INTEGER",
        "ALTER TABLE albaranescli ADD COLUMN IF NOT EXISTS idmaquina INTEGER",
        "ALTER TABLE presupuestoscli ADD COLUMN IF NOT EXISTS idmaquina INTEGER",

        "DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ca_facturascli_maquinas') THEN
                ALTER TABLE facturascli ADD CONSTRAINT ca_facturascli_maquinas
                FOREIGN KEY (idmaquina) REFERENCES serviciosat_maquinas(idmaquina)
                ON DELETE SET NULL ON UPDATE CASCADE;
            END IF;
        END $$",

        "DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ca_albaranescli_maquinas') THEN
                ALTER TABLE albaranescli ADD CONSTRAINT ca_albaranescli_maquinas
                FOREIGN KEY (idmaquina) REFERENCES serviciosat_maquinas(idmaquina)
                ON DELETE SET NULL ON UPDATE CASCADE;
            END IF;
        END $$",

        "DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ca_presupuestoscli_maquinas') THEN
                ALTER TABLE presupuestoscli ADD CONSTRAINT ca_presupuestoscli_maquinas
                FOREIGN KEY (idmaquina) REFERENCES serviciosat_maquinas(idmaquina)
                ON DELETE SET NULL ON UPDATE CASCADE;
            END IF;
        END $$"
    ];
} else {
    // MySQL/MariaDB
    $queries = [
        "ALTER TABLE facturascli ADD COLUMN IF NOT EXISTS idmaquina INT",
        "ALTER TABLE albaranescli ADD COLUMN IF NOT EXISTS idmaquina INT",
        "ALTER TABLE presupuestoscli ADD COLUMN IF NOT EXISTS idmaquina INT",
    ];

    // Añadir constraints si no existen (MySQL/MariaDB)
    foreach (['facturascli', 'albaranescli', 'presupuestoscli'] as $table) {
        $constraintName = 'ca_' . $table . '_maquinas';
        $queries[] = "ALTER TABLE {$table} ADD CONSTRAINT {$constraintName}
                      FOREIGN KEY (idmaquina) REFERENCES serviciosat_maquinas(idmaquina)
                      ON DELETE SET NULL ON UPDATE CASCADE";
    }
}

// Ejecutar queries
$errors = 0;
foreach ($queries as $i => $query) {
    echo "Ejecutando query " . ($i + 1) . "... ";

    try {
        if ($db->exec($query)) {
            echo "✓ OK\n";
        } else {
            // Puede fallar si ya existe, no es grave
            echo "⚠ Ya existe o error menor\n";
        }
    } catch (Exception $e) {
        // Si es un error de "ya existe", no es problema
        if (strpos($e->getMessage(), 'already exists') !== false ||
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "⚠ Ya existe\n";
        } else {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

echo "\n=== Resumen ===\n";
if ($errors === 0) {
    echo "✓ Base de datos actualizada correctamente\n";
    echo "✓ Columnas idmaquina añadidas a facturascli, albaranescli, presupuestoscli\n";
    echo "✓ Ya puedes usar el selector de vehículos en los documentos\n";
} else {
    echo "⚠ Se encontraron {$errors} errores\n";
    echo "Revisa manualmente la base de datos\n";
}

echo "\n";
