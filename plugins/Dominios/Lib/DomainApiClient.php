<?php
/**
 * This file is part of the Dominios plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\Dominios\Lib;

use FacturaScripts\Core\Tools;

/**
 * Cliente API del proveedor de dominios - Singleton
 * Proporciona acceso al cliente SDK del proveedor.
 */
final class DomainApiClient
{
    private static ?\Dondominio\API\API $instance = null;

    /**
     * Obtiene la instancia del cliente de API del proveedor.
     * Utiliza patrón Singleton para evitar múltiples instancias.
     */
    public static function get(): ?\Dondominio\API\API
    {
        if (!DomainConfig::isConfigured()) {
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
    }

    /**
     * Construye la instancia del cliente de API.
     */
    private static function buildClient(): ?\Dondominio\API\API
    {
        $options = DomainConfig::getOptions();

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

            Tools::log()->error('domain-sdk-not-found');
        } catch (\Throwable $exception) {
            Tools::log()->error('domain-client-error', ['%message%' => $exception->getMessage()]);
        }

        return null;
    }
}
