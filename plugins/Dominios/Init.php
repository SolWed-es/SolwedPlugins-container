<?php
/**
 * This file is part of the Dominios plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\Dominios;

use FacturaScripts\Core\Tools;

/**
 * Plugin initialization for Dominios.
 * Provides domain management integration with domain API.
 * No database persistence - all data is fetched directly from API.
 */
class Init
{
    private string $autoloadPath;
    private string $pluginId = 'dominios';
    private static bool $extensionsRegistered = false;

    public function __construct()
    {
        // Usar Tools::folder() para rutas compatibles con FacturaScripts
        $this->autoloadPath = Tools::folder('Plugins', 'Dominios', 'vendor', 'autoload.php');
    }

    public function init(): void
    {
        $this->checkDependencies();
        $this->loadSdk();
        $this->checkConfig();
        $this->initializeSettings();
        $this->copyAssets();
        $this->registerControllerExtensions();
    }

    public function update(): void
    {
        $this->checkDependencies();
        $this->loadSdk();
        $this->checkConfig();
        $this->initializeSettings();
        $this->copyAssets();
        $this->registerControllerExtensions();
    }

    public function uninstall(): void
    {
        // No cleanup required - no database tables created
    }

    /**
     * Carga el SDK del proveedor de dominios.
     */
    private function loadSdk(): void
    {
        if (is_file($this->autoloadPath)) {
            require_once $this->autoloadPath;
        }
    }

    /**
     * Verifica las dependencias del sistema.
     */
    private function checkDependencies(): void
    {
        // Verificar cURL
        if (!function_exists('curl_init')) {
            throw new \Exception('La extensión cURL de PHP es requerida. Instale php-curl');
        }

        // Nota: Las verificaciones de archivos se hacen en loadSdk() para evitar
        // problemas en entornos de testing. El plugin asume que vendor/ está incluido.
    }

    /**
     * Verifica que existe el archivo de configuración.
     */
    private function checkConfig(): void
    {
        $configPath = Tools::folder('Plugins', 'Dominios', 'config.php');

        if (!file_exists($configPath)) {
            $examplePath = Tools::folder('Plugins', 'Dominios', 'config.example.php');

            Tools::log()->warning('domain-config-not-found', [
                '%message%' => 'No se encontró config.php. Copia config.example.php a config.php y edita las credenciales.',
                '%example%' => $examplePath,
                '%target%' => $configPath,
            ]);
        }
    }

    /**
     * Copia los assets necesarios.
     */
    private function copyAssets(): void
    {
        $source = Tools::folder('Plugins', 'Dominios', 'Assets', 'JS', 'DominiosPortalDomains.js');
        $dest = Tools::folder('Dinamic', 'Assets', 'JS', 'DominiosPortalDomains.js');

        if (file_exists($source)) {
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            if (!file_exists($dest) || filemtime($source) > filemtime($dest)) {
                copy($source, $dest);
                Tools::log()->info('domain-assets-copied', [
                    '%source%' => $source,
                    '%dest%' => $dest,
                ]);
            }
        } else {
            Tools::log()->warning('domain-assets-source-missing', [
                '%source%' => $source,
            ]);
        }
    }

    /**
     * Inicializa la configuración del plugin.
     * ELIMINADO: No guarda configuración en BD para mantener plugin stateless
     */
    private function initializeSettings(): void
    {
        // Plugin completamente stateless - configuración leída del .ini
        // No se guarda nada en base de datos
    }

    /**
     * Obtiene un valor del facturascripts.ini
     */
    private function getIniSetting(string $key, string $default = ''): string
    {
        $iniPath = Tools::folder('Plugins', 'Dominios', 'facturascripts.ini');
        if (!file_exists($iniPath)) {
            return $default;
        }

        $ini = parse_ini_file($iniPath, false);
        return $ini[$key] ?? $default;
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
            if (class_exists('\FacturaScripts\Dinamic\Controller\PortalCliente')) {
                \FacturaScripts\Dinamic\Controller\PortalCliente::addExtension(
                    new \FacturaScripts\Plugins\Dominios\Extension\Controller\PortalCliente()
                );
            } else {
                Tools::log()->info('domain-portalcliente-not-available', [
                    '%message%' => 'El plugin PortalCliente no está disponible. La extensión se cargará cuando esté disponible.',
                ]);
            }

            // Extensión para EditCliente (mostrar dominios en edición de cliente)
            if (class_exists('\FacturaScripts\Dinamic\Controller\EditCliente')) {
                \FacturaScripts\Dinamic\Controller\EditCliente::addExtension(
                    new \FacturaScripts\Plugins\Dominios\Extension\Controller\EditCliente()
                );
            } else {
                Tools::log()->info('domain-editcliente-not-available', [
                    '%message%' => 'El controlador EditCliente no está disponible. La extensión se cargará cuando esté disponible.',
                ]);
            }

            self::$extensionsRegistered = true;
        } catch (\Throwable $e) {
            Tools::log()->error('domain-extension-error', [
                '%message%' => $e->getMessage(),
                '%trace%' => $e->getTraceAsString(),
            ]);
        }
    }
}
