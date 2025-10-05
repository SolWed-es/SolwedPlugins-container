<?php

namespace FacturaScripts\Plugins\SolwedTiendaWeb\Lib;

use Automattic\WooCommerce\Client;
use FacturaScripts\Core\Tools;

class WooHelper
{

    private static $woo_client = null;
    public static function getClient(): ?Client
    {
        error_log("[WooAPI] Initializing client");


        if (self::$woo_client) {
            error_log("[WooAPI] Returning existing client instance");
            return self::$woo_client;
        }

        $url = Tools::settings('woocommerce', 'enlace');
        $ck = Tools::settings('woocommerce', 'ck');
        $cs = Tools::settings('woocommerce', 'cs');

        error_log("[WooAPI] Configuration - URL: " . ($url ?: 'NOT SET'));
        error_log("[WooAPI] Configuration - Consumer Key: " . ($ck ? 'SET (length: ' . strlen($ck) . ')' : 'NOT SET'));
        error_log("[WooAPI] Configuration - Consumer Secret: " . ($cs ? 'SET (length: ' . strlen($cs) . ')' : 'NOT SET'));

        if (!$url || !$ck || !$cs) {
            error_log("[WooAPI] ERROR: Missing WooCommerce configuration");
            return null;
        }

        error_log("[WooAPI] Creating new WooCommerce client");
        self::$woo_client = new Client(
            $url,
            $ck,
            $cs,
            [
                'version' => 'wc/v3',
                'verify_ssl' => false,
                'timeout' => 15
            ]
        );

        error_log("[WooAPI] Client created successfully");
        return self::$woo_client;
    }
}
