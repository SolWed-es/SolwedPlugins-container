<?php

namespace FacturaScripts\Plugins\DonDominio\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioApiClient;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioConfig;

class EditSettingsExtension
{
    private const VIEW_NAME = 'SettingsDonDominio';
    private const ACTION_TEST_CONNECTION = 'dondominio-test-connection';
    private const SETTINGS_FLAG = '__dondominio_test_button_added';
    private const PLUGIN_ID = 'dondominio';

    public function execAfterAction(): Closure
    {
        $viewName = self::VIEW_NAME;
        $flagKey = self::SETTINGS_FLAG;
        $actionName = self::ACTION_TEST_CONNECTION;

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
                'icon' => 'fa-solid fa-plug',
                'label' => 'dondominio-check-connection',
            ]);

            $view->settings[$flagKey] = true;
        };
    }

    public function execPreviousAction(): Closure
    {
        $actionName = self::ACTION_TEST_CONNECTION;
        $pluginId = self::PLUGIN_ID;

        return function (string $action) use ($actionName, $pluginId) {
            if ($actionName !== $action) {
                return null;
            }

            if (false === $this->validateFormToken()) {
                return true;
            }

            $request = $this->request;
            $fields = [
                'dondominio_apiuser' => '',
                'dondominio_apipasswd' => '',
                'dondominio_endpoint' => '',
                'dondominio_port' => null,
                'dondominio_timeout' => null,
            ];

            foreach ($fields as $key => $default) {
                Tools::settingsSet($pluginId, $key, $request->request->get($key, $default));
            }

            $verify = $request->request->get('dondominio_verifyssl');
            Tools::settingsSet($pluginId, 'dondominio_verifyssl', $verify ? '1' : '0');

            $apiUser = trim((string)$request->request->get('dondominio_apiuser', ''));
            $apiPass = trim((string)$request->request->get('dondominio_apipasswd', ''));
            if ('' === $apiUser || '' === $apiPass) {
                Tools::log()->warning('dondominio-missing-credentials');
                return true;
            }

            DonDominioConfig::clearCache();
            DonDominioApiClient::reset();

            $client = DonDominioApiClient::get();
            if ($client) {
                Tools::log()->notice('dondominio-check-success');
            } else {
                Tools::log()->error('dondominio-check-failure');
            }

            return true;
        };
    }
}
