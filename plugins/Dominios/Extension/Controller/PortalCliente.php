<?php
/**
 * This file is part of the Dominios plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\Dominios\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Dominios\Lib\DomainApiService;

/**
 * Extensión del controlador PortalCliente para añadir funcionalidad de dominios.
 * Obtiene datos directamente de la API del proveedor de dominios sin usar caché.
 */
class PortalCliente
{
    /**
     * Añade la vista de dominios al crear las vistas.
     */
    public function createViews(): Closure
    {
        return function () {
            $this->addHtmlView(
                'Domains',
                'PortalDomains',
                'Cliente',
                'Dominios',
                'fa-solid fa-globe'
            );
        };
    }

    /**
     * Carga los datos para la vista de dominios desde la API.
     */
    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName !== 'Domains') {
                return;
            }

            // Obtener el cliente del contacto
            $cliente = $this->contact->getCustomer(false);
            if (!$cliente instanceof \FacturaScripts\Dinamic\Model\Cliente || !$cliente->exists()) {
                $view->cursor = [];
                $view->count = 0;
                $view->error_message = 'No se encontró cliente asociado.';
                return;
            }

            // Verificar credenciales configuradas
            if (!\FacturaScripts\Plugins\Dominios\Lib\DomainConfig::isConfigured()) {
                $view->cursor = [];
                $view->count = 0;
                $view->error_message = 'Credenciales de dominio no configuradas. Configure en Admin → Panel de control → Configuración.';
                $view->error_type = 'warning';
                return;
            }

            // Obtener dominios directamente de la API
            $originalTimeout = ini_get('max_execution_time');

            try {
                // Establecer timeout máximo para esta operación
                set_time_limit(30);

                $service = new DomainApiService();
                $contacts = $service->getClientContacts($cliente->codcliente);

                if (empty($contacts)) {
                    $view->cursor = [];
                    $view->count = 0;
                    $view->error_message = 'No hay dominios registrados para este cliente.';
                    $view->error_type = 'info';

                    // Restaurar timeout original
                    set_time_limit((int)$originalTimeout);
                    return;
                }

                $view->cursor = $contacts;
                $view->count = count($contacts);
                $view->error_message = null;

                // Obtener dominios próximos a expirar
                $expiringDomains = $service->getExpiringDomains($cliente->codcliente, 30);
                $view->expiring_domains = $expiringDomains;
                $view->expiring_count = count($expiringDomains);
                $view->autorenew_contract_url = 'https://filedn.eu/litOB0SUT8q5aLOM933djFm/Contrato%20domiciliaci%C3%B3n-Solwed.pdf';
                $view->allow_autorenew_toggle = false;

                // ELIMINADO: Consulta a BD para verificar contratos firmados
                // Plugin stateless - renovación automática deshabilitada
                $view->has_signed_domiciliation = false;

                $view->can_purchase_domain = false;

                // Restaurar timeout original
                set_time_limit((int)$originalTimeout);

            } catch (\Exception $e) {
                // Restaurar timeout en caso de error
                set_time_limit((int)$originalTimeout);

                $errorMsg = $e->getMessage();
                Tools::log()->error('domain-load-error', [
                    '%message%' => $errorMsg,
                    '%code%' => $cliente->codcliente,
                    '%trace%' => $e->getTraceAsString(),
                ]);

                $view->cursor = [];
                $view->count = 0;
                $view->expiring_domains = [];
                $view->expiring_count = 0;
                $view->error_message = 'Error al cargar los dominios desde la API: ' . $errorMsg;
                $view->error_type = 'danger';
            }
        };
    }

    /**
     * Maneja las acciones AJAX para gestión de dominios.
     */
    public function execPreviousAction(): Closure
    {
        return function ($action): bool {
            // Helper para respuesta JSON
            $jsonResponse = function(array $data): bool {
                $this->setTemplate(false);
                $this->response->setContent(json_encode($data));
                $this->response->headers->set('Content-Type', 'application/json');
                return false;
            };

            // Helper para verificar propiedad del dominio
            $verifyDomainOwnership = function(string $domain, string $codcliente) use ($jsonResponse): bool {
                try {
                    $service = new DomainApiService();
                    $contacts = $service->getClientContacts($codcliente);

                    foreach ($contacts as $contact) {
                        if (empty($contact['domains'])) {
                            continue;
                        }

                        foreach ($contact['domains'] as $domainData) {
                            if (($domainData['name'] ?? '') === $domain) {
                                return true;
                            }
                        }
                    }

                    return false;
                } catch (\Throwable $e) {
                    Tools::log()->error('domain-ownership-check-error', [
                        '%domain%' => $domain,
                        '%codcliente%' => $codcliente,
                        '%message%' => $e->getMessage(),
                    ]);
                    return false;
                }
            };

            $service = new DomainApiService();

            // Obtener el cliente del contacto
            $cliente = $this->contact->getCustomer(false);
            if (!$cliente instanceof \FacturaScripts\Dinamic\Model\Cliente || !$cliente->exists()) {
                return $jsonResponse([
                    'success' => false,
                    'error' => 'Cliente no encontrado'
                ]);
            }

            // Manejar acciones
            switch ($action) {
                case 'get-authcode':
                    $domain = $this->request->request->get('domain', '');
                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio no especificado']);
                    }

                    // Verificar que el dominio pertenece al cliente
                    if (!$verifyDomainOwnership($domain, $cliente->codcliente)) {
                        return $jsonResponse(['success' => false, 'error' => 'No tienes permisos para este dominio']);
                    }

                    $authcode = $service->getDomainAuthCode($domain);
                    if (null === $authcode) {
                        return $jsonResponse(['success' => false, 'error' => 'No se pudo obtener el AuthCode']);
                    }

                    return $jsonResponse([
                        'success' => true,
                        'authcode' => $authcode,
                        'domain' => $domain
                    ]);

                case 'update-nameservers':
                    $domain = $this->request->request->get('domain', '');
                    $nameserversRaw = $this->request->request->get('nameservers', '');

                    if (empty($domain) || empty($nameserversRaw)) {
                        return $jsonResponse(['success' => false, 'error' => 'Parámetros inválidos']);
                    }

                    // Verificar que el dominio pertenece al cliente
                    if (!$verifyDomainOwnership($domain, $cliente->codcliente)) {
                        return $jsonResponse(['success' => false, 'error' => 'No tienes permisos para este dominio']);
                    }

                    // Parsear nameservers (separados por saltos de línea o comas)
                    $nameservers = array_filter(
                        array_map('trim', preg_split('/[\r\n,]+/', $nameserversRaw)),
                        fn($ns) => !empty($ns)
                    );

                    $result = $service->updateDomainNameservers($domain, $nameservers);
                    return $jsonResponse($result);

                case 'renew-domain':
                    $domain = $this->request->request->get('domain', '');
                    $years = (int)$this->request->request->get('years', 1);

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio no especificado']);
                    }

                    // Verificar que el dominio pertenece al cliente
                    if (!$verifyDomainOwnership($domain, $cliente->codcliente)) {
                        return $jsonResponse(['success' => false, 'error' => 'No tienes permisos para este dominio']);
                    }

                    $result = $service->renewDomain($domain, $years);
                    return $jsonResponse($result);

                case 'toggle-autorenew':
                    return $jsonResponse([
                        'success' => false,
                        'error' => 'Esta acción sólo puede realizarla un administrador desde el panel interno.'
                    ]);

                case 'toggle-transfer-lock':
                    $domain = $this->request->request->get('domain', '');
                    $enable = filter_var($this->request->request->get('enable', false), FILTER_VALIDATE_BOOLEAN);

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio no especificado']);
                    }

                    // Verificar que el dominio pertenece al cliente
                    if (!$verifyDomainOwnership($domain, $cliente->codcliente)) {
                        return $jsonResponse(['success' => false, 'error' => 'No tienes permisos para este dominio']);
                    }

                    $result = $service->setTransferLock($domain, $enable);
                    return $jsonResponse($result);

                case 'check-domain':
                    $domain = $this->request->request->get('domain', '');
                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio no especificado']);
                    }

                    $result = $service->checkDomainAvailability($domain);
                    return $jsonResponse($result);

                case 'purchase-domain':
                    $domain = $this->request->request->get('domain', '');
                    $years = (int)$this->request->request->get('years', 1);

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio no especificado']);
                    }

                    $result = $service->purchaseDomain($domain, $cliente->codcliente, $years, false);
                    return $jsonResponse($result);
            }

            // No es una acción de dominios, continuar con el flujo normal
            return true;
        };
    }
}
