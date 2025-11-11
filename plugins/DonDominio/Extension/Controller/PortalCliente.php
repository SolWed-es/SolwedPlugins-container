<?php
/**
 * Extension para anadir funcionalidad de dominios al PortalCliente
 */

namespace FacturaScripts\Plugins\DonDominio\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;

/**
 * Extension del controlador PortalCliente para anadir funcionalidad de dominios
 */
class PortalCliente
{
    private const LOG_CHANNEL = 'dondominio_portal_domains';

    /**
     * Anade la vista de dominios al crear las vistas
     */
    public function createViews(): Closure
    {
        return function () {
            $this->createViewDomains();
        };
    }

    /**
     * Crea la vista de dominios
     */
    protected function createViewDomains(): Closure
    {
        return function (string $viewName = 'PortalDomains') {
            $this->addHtmlView($viewName, 'Tab/PortalDomains', 'Cliente', Tools::lang()->trans('dondominio-domains-tab'), 'fa-solid fa-globe');
        };
    }

    /**
     * Carga los datos para la vista de dominios
     */
    public function loadData(): Closure
    {
        $logChannel = self::LOG_CHANNEL;

        return function ($viewName, $view) use ($logChannel) {
            if ($viewName === 'PortalDomains') {
                // Obtener el cliente del contacto
                $cliente = $this->contact->getCustomer(false);
                if (!$cliente instanceof \FacturaScripts\Dinamic\Model\Cliente || !$cliente->exists()) {
                    $view->cursor = [];
                    $view->count = 0;
                    return;
                }

                // Cargar el modelo extendido para acceder a la configuracion de autologin
                $clienteExtendido = new \FacturaScripts\Plugins\DonDominio\Model\ClienteDonDominio();
                $clienteExtendido->loadFromCode($cliente->codcliente);

                // Usar los datos cacheados en base de datos
                try {
                    $service = new \FacturaScripts\Plugins\DonDominio\Lib\DomainSyncService();
                    $contacts = $service->getClientContacts($cliente->codcliente, false);

                    foreach ($contacts as &$contact) {
                        foreach ($contact['domains'] as &$domain) {
                            $domain['whois_url'] = \FacturaScripts\Plugins\DonDominio\Lib\AutoLoginService::generateWhoisUrl($domain['name']);
                            $domainData = ['nameservers' => $domain['nameservers'] ?? []];
                            $domain['tools_urls'] = \FacturaScripts\Plugins\DonDominio\Lib\AutoLoginService::getAvailableAccessUrls($clienteExtendido, $domainData);
                        }
                    }

                    $view->cursor = $contacts;
                    $view->count = count($contacts);
                    $view->setSettings('active', $view->count > 0);

                    $expiringDomains = \FacturaScripts\Plugins\DonDominio\Lib\DomainAlertService::getExpiringDomainsForClient($cliente->codcliente, 30, false);
                    $view->expiring_domains = $expiringDomains;
                    $view->expiring_count = count($expiringDomains);

                    Tools::log($logChannel)->notice('dondominio-portal-domains-loaded', [
                        '%count%' => $view->count,
                    ]);
                } catch (\Exception $e) {
                    Tools::log()->error('Error cargando dominios en portal: ' . $e->getMessage());
                    $view->cursor = [];
                    $view->count = 0;
                    $view->expiring_domains = [];
                    $view->expiring_count = 0;

                    Tools::log($logChannel)->warning('dondominio-portal-domains-error', [
                        '%message%' => $e->getMessage(),
                    ]);
                }
            }
        };
    }
}

