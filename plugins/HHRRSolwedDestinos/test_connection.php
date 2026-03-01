<?php
/**
 * Script de diagnóstico para verificar la conexión y la tabla rrhh_employeeroutes
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase;

// Inicializar la aplicación
AppSettings::init();

// Crear conexión a la base de datos
$db = new DataBase();

// Verificar si la tabla existe
$tableName = 'rrhh_employeeroutes';
$sql = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $tableName . "'";

$result = $db->select($sql);
if ($result) {
    $exists = $result[0]['count'] > 0;
    echo "Tabla rrhh_employeeroutes existe: " . ($exists ? 'SÍ' : 'NO') . "\n";
    
    if ($exists) {
        // Verificar estructura de la tabla
        $sql = "DESCRIBE rrhh_employeeroutes";
        $structure = $db->select($sql);
        echo "Estructura de la tabla:\n";
        foreach ($structure as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    }
} else {
    echo "Error al verificar la tabla: " . $db->error() . "\n";
}

// Verificar si hay datos en la tabla
if ($exists) {
    $sql = "SELECT COUNT(*) as total FROM rrhh_employeeroutes";
    $result = $db->select($sql);
    if ($result) {
        echo "Total de registros: " . $result[0]['total'] . "\n";
    }
}

echo "Diagnóstico completado.\n";