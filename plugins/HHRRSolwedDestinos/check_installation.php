<?php
/**
 * Script para verificar la instalación del plugin
 */

echo "=== Verificación de Instalación HHRRSolwedDestinos ===\n\n";

// Verificar archivos
$files = [
    'Controller/EditRuta.php',
    'Model/EmployeeRoute.php',
    'Table/rrhh_employeeroutes.xml',
    'View/Tab/EmpleadosRuta.html.twig'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ $file existe\n";
    } else {
        echo "✗ $file NO existe\n";
    }
}

echo "\n=== Verificación completada ===\n";