<?php
/**
 * This file is part of the DonDominio plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\DonDominio;

use FacturaScripts\Core\Tools;

/**
 * Plugin initialization for DonDominio.
 * Provides domain management integration with DonDominio API.
 * No database persistence - all data is fetched directly from API.
 */
class Init
{
    private string $autoloadPath;
    private string $pluginId = 'dondominio';
    private static bool $extensionsRegistered = false;

    public function __construct()
    {
        $this->autoloadPath = Tools::folder('Plugins', 'DonDominio', 'vendor', 'autoload.php');
    }

    public function init(): void
    {
        $this->loadSdk();
        $this->initializeSettings();
        $this->registerControllerExtensions();
    }

    public function update(): void
    {
        $this->loadSdk();
        $this->initializeSettings();
        $this->registerControllerExtensions();
    }

    public function uninstall(): void
    {
        // No cleanup required - no database tables created
    }

    /**
     * Carga el SDK de DonDominio.
     */
    private function loadSdk(): void
    {
        if (is_file($this->autoloadPath)) {
            require_once $this->autoloadPath;
        }
    }

    /**
     * Inicializa la configuración del plugin.
     */
    private function initializeSettings(): void
    {
        try {
            $settingsChanged = false;

            // Configuración del endpoint
            $settingsChanged = $this->ensureSettingValue(
                'dondominio_endpoint',
                'https://simple-api.dondominio.net'
            ) || $settingsChanged;

            // Configuración del puerto
            $settingsChanged = $this->ensureSettingValue('dondominio_port', '443') || $settingsChanged;

            // Configuración del timeout
            $settingsChanged = $this->ensureSettingValue('dondominio_timeout', '15') || $settingsChanged;

            // Configuración de verificación SSL
            $settingsChanged = $this->ensureSettingValue('dondominio_verifyssl', '1') || $settingsChanged;

            if ($settingsChanged) {
                Tools::settingsSave();
                Tools::settingsClear();
            }
        } catch (\Exception $e) {
            Tools::log()->error('dondominio-init-settings-error', [
                '%message%' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Establece un valor de configuración si no existe.
     */
    private function ensureSettingValue(string $key, string $default): bool
    {
        $currentValue = Tools::settings($this->pluginId, $key, null);
        if (null === $currentValue || (is_string($currentValue) && '' === trim($currentValue))) {
            Tools::settingsSet($this->pluginId, $key, $default);
            return true;
        }

        return false;
    }

    /**
     * Registra las extensiones de controladores.
     */
    private function registerControllerExtensions(): void
    {
        if (self::$extensionsRegistered) {
            return;
        }

        try {
            // Extensión para el portal del cliente (mostrar dominios)
            \FacturaScripts\Dinamic\Controller\PortalCliente::addExtension(
                new \FacturaScripts\Plugins\DonDominio\Extension\Controller\PortalCliente()
            );

            // Extensión para EditCliente (mostrar dominios en edición de cliente)
            \FacturaScripts\Dinamic\Controller\EditCliente::addExtension(
                new \FacturaScripts\Plugins\DonDominio\Extension\Controller\EditCliente()
            );

            self::$extensionsRegistered = true;
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-extension-error', [
                '%message%' => $e->getMessage(),
            ]);
        }
    }
}
