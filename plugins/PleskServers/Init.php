<?php
/**
 * This file is part of the PleskServers plugin.
 */

namespace FacturaScripts\Plugins\PleskServers;

use FacturaScripts\Core\Tools;

class Init
{
    private string $pluginId = 'pleskservers';
    private static bool $extensionsRegistered = false;

    public function init(): void
    {
        $this->initializeSettings();
        $this->installModels();
        $this->registerControllerExtensions();
    }

    public function update(): void
    {
        $this->initializeSettings();
        $this->installModels();
        $this->registerControllerExtensions();
    }

    public function uninstall(): void
    {
        // nothing to do on uninstall
    }

    private function initializeSettings(): void
    {
        try {
            $settingsChanged = false;

            // Valores por defecto
            $settingsChanged = $this->ensureSettingValue('pleskservers_timeout', '30') || $settingsChanged;
            $settingsChanged = $this->ensureSettingValue('pleskservers_verify_ssl', '1') || $settingsChanged;
            $settingsChanged = $this->ensureSettingValue('pleskservers_cache_ttl', '300') || $settingsChanged;

            if ($settingsChanged) {
                Tools::settingsSave();
                Tools::settingsClear();
            }
        } catch (\Exception $e) {
            Tools::log()->error('Error en initializeSettings de PleskServers: ' . $e->getMessage());
        }
    }

    private function installModels(): void
    {
        try {
            // Instalar el modelo de servidores Plesk
            $pleskServer = new \FacturaScripts\Plugins\PleskServers\Model\PleskServer();
            $pleskServer->install();
        } catch (\Exception $e) {
            $errorMsg = 'Error instalando modelos de PleskServers: ' . $e->getMessage();
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
            // Extensión para configuración
            \FacturaScripts\Dinamic\Controller\EditSettings::addExtension(
                new \FacturaScripts\Plugins\PleskServers\Extension\Controller\EditSettingsExtension()
            );

            // Extensión para ficha de cliente
            \FacturaScripts\Dinamic\Controller\EditCliente::addExtension(
                new \FacturaScripts\Plugins\PleskServers\Extension\Controller\EditClienteExtension()
            );

            self::$extensionsRegistered = true;
        } catch (\Throwable $e) {
            Tools::log()->error('Error registrando extensiones de PleskServers: ' . $e->getMessage());
        }
    }
}
