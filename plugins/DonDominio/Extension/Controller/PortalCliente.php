<?php
/**
 * This file is part of the DonDominio plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\DonDominio\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\DonDominio\Lib\DomainApiService;

/**
 * Extensión del controlador PortalCliente para añadir funcionalidad de dominios.
 * Obtiene datos directamente de la API de DonDominio sin usar caché.
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
            if (!\FacturaScripts\Plugins\DonDominio\Lib\DonDominioConfig::isConfigured()) {
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

                $db = new DataBase();
                $sql = "SELECT COUNT(*) AS total
                        FROM attached_files_rel afr
                        INNER JOIN attached_files af ON afr.idfile = af.idfile
                        WHERE afr.model = 'Cliente'
                          AND afr.modelcode = " . $db->var2str($cliente->codcliente) . "
                          AND afr.observations = 'DOMICILIACION_AUTORENEW'
                          AND af.signed = true";
                $result = $db->select($sql);
                $view->has_signed_domiciliation = !empty($result) && (int)$result[0]['total'] > 0;

                $view->can_purchase_domain = false;

                // Restaurar timeout original
                set_time_limit((int)$originalTimeout);

            } catch (\Exception $e) {
                // Restaurar timeout en caso de error
                set_time_limit((int)$originalTimeout);

                $errorMsg = $e->getMessage();
                Tools::log()->error('dondominio-load-error', [
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

            $service = new DomainApiService();

            // Manejar acciones
            switch ($action) {
                case 'toggle-autorenew':
                    return $jsonResponse([
                        'success' => false,
                        'error' => 'Esta acción sólo puede realizarla un administrador desde el panel interno.'
                    ]);
            }

            return parent::execPreviousAction($action);
        };
    }

}
