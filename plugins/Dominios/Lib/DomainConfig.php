<?php

namespace FacturaScripts\Plugins\Dominios\Lib;

use FacturaScripts\Core\Tools;

/**
 * Gestión de configuración para el plugin Dominios.
 * Maneja las credenciales y parámetros de conexión con DonDominio API.
 */
final class DomainConfig
{
    private const PLUGIN_ID = 'dominios';

    /** @var array|null */
    private static $config = null;

    /**
     * Carga la configuración desde el archivo config.php
     */
    private static function loadConfig(): array
    {
        if (null !== self::$config) {
            return self::$config;
        }

        $configPath = Tools::folder('Plugins', 'Dominios', 'config.php');

        if (!file_exists($configPath)) {
            Tools::log()->critical('domain-config-missing', [
                '%message%' => 'No se encontró el archivo config.php. Copia config.example.php a config.php y configura tus credenciales.',
                '%path%' => $configPath,
            ]);

            self::$config = [];
            return self::$config;
        }

        try {
            self::$config = require $configPath;

            if (!is_array(self::$config)) {
                Tools::log()->error('domain-config-invalid', [
                    '%message%' => 'El archivo config.php debe retornar un array.',
                ]);
                self::$config = [];
            }

        } catch (\Throwable $e) {
            Tools::log()->error('domain-config-error', [
                '%message%' => $e->getMessage(),
                '%file%' => $configPath,
            ]);
            self::$config = [];
        }

        return self::$config;
    }

    /**
     * Verifica si la configuración del plugin está completa.
     */
    public static function isConfigured(): bool
    {
        $options = self::getOptions();
        return !empty($options['apiuser']) && !empty($options['apipasswd']);
    }

    /**
     * Obtiene todas las opciones de configuración necesarias para la API.
     */
    public static function getOptions(): array
    {
        $config = self::loadConfig();

        return [
            'apiuser' => $config['apiuser'] ?? '',
            'apipasswd' => $config['apipasswd'] ?? '',
            'endpoint' => $config['endpoint'] ?? 'https://simple-api.dondominio.net',
            'port' => (int) ($config['port'] ?? 443),
            'timeout' => (int) ($config['timeout'] ?? 15),
            'verifySSL' => (bool) ($config['verifySSL'] ?? true),
        ];
    }
}
