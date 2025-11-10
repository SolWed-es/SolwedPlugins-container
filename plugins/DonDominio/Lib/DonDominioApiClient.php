<?php

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Tools;

final class DonDominioApiClient
{
    private static $instance = null;

    /**
     * Obtiene la instancia del cliente de API de DonDominio
     * Utiliza patrón Singleton para evitar múltiples instancias
     */
    public static function get()
    {
        if (!DonDominioConfig::isConfigured()) {
            Tools::log()->warning('dondominio-missing-credentials');
            return null;
        }

        if (null === self::$instance) {
            self::$instance = self::buildClient();
        }

        return self::$instance;
    }

    /**
     * Reinicia la instancia del cliente
     * Útil cuando se actualiza la configuración
     */
    public static function reset(): void
    {
        self::$instance = null;
        DonDominioConfig::clearCache();
    }

    /**
     * Construye la instancia del cliente de API
     */
    private static function buildClient()
    {
        $options = DonDominioConfig::getOptions();

        try {
            if (class_exists('\Dondominio\API\API')) {
                return new \Dondominio\API\API([
                    'apiuser' => $options['apiuser'],
                    'apipasswd' => $options['apipasswd'],
                    'endpoint' => $options['endpoint'],
                    'port' => $options['port'],
                    'timeout' => $options['timeout'],
                    'verifySSL' => $options['verifySSL'],
                ]);
            }
        } catch (\Throwable $exception) {
            Tools::log()->error('dondominio-client-error', ['%message%' => $exception->getMessage()]);
        }

        return null;
    }
}
