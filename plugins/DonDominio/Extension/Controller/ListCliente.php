<?php

namespace FacturaScripts\Plugins\DonDominio\Extension\Controller;

use Closure;
use FacturaScripts\Plugins\DonDominio\Lib\DomainListingService;

/**
 * Extensión del controlador ListCliente para añadir pestaña de dominios
 */
class ListCliente
{
    public function createViews(): Closure
    {
        return function() {
            // Añadir nueva vista para dominios (datos en tiempo real desde API)
            $this->addView('ListCliente', 'Domain', 'dondominio-domains-tab', 'fa-solid fa-globe');
            $this->addSearchFields('ListCliente', ['domain', 'codcliente', 'status']);
            
            // Añadir búsqueda rápida optimizada
            $this->customSearch = true;
            $this->addOrderBy('ListCliente', ['domain'], 'domain');
            $this->addOrderBy('ListCliente', ['codcliente'], 'codcliente');
            $this->addOrderBy('ListCliente', ['expires_at'], 'expires-at', 2);

            // Filtros
            $this->addFilterSelect('ListCliente', 'status', 'dondominio-status', 'status', [
                ['code' => 'active', 'description' => 'dondominio-status-active'],
                ['code' => 'pending', 'description' => 'dondominio-status-pending'],
                ['code' => 'expired', 'description' => 'dondominio-status-expired'],
                ['code' => 'suspended', 'description' => 'dondominio-status-suspended']
            ]);

            // Configurar la vista
            $view = $this->views['ListCliente'];
            $view->template = 'ListAllDomains.html.twig';

            // Paginación ultra-optimizada para evitar problemas de memoria
            $view->limit = 5;
            
            // Añadir optimización para consultas
            $this->setSettings('ListCliente', 'disableRowActions', true);
            $this->setSettings('ListCliente', 'printButton', false);
            $this->setSettings('ListCliente', 'csvButton', false);

            // Añadir botón para abrir cliente
            $this->addButton('ListCliente', [
                'action' => 'open-client',
                'icon' => 'fa-solid fa-user',
                'label' => 'open-client',
                'type' => 'action'
            ]);
        };
    }

    public function loadData(): Closure
    {
        return function($viewName, $view) {
            if ($viewName !== 'ListCliente' || $view->modelName !== 'Domain') {
                return;
            }

            try {
                $filters = [
                    'query' => (string) $this->request->query->get('query', ''),
                    'status' => (string) $this->request->request->get($viewName . '_status', ''),
                ];

                $service = new DomainListingService();
                $result = $service->listDomains($filters, $view->getOffset(), (int) $view->limit);

                $view->cursor = $result['items'];
                $view->count = $result['total'];
                $view->global_expiring_count = 0;
                $view->result_limit_reached = !empty($result['has_more']);
                $view->result_limit_value = (int) $view->limit;
            } catch (\Throwable $exception) {
                \FacturaScripts\Core\Tools::log()->error('Error cargando dominios desde API: ' . $exception->getMessage());
                $view->cursor = [];
                $view->count = 0;
                $view->global_expiring_count = 0;
                $view->result_limit_reached = false;
                $view->result_limit_value = 0;
            }
        };
    }

    public function execPreviousAction(): Closure
    {
        return function($action) {
            if ($action === 'open-client') {
                $codes = $this->request->request->get('codes', '');
                if (empty($codes)) {
                    return true;
                }

                $code = explode(',', $codes)[0];

                // Para datos de API, el código es el codcliente, no el ID del dominio
                // Buscar el cliente correspondiente
                $dataBase = new \FacturaScripts\Core\Base\DataBase\DataBase();
                $sql = "SELECT codcliente FROM clientes WHERE codcliente = '" . $dataBase->escapeString($code) . "' LIMIT 1";
                $result = $dataBase->select($sql);

                if (!empty($result)) {
                    $this->redirect('EditCliente?code=' . $result[0]['codcliente'] . '&activetab=EditClienteDominio');
                    return false;
                }

                return true;
            }

            return false;
        };
    }

}
