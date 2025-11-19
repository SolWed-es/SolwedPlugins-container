<?php
/**
 * This file is part of the DonDominio plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\DonDominio\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\DonDominio\Lib\DomainApiService;

/**
 * Extensión del controlador EditCliente para mostrar dominios del cliente.
 * Obtiene datos en tiempo real desde la API de DonDominio.
 */
class EditCliente
{
    /**
     * Crea la vista de dominios en EditCliente.
     */
    public function createViews(): Closure
    {
        return function () {
            // Agregar vista de dominios
            $this->addHtmlView(
                'EditClienteDominios',
                'PortalDomains',
                'Cliente',
                'Dominios',
                'fa-solid fa-globe'
            );
        };
    }

    /**
     * Carga los datos de dominios desde la API.
     */
    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName !== 'EditClienteDominios') {
                return;
            }

            $cliente = $this->getModel();
            if (!$cliente || !$cliente->exists()) {
                $view->cursor = [];
                $view->count = 0;
                $view->error_message = 'No se encontró cliente asociado.';
                return;
            }

            // Verificar credenciales
            if (!\FacturaScripts\Plugins\DonDominio\Lib\DonDominioConfig::isConfigured()) {
                $view->cursor = [];
                $view->count = 0;
                $view->error_message = 'Credenciales de dominio no configuradas. Configure en Admin → Panel de control → Configuración.';
                $view->error_type = 'warning';
                return;
            }

            // Obtener dominios desde API
            $originalTimeout = ini_get('max_execution_time');

            try {
                // Establecer timeout máximo para esta operación
                set_time_limit(20);

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

                // Dominios próximos a expirar
                $expiringDomains = $service->getExpiringDomains($cliente->codcliente, 30);
                $view->expiring_domains = $expiringDomains;
                $view->expiring_count = count($expiringDomains);

                // Información de cuenta (solo para administradores)
                if ($this->user && $this->user->admin) {
                    $accountInfo = $service->getAccountInfo();
                    $view->account_info = $accountInfo;
                }

                $view->autorenew_contract_url = 'https://filedn.eu/litOB0SUT8q5aLOM933djFm/Contrato%20domiciliaci%C3%B3n-Solwed.pdf';
                $view->allow_autorenew_toggle = true;
                $view->has_signed_domiciliation = true;
                $view->can_purchase_domain = true;
                $view->hide_autorenew_warning = true;

                // Restaurar timeout original
                set_time_limit((int)$originalTimeout);

            } catch (\Exception $e) {
                // Restaurar timeout en caso de error
                set_time_limit((int)$originalTimeout);

                Tools::log()->error('dondominio-load-error', [
                    '%message%' => $e->getMessage(),
                    '%code%' => $cliente->codcliente,
                ]);

                $view->cursor = [];
                $view->count = 0;
                $view->expiring_domains = [];
                $view->expiring_count = 0;
                $view->error_message = 'Error al cargar los dominios desde la API.';
                $view->error_type = 'danger';
            }
        };
    }

    /**
     * Maneja las acciones AJAX de gestión de dominios.
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

            $service = new DomainApiService();

            // Manejar acciones de dominio
            switch ($action) {
                case 'get-authcode':
                    $domain = $this->request->request->get('domain', '');
                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio requerido']);
                    }

                    $authcode = $service->getDomainAuthCode($domain);
                    if (null === $authcode) {
                        return $jsonResponse(['success' => false, 'error' => 'No se pudo obtener el AuthCode']);
                    }

                    return $jsonResponse(['success' => true, 'authcode' => $authcode]);

                case 'get-history':
                    $domain = $this->request->request->get('domain', '');
                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio requerido']);
                    }

                    $history = $service->getDomainHistory($domain);
                    return $jsonResponse(['success' => true, 'history' => $history]);

                case 'test-dns':
                    $domain = $this->request->request->get('domain', '');
                    $type = $this->request->request->get('type', 'A');

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio requerido']);
                    }

                    $result = $service->performDnsTest($domain, $type);
                    if (null === $result) {
                        return $jsonResponse(['success' => false, 'error' => 'No se pudo realizar el test DNS']);
                    }

                    return $jsonResponse(['success' => true, 'result' => $result]);

                case 'update-nameservers':
                    $domain = $this->request->request->get('domain', '');
                    $nameservers = $this->request->request->get('nameservers', []);

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio requerido']);
                    }

                    // Si viene como string separado por comas o saltos de línea
                    if (is_string($nameservers)) {
                        $nameservers = preg_split('/[\r\n,]+/', $nameservers);
                        $nameservers = array_map('trim', $nameservers);
                        $nameservers = array_filter($nameservers);
                    }

                    $result = $service->updateDomainNameservers($domain, $nameservers);
                    return $jsonResponse($result);

                case 'renew-domain':
                    $domain = $this->request->request->get('domain', '');
                    $years = (int)$this->request->request->get('years', 1);

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio requerido']);
                    }

                    $result = $service->renewDomain($domain, $years);
                    return $jsonResponse($result);

                case 'resend-verification':
                    $domain = $this->request->request->get('domain', '');

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio requerido']);
                    }

                    $result = $service->resendVerificationEmail($domain);
                    return $jsonResponse($result);

                case 'toggle-autorenew':
                    $domain = $this->request->request->get('domain', '');
                    $enable = filter_var($this->request->request->get('enable', 'false'), FILTER_VALIDATE_BOOLEAN);

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio requerido']);
                    }

                    // Administradores: isAdmin=true (sin restricción de documento)
                    $result = $service->setAutoRenew($domain, $enable, '', true);
                    return $jsonResponse($result);

                case 'toggle-transfer-lock':
                    $domain = $this->request->request->get('domain', '');
                    $enable = filter_var($this->request->request->get('enable', 'false'), FILTER_VALIDATE_BOOLEAN);

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Dominio requerido']);
                    }

                    $result = $service->setTransferLock($domain, $enable);
                    return $jsonResponse($result);

                case 'check-domain':
                    $domain = $this->request->request->get('domain', '');

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Nombre de dominio requerido']);
                    }

                    $result = $service->checkDomainAvailability($domain);
                    return $jsonResponse($result);

                case 'purchase-domain':
                    $domain = $this->request->request->get('domain', '');
                    $years = (int)$this->request->request->get('years', 1);

                    if (empty($domain)) {
                        return $jsonResponse(['success' => false, 'error' => 'Nombre de dominio requerido']);
                    }

                    // Obtener código de cliente desde el modelo
                    $cliente = $this->getModel();
                    if (!$cliente || !$cliente->exists()) {
                        return $jsonResponse(['success' => false, 'error' => 'Cliente no encontrado']);
                    }

                    // Administradores: isAdmin=true (bypass de documento)
                    $result = $service->purchaseDomain($domain, $cliente->codcliente, $years, true);
                    return $jsonResponse($result);
            }

            return parent::execPreviousAction($action);
        };
    }
}
