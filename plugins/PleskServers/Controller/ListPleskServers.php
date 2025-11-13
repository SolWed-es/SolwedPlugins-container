<?php

namespace FacturaScripts\Plugins\PleskServers\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Plugins\PleskServers\Model\PleskServer;

class ListPleskServers extends ListController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'pleskservers-title';
        $pageData['icon'] = 'fa-solid fa-server';
        $pageData['menu'] = 'admin';
        $pageData['submenu'] = 'config';

        return $pageData;
    }

    protected function createViews(): void
    {
        $this->createViewPleskServers();
    }

    private function createViewPleskServers(): void
    {
        $this->addView('ListPleskServers', 'PleskServer', 'pleskservers-title', 'fa-solid fa-server');
        $this->addSearchFields('ListPleskServers', ['name', 'description', 'host']);
        $this->addOrderBy('ListPleskServers', ['name'], 'name');
        $this->addOrderBy('ListPleskServers', ['created_at'], 'created_at', 2);

        // Botones
        $this->addButton('ListPleskServers', [
            'action' => 'test-connection',
            'color' => 'info',
            'icon' => 'fa-solid fa-plug',
            'label' => 'test-connection',
            'type' => 'action'
        ]);
    }

    protected function execPreviousAction($action): bool
    {
        switch ($action) {
            case 'test-connection':
                return $this->testConnection();

            default:
                return parent::execPreviousAction($action);
        }
    }

    private function testConnection(): bool
    {
        $codes = $this->request->request->get('codes', '');
        if (empty($codes)) {
            $this->toolBox()->i18nLog()->warning('no-server-selected');
            return true;
        }

        $codes = explode(',', $codes);
        foreach ($codes as $code) {
            $server = new PleskServer();
            if ($server->loadFromCode($code) && $server->testConnection()) {
                $this->toolBox()->i18nLog()->notice('connection-ok', ['%server%' => $server->name]);
            } else {
                $this->toolBox()->i18nLog()->error('connection-error', ['%server%' => $server->name]);
            }
        }

        return true;
    }
}
