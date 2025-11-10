<?php
/**
 * Extensión para añadir funcionalidad de dominios al PortalCliente
 */

namespace FacturaScripts\Plugins\DonDominio\Extension\Controller;

use Closure;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;

/**
 * Extensión del controlador PortalCliente para añadir funcionalidad de dominios
 */
class PortalCliente
{
    /**
     * Añade la vista de dominios al crear las vistas
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
            $this->addHtmlView($viewName, 'Tab/PortalDomains', 'Cliente', 'domains', 'fa-solid fa-globe');
        };
    }

    /**
     * Carga los datos para la vista de dominios
     */
    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName === 'PortalDomains') {
                // Obtener el cliente del contacto
                $cliente = $this->contact->getCustomer(false);
                if (!$cliente instanceof \FacturaScripts\Dinamic\Model\Cliente || !$cliente->exists()) {
                    $view->cursor = [];
                    $view->count = 0;
                    return;
                }

                // Cargar el modelo extendido para acceder a la configuración de autologin
                $clienteExtendido = new \FacturaScripts\Plugins\DonDominio\Model\ClienteDonDominio();
                $clienteExtendido->loadFromCode($cliente->codcliente);

                // Usar la caché local para evitar llamadas repetidas
                try {
                    $service = new \FacturaScripts\Plugins\DonDominio\Lib\DomainSyncService();
                    $sessionKey = 'dondominio_portal_client_sync_' . $cliente->codcliente;
                    $lastSync = Session::get($sessionKey);
                    $shouldForceSync = empty($lastSync) || (time() - (int)$lastSync) >= 300;
                    if ($shouldForceSync) {
                        $service->syncClientIfNeeded($cliente->codcliente, true);
                        Session::set($sessionKey, time());
                    }

                    $contacts = $service->getClientContacts($cliente->codcliente);

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

                    $expiringDomains = \FacturaScripts\Plugins\DonDominio\Lib\DomainAlertService::getExpiringDomainsForClient($cliente->codcliente);
                    $view->expiring_domains = $expiringDomains;
                    $view->expiring_count = count($expiringDomains);
                } catch (\Exception $e) {
                    Tools::log()->error('Error cargando dominios en portal: ' . $e->getMessage());
                    $view->cursor = [];
                    $view->count = 0;
                    $view->expiring_domains = [];
                    $view->expiring_count = 0;
                }
            }
        };
    }
}
