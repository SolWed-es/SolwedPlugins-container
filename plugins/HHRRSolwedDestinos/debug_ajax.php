<?php
/**
 * Script de debug para verificar las llamadas AJAX
 */

// Habilitar errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular una llamada AJAX
$_POST['action'] = 'load-rutas-data';
$_POST['idruta'] = 1;

// Incluir el controlador
require_once __DIR__ . '/../../vendor/autoload.php';

// Crear una instancia del controlador
$controller = new \FacturaScripts\Plugins\HHRRSolwedDestinos\Controller\EditRuta();

// Simular la llamada
$controller->privateCore(null, null, []);

echo "Debug completado\n";