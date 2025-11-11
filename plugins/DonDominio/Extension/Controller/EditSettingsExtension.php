<?php

namespace FacturaScripts\Plugins\DonDominio\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioApiClient;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioConfig;
use FacturaScripts\Plugins\DonDominio\Lib\BulkDomainSyncService;
use FacturaScripts\Plugins\DonDominio\Lib\TableInstaller;

class EditSettingsExtension
{
    private const VIEW_NAME = 'SettingsDonDominio';
    private const ACTION_TEST_CONNECTION = 'dondominio-test-connection';
    private const ACTION_INSTALL_TABLES = 'dondominio-install-tables';
    private const ACTION_SYNC_ALL = 'dondominio-sync-all';
    private const SETTINGS_FLAG = '__dondominio_test_button_added';
    private const PLUGIN_ID = 'dondominio';

    public function execAfterAction(): Closure
    {
        $viewName = self::VIEW_NAME;
        $flagKey = self::SETTINGS_FLAG;
        $actionName = self::ACTION_TEST_CONNECTION;

        $installAction = self::ACTION_INSTALL_TABLES;
        $syncAllAction = self::ACTION_SYNC_ALL;

        return function (string $action) use ($viewName, $flagKey, $actionName, $installAction, $syncAllAction): void {
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

            $this->addButton($viewName, [
                'action' => $installAction,
                'color' => 'outline-secondary',
                'icon' => 'fa-solid fa-database',
                'label' => 'dondominio-install-tables',
            ]);

            $this->addButton($viewName, [
                'action' => $syncAllAction,
                'color' => 'outline-warning',
                'icon' => 'fa-solid fa-rotate',
                'label' => 'dondominio-sync-all',
            ]);

            $view->settings[$flagKey] = true;
        };
    }

    public function execPreviousAction(): Closure
    {
        $actionName = self::ACTION_TEST_CONNECTION;
        $installAction = self::ACTION_INSTALL_TABLES;
        $syncAllAction = self::ACTION_SYNC_ALL;
        $pluginId = self::PLUGIN_ID;

        return function (string $action) use ($actionName, $installAction, $syncAllAction, $pluginId) {
            if ($installAction === $action) {
                if (false === $this->validateFormToken()) {
                    return true;
                }

                TableInstaller::ensureTables();
                Tools::log()->notice('dondominio-install-tables-ok');
                return true;
            }

            if ($syncAllAction === $action) {
                if (false === $this->validateFormToken()) {
                    return true;
                }

                try {
                    $summary = BulkDomainSyncService::syncAllClients();
                    Tools::log()->notice('dondominio-sync-all-completed', [
                        '%processed%' => $summary['processed'],
                        '%synced%' => $summary['synced'],
                        '%errors%' => $summary['errors'],
                    ]);
                } catch (\Throwable $exception) {
                    Tools::log()->error('dondominio-sync-all-error', [
                        '%message%' => $exception->getMessage(),
                    ]);
                }

                return true;
            }

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
