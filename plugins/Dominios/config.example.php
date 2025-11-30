<?php
/**
 * Archivo de configuración de ejemplo para el plugin Dominios.
 *
 * INSTRUCCIONES:
 * 1. Copia este archivo a config.php
 *    cp config.example.php config.php
 *
 * 2. Edita config.php con tus credenciales reales
 *    nano config.php
 *
 * 3. El archivo config.php está en .gitignore y NO se subirá al repositorio
 *
 * IMPORTANTE:
 * - No edites este archivo (config.example.php)
 * - Edita solo config.php con tus credenciales
 * - config.php no se incluirá en el repositorio por seguridad
 */

return [
    // Credenciales de la API de DonDominio
    'apiuser' => 'TU-USUARIO-API',
    'apipasswd' => 'TU-CONTRASEÑA-API',

    // Configuración de conexión
    'endpoint' => 'https://simple-api.dondominio.net',
    'port' => 443,
    'timeout' => 8,
    'verifySSL' => true,
];
