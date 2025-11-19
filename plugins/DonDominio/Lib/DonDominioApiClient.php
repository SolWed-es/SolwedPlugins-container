<?php
/**
 * This file is part of the DonDominio plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Tools;

/**
 * Cliente API de DonDominio - Singleton
 * Proporciona acceso al cliente SDK de DonDominio.
 */
final class DonDominioApiClient
{
    private static ?\Dondominio\API\API $instance = null;

    /**
     * Obtiene la instancia del cliente de API de DonDominio.
     * Utiliza patrón Singleton para evitar múltiples instancias.
     */
    public static function get(): ?\Dondominio\API\API
    {
        if (!DonDominioConfig::isConfigured()) {
            return null;
        }

        if (null === self::$instance) {
            self::$instance = self::buildClient();
        }

        return self::$instance;
    }

    /**
     * Reinicia la instancia del cliente.
     * Útil cuando se actualiza la configuración.
     */
    public static function reset(): void
    {
        self::$instance = null;
        DonDominioConfig::clearCache();
    }

    /**
     * Construye la instancia del cliente de API.
     */
    private static function buildClient(): ?\Dondominio\API\API
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

            Tools::log()->error('dondominio-sdk-not-found');
        } catch (\Throwable $exception) {
            Tools::log()->error('dondominio-client-error', ['%message%' => $exception->getMessage()]);
        }

        return null;
    }
}
