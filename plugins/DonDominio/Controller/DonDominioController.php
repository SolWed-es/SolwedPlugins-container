<?php

namespace FacturaScripts\Plugins\DonDominio\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioApiClient;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioConfig;

class DonDominioController extends Controller
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'DonDominio';
        $pageData['icon'] = 'fa-solid fa-globe';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->get('action', '');

        if ($action === 'test') {
            $this->testConnection();
        }
    }

    private function testConnection(): void
    {
        if (!DonDominioConfig::isConfigured()) {
            $this->toolBox()->i18nLog()->warning('dondominio-missing-credentials');
            return;
        }

        $client = DonDominioApiClient::get();
        if ($client) {
            $this->toolBox()->i18nLog()->notice('Conexión con DonDominio establecida correctamente');
        } else {
            $this->toolBox()->i18nLog()->error('No se pudo establecer conexión con DonDominio');
        }
    }
}
