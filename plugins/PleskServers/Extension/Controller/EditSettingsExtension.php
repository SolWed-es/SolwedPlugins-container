<?php

namespace FacturaScripts\Plugins\PleskServers\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;

class EditSettingsExtension
{
    private const VIEW_NAME = 'SettingsPleskServers';
    private const ACTION_MANAGE_SERVERS = 'pleskservers-manage-servers';
    private const SETTINGS_FLAG = '__pleskservers_button_added';
    private const PLUGIN_ID = 'pleskservers';

    public function execAfterAction(): Closure
    {
        $viewName = self::VIEW_NAME;
        $flagKey = self::SETTINGS_FLAG;
        $actionName = self::ACTION_MANAGE_SERVERS;

        return function (string $action) use ($viewName, $flagKey, $actionName): void {
            if (!isset($this->views[$viewName])) {
                return;
            }

            $view = $this->views[$viewName];
            if (!empty($view->settings[$flagKey])) {
                return;
            }

            $this->addButton($viewName, [
                'action' => $actionName,
                'color' => 'outline-primary',
                'icon' => 'fa-solid fa-server',
                'label' => 'manage-plesk-servers',
                'type' => 'link',
                'href' => 'ListPleskServers'
            ]);

            $view->settings[$flagKey] = true;
        };
    }

    public function execPreviousAction(): Closure
    {
        $actionName = self::ACTION_MANAGE_SERVERS;
        $pluginId = self::PLUGIN_ID;

        return function (string $action) use ($actionName, $pluginId) {
            if ($actionName !== $action) {
                return null;
            }

            // Save settings
            $request = $this->request;
            $fields = [
                'pleskservers_timeout' => 30,
                'pleskservers_verify_ssl' => true,
                'pleskservers_cache_ttl' => 300,
            ];

            foreach ($fields as $key => $default) {
                $value = $request->request->get($key, $default);
                if (is_bool($default)) {
                    $value = $value ? '1' : '0';
                }
                Tools::settingsSet($pluginId, $key, $value);
            }

            Tools::settingsSave();
            Tools::log()->notice('pleskservers-settings-saved');

            return true;
        };
    }
}
