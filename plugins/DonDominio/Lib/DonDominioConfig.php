<?php

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Tools;

final class DonDominioConfig
{
    private const DEFAULT_ENDPOINT = 'https://simple-api.dondominio.net';
    private const DEFAULT_TIMEOUT = 15;
    private const DEFAULT_PORT = 443;
    private const PLUGIN_ID = 'dondominio';
    
    private static ?array $cachedOptions = null;
    private static ?bool $cachedIsConfigured = null;

    /**
     * Obtiene las opciones de configuración de DonDominio
     * Utiliza caché para evitar múltiples lecturas de la base de datos
     */
    public static function getOptions(): array
    {
        if (null !== self::$cachedOptions) {
            return self::$cachedOptions;
        }

        $endpoint = Tools::settings(self::PLUGIN_ID, 'dondominio_endpoint', self::DEFAULT_ENDPOINT) ?: self::DEFAULT_ENDPOINT;
        
        $timeout = (int) Tools::settings(self::PLUGIN_ID, 'dondominio_timeout', self::DEFAULT_TIMEOUT);
        $timeout = ($timeout > 0) ? $timeout : self::DEFAULT_TIMEOUT;

        $port = (int) Tools::settings(self::PLUGIN_ID, 'dondominio_port', self::DEFAULT_PORT);
        $port = ($port > 0) ? $port : self::DEFAULT_PORT;

        $verifySetting = Tools::settings(self::PLUGIN_ID, 'dondominio_verifyssl', true);
        if (is_bool($verifySetting)) {
            $verifySSL = $verifySetting;
        } elseif (is_numeric($verifySetting)) {
            $verifySSL = (int)$verifySetting === 1;
        } else {
            $verifySSL = !in_array(strtolower(trim((string)$verifySetting)), ['0', 'false', 'no', ''], true);
        }

        self::$cachedOptions = [
            'endpoint' => $endpoint,
            'port' => $port,
            'timeout' => $timeout,
            'verifySSL' => $verifySSL,
            'apiuser' => (string) Tools::settings(self::PLUGIN_ID, 'dondominio_apiuser', ''),
            'apipasswd' => (string) Tools::settings(self::PLUGIN_ID, 'dondominio_apipasswd', '')
        ];

        return self::$cachedOptions;
    }

    /**
     * Verifica si el plugin está configurado con credenciales válidas
     */
    public static function isConfigured(): bool
    {
        if (null !== self::$cachedIsConfigured) {
            return self::$cachedIsConfigured;
        }

        $user = trim((string) Tools::settings(self::PLUGIN_ID, 'dondominio_apiuser', ''));
        $password = trim((string) Tools::settings(self::PLUGIN_ID, 'dondominio_apipasswd', ''));

        self::$cachedIsConfigured = !empty($user) && !empty($password);
        return self::$cachedIsConfigured;
    }

    /**
     * Limpia el caché de configuración
     * Se debe llamar después de actualizar la configuración
     */
    public static function clearCache(): void
    {
        self::$cachedOptions = null;
        self::$cachedIsConfigured = null;
    }
}
