<?php
/**
 * This file is part of the DonDominio plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\DonDominio;

use FacturaScripts\Core\Migrations;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\DonDominio\Migrations\AddDomainContactIdColumn;
use FacturaScripts\Plugins\DonDominio\Migrations\CreateDomainContactsTable;

class Init
{
    private $autoloadPath;
    private $pluginId = 'dondominio';
    private static $extensionsRegistered = false;

    public function __construct()
    {
        $this->autoloadPath = Tools::folder('Plugins', 'DonDominio', 'vendor', 'autoload.php');
    }

    public function init(): void
    {
        $this->loadSdk();
        $this->initializeSettings();
        $this->initGlobalVars();
        $this->installModels();
        $this->runMigrations();
        $this->registerControllerExtensions();
    }

    public function update(): void
    {
        $this->loadSdk();
        $this->initializeSettings();
        $this->installModels();
        $this->runMigrations();
        $this->registerControllerExtensions();
    }

    public function uninstall(): void
    {
        // nothing to do on uninstall
    }

    private function loadSdk(): void
    {
        if (is_file($this->autoloadPath)) {
            require_once $this->autoloadPath;
        }
    }

    private function initializeSettings(): void
    {
        try {
            $settingsChanged = false;

            // Aseguramos una IP válida del servidor
            $serverIp = $this->detectServerIp();
            $settingsChanged = $this->ensureSettingValue('dondominio_serverip', $serverIp) || $settingsChanged;

            // Valores por defecto si el usuario aún no ha configurado nada
            $settingsChanged = $this->ensureSettingValue('dondominio_endpoint', 'https://simple-api.dondominio.net') || $settingsChanged;
            $settingsChanged = $this->ensureSettingValue('dondominio_port', '443') || $settingsChanged;
            $settingsChanged = $this->ensureSettingValue('dondominio_timeout', '15') || $settingsChanged;
            $settingsChanged = $this->ensureSettingValue('dondominio_verifyssl', '1') || $settingsChanged;
            $settingsChanged = $this->ensureSettingValue('dondominio_enable_listcliente', '0') || $settingsChanged;

            if ($settingsChanged) {
                Tools::settingsSave();
                Tools::settingsClear();
            }
        } catch (\Exception $e) {
            Tools::log()->error('Error en initializeSettings: ' . $e->getMessage());
        }
    }

    private function initGlobalVars(): void
    {
        try {
            $ipAddress = $this->detectServerIp();
            $GLOBALS['ip_address'] = $ipAddress;
        } catch (\Exception $e) {
            Tools::log()->error('Error en initGlobalVars: ' . $e->getMessage());
        }
    }

    private function detectServerIp(): string
    {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return '127.0.0.1';
    }

    private function installModels(): void
    {
        try {
            // Instalar el modelo extendido de Cliente
            $clienteDonDominio = new \FacturaScripts\Plugins\DonDominio\Model\ClienteDonDominio();
            $clienteDonDominio->install();

            // Instalar el modelo de dominios
            $domain = new \FacturaScripts\Plugins\DonDominio\Model\Domain();
            $domain->install();

            $server = new \FacturaScripts\Plugins\DonDominio\Model\Server();
            $server->install();

             $server = new \FacturaScripts\Plugins\DonDominio\Model\ClienteERP();
            $server->install();
        } catch (\Exception $e) {
            $errorMsg = 'Error instalando modelos de DonDominio: ' . $e->getMessage();
            Tools::log()->error($errorMsg);
        }
    }

    private function ensureSettingValue(string $key, string $default): bool
    {
        $currentValue = Tools::settings($this->pluginId, $key, null);
        if (null === $currentValue || (is_string($currentValue) && '' === trim($currentValue))) {
            Tools::settingsSet($this->pluginId, $key, $default);
            return true;
        }

        return false;
    }

    private function registerControllerExtensions(): void
    {
        if (self::$extensionsRegistered) {
            return;
        }

        try {
            \FacturaScripts\Dinamic\Controller\EditSettings::addExtension(
                new \FacturaScripts\Plugins\DonDominio\Extension\Controller\EditSettingsExtension()
            );

            // Extensión para añadir funcionalidad de dominios al PortalCliente
            \FacturaScripts\Dinamic\Controller\PortalCliente::addExtension(
                new \FacturaScripts\Plugins\DonDominio\Extension\Controller\PortalCliente()
            );

            if ($this->isListClienteDomainsEnabled()) {
                // Extensión para añadir pestaña de dominios a ListCliente
                \FacturaScripts\Dinamic\Controller\ListCliente::addExtension(
                    new \FacturaScripts\Plugins\DonDominio\Extension\Controller\ListCliente()
                );
            }

            self::$extensionsRegistered = true;
        } catch (\Throwable $e) {
            Tools::log()->error('Error registrando extensiones: ' . $e->getMessage());
        }
    }

    private function runMigrations(): void
    {
        try {
            Migrations::runPluginMigrations([
                new CreateDomainContactsTable(),
                new AddDomainContactIdColumn(),
            ]);
        } catch (\Throwable $e) {
            Tools::log()->error('Error ejecutando migraciones de DonDominio: ' . $e->getMessage());
        }
    }

    private function isListClienteDomainsEnabled(): bool
    {
        $value = Tools::settings($this->pluginId, 'dondominio_enable_listcliente', '0');
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower((string) $value);
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}
