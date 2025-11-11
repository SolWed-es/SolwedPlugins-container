<?php
declare(strict_types=1);

/**
 * Script de ayuda para asegurarse de que las tablas de caché de DonDominio existen.
 *       php Plugins/DonDominio/scripts/install_tables.php
 */

require_once dirname(__DIR__, 3) . '/config.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Plugins\DonDominio\Lib\TableInstaller;

echo "Verificando tablas de DonDominio...\n";

$database = new DataBase();
$database->connect();

$results = TableInstaller::ensureTables($database);
foreach ($results as $table => $status) {
    switch ($status) {
        case 'exists':
            echo " - La tabla {$table} ya existe.\n";
            break;
        case 'created':
            echo " - La tabla {$table} se creó correctamente.\n";
            break;
        case 'failed':
        default:
            echo " - No se pudo crear {$table}. Revisa los logs del servidor.\n";
            break;
    }
}

echo "Instalación completada. Si todo fue correcto, ya puedes sincronizar dominios desde el portal.\n";
