<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Lib;

use Automattic\WooCommerce\Client;
use FacturaScripts\Core\Tools;

class WooHelper {

    private static $woo_client = null;
    public static function getClient(): ?Client
    {
        error_log("[WooAPI] Initializing client");


        if (self::$woo_client) {
            return self::$woo_client;
        }

        $url = Tools::settings('woocommerce', 'enlace');
        $ck = Tools::settings('woocommerce', 'ck');
        $cs = Tools::settings('woocommerce', 'cs');

        if (!$url || !$ck || !$cs) {
            return null;
        }

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

        return self::$woo_client;
    }

}