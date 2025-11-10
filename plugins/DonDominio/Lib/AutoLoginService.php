<?php
/**
 * Servicio para gestionar autologins a servicios externos
 */

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Servicio para autologins
 */
class AutoLoginService
{
    /**
     * Genera una URL de autologin para el servidor de correo
     *
     * @param Cliente $cliente
     * @return string|null
     */
    public static function generateMailServerUrl(Cliente $cliente): ?string
    {
        if (empty($cliente->mail_server_url) || empty($cliente->mail_username)) {
            return null;
        }

        $token = self::generateToken($cliente->codcliente, 'mail');
        $params = http_build_query([
            'user' => $cliente->mail_username,
            'token' => $token,
            'timestamp' => time()
        ]);

        return $cliente->mail_server_url . '?' . $params;
    }

    /**
     * Genera una URL de autologin para el servidor web
     *
     * @param Cliente $cliente
     * @return string|null
     */
    public static function generateWebServerUrl(Cliente $cliente): ?string
    {
        if (empty($cliente->web_server_url) || empty($cliente->web_username)) {
            return null;
        }

        $token = self::generateToken($cliente->codcliente, 'web');
        $params = http_build_query([
            'user' => $cliente->web_username,
            'token' => $token,
            'timestamp' => time()
        ]);

        return $cliente->web_server_url . '?' . $params;
    }

    /**
     * Genera una URL de autologin para el ERP
     *
     * @param Cliente $cliente
     * @return string|null
     */
    public static function generateErpAccessUrl(Cliente $cliente): ?string
    {
        if (empty($cliente->erp_access_url) || empty($cliente->erp_username)) {
            return null;
        }

        $token = self::generateToken($cliente->codcliente, 'erp');
        $params = http_build_query([
            'user' => $cliente->erp_username,
            'token' => $token,
            'timestamp' => time()
        ]);

        return $cliente->erp_access_url . '?' . $params;
    }

    /**
     * Genera una URL para consultar WHOIS de un dominio
     *
     * @param string $domain
     * @return string
     */
    public static function generateWhoisUrl(string $domain): string
    {
        // Usar la página oficial de ICANN para WHOIS
        return 'https://lookup.icann.org/es/lookup?q=' . urlencode($domain);
    }

    /**
     * Genera un token temporal para autologin
     *
     * @param string $clientCode
     * @param string $service
     * @return string
     */
    private static function generateToken(string $clientCode, string $service): string
    {
        $timestamp = time();
        $secret = Tools::config('secret_key', 'default_secret');

        $data = $clientCode . '|' . $service . '|' . $timestamp;
        $hash = hash_hmac('sha256', $data, $secret);

        return base64_encode($hash . '|' . $timestamp);
    }

    /**
     * Valida un token de autologin
     *
     * @param string $token
     * @param string $clientCode
     * @param string $service
     * @return bool
     */
    public static function validateToken(string $token, string $clientCode, string $service): bool
    {
        try {
            $decoded = base64_decode($token);
            [$hash, $timestamp] = explode('|', $decoded, 2);

            // Verificar que el token no haya expirado (válido por 1 hora)
            if (time() - (int)$timestamp > 3600) {
                return false;
            }

            $secret = Tools::config('secret_key', 'default_secret');
            $data = $clientCode . '|' . $service . '|' . $timestamp;
            $expectedHash = hash_hmac('sha256', $data, $secret);

            return hash_equals($expectedHash, $hash);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene las URLs de acceso disponibles para un cliente
     *
     * @param Cliente|\FacturaScripts\Plugins\DonDominio\Model\ClienteDonDominio $cliente
     * @param array $domainData Datos del dominio para contextualizar URLs
     * @return array
     */
    public static function getAvailableAccessUrls($cliente, array $domainData = []): array
    {
        $urls = [];

        // Correos - siempre apunta a serverdata.solwed-hosting.es (Plesk)
        $urls['mail'] = [
            'url' => 'https://serverdata.solwed-hosting.es/',
            'label' => 'Correos',
            'icon' => 'fa-solid fa-envelope',
            'class' => 'btn-outline-primary'
        ];

        // Web - apunta al segundo nameserver si existe
        $webUrl = 'https://' . ($domainData['nameservers'][1] ?? '');
        if (!empty($webUrl) && $webUrl !== 'https://') {
            $urls['web'] = [
                'url' => $webUrl,
                'label' => 'Web',
                'icon' => 'fa-solid fa-globe',
                'class' => 'btn-outline-success'
            ];
        }

        // ERP - URL específica de FacturaScript con autologin
        $erpAccessUrl = property_exists($cliente, 'erp_url') ? ($cliente->erp_url ?? '') : '';
        $erpUsername = property_exists($cliente, 'erp_user') ? ($cliente->erp_user ?? '') : '';

        // Para testing: mostrar ERP siempre (con URL por defecto si no está configurada)
        if (empty($erpAccessUrl)) {
            $erpAccessUrl = 'https://erp.solwed.es'; // URL por defecto para testing
        }

        $erpUrl = self::generateFacturaScriptAutoLoginUrl($cliente);
        if ($erpUrl) {
            $urls['erp'] = [
                'url' => $erpUrl,
                'label' => 'ERP',
                'icon' => 'fa-solid fa-cogs',
                'class' => 'btn-warning'
            ];
        }

        return $urls;
    }

    /**
     * Genera URL de autologin para FacturaScript
     *
     * @param Cliente $cliente
     * @return string|null
     */
    public static function generateFacturaScriptAutoLoginUrl($cliente): ?string
    {
        $erpAccessUrl = property_exists($cliente, 'erp_url') ? ($cliente->erp_url ?? '') : '';
        $erpUsername = property_exists($cliente, 'erp_user') ? ($cliente->erp_user ?? '') : '';
        $erpPassword = property_exists($cliente, 'erp_password') ? ($cliente->erp_password ?? '') : '';

        // Usar valores por defecto si no están configurados
        if (empty($erpAccessUrl)) {
            $erpAccessUrl = 'https://erp.solwed.es';
        }
        if (empty($erpUsername)) {
            $erpUsername = $cliente->codcliente; // Usar código de cliente como username por defecto
        }

        // Si no hay contraseña configurada, no se puede hacer autologin
        if (empty($erpPassword)) {
            return null;
        }

        // Asegurar que la URL tenga protocolo
        if (!preg_match('/^https?:\/\//', $erpAccessUrl)) {
            $erpAccessUrl = 'https://' . $erpAccessUrl;
        }

        // Construir URL con usuario y contraseña pre-rellenados
        $params = [
            'user' => $erpUsername,
            'password' => $erpPassword
        ];

        $baseUrl = rtrim($erpAccessUrl, '/');
        return $baseUrl . '/login?' . http_build_query($params);
    }
}
