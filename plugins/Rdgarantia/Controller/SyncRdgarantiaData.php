<?php

/**
 * Sync Rdgarantia Data Controller
 * Manual sync interface for Agents and Sellers
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Controller;

use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Plugins\Rdgarantia\Worker\SyncAgentesWorker;
use FacturaScripts\Plugins\Rdgarantia\Worker\SyncClientesWorker;
use FacturaScripts\Plugins\Rdgarantia\Worker\SyncProductosWorker;
use FacturaScripts\Plugins\Rdgarantia\Worker\SyncFacturasWorker;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaSyncMap;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaProvinceMap;
use FacturaScripts\Core\Tools;

class SyncRdgarantiaData extends PanelController
{
    /**
     * Returns basic page attributes
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'Sync Rdgarantia Data';
        $data['icon'] = 'fas fa-sync';
        return $data;
    }

    /**
     * Load views and data
     */
    protected function createViews()
    {
        $this->setTemplate('SyncRdgarantiaData');
    }

    /**
     * Load data for views
     */
    protected function loadData($viewName, $view)
    {
        // No dynamic data loading needed for this view
    }

    /**
     * Run actions on page load
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'sync-agents':
                $this->syncAgents();
                return true;

            case 'sync-clients':
                $this->syncClients();
                return true;

            case 'sync-products':
                $this->syncProducts();
                return true;

            case 'force-resync-agents':
                $this->forceResyncAgents();
                return true;

            case 'force-resync-clients':
                $this->forceResyncClients();
                return true;

            case 'force-resync-products':
                $this->forceResyncProducts();
                return true;

            case 'sync-all':
                $this->syncAll();
                return true;

            case 'sync-invoices':
                $this->syncInvoices();
                return true;

            case 'force-resync-invoices':
                $this->forceResyncInvoices();
                return true;

            case 'load-provinces':
                $this->loadProvinces();
                return true;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Sync agents from Rdgarantia
     */
    private function syncAgents(): void
    {
        $worker = new SyncAgentesWorker();
        $results = $worker->execute(false);

        Tools::log()->info(
            "Agents sync completed: {$results['success']} new, {$results['updated']} updated, " .
                "{$results['skipped']} skipped, {$results['errors']} errors"
        );

        foreach ($results['messages'] as $message) {
            if (strpos($message, 'ERROR') === 0) {
                Tools::log()->error($message);
            } else {
                Tools::log()->info($message);
            }
        }
    }

    /**
     * Force re-sync agents (update existing records)
     */
    private function forceResyncAgents(): void
    {
        $worker = new SyncAgentesWorker();
        $results = $worker->execute(true);

        Tools::log()->warning(
            "Agents FORCE re-sync completed: {$results['success']} new, {$results['updated']} updated, " .
                "{$results['skipped']} skipped, {$results['errors']} errors"
        );

        foreach ($results['messages'] as $message) {
            if (strpos($message, 'ERROR') === 0) {
                Tools::log()->error($message);
            } else {
                Tools::log()->info($message);
            }
        }
    }

    /**
     * Sync clients from Rdgarantia
     */
    private function syncClients(): void
    {
        $worker = new SyncClientesWorker();
        $results = $worker->execute(false);

        Tools::log()->info(
            "Clients sync completed: {$results['success']} new, {$results['updated']} updated, " .
                "{$results['skipped']} skipped, {$results['errors']} errors"
        );

        foreach ($results['messages'] as $message) {
            if (strpos($message, 'ERROR') === 0) {
                Tools::log()->error($message);
            } else {
                Tools::log()->info($message);
            }
        }
    }

    /**
     * Force re-sync clients (update existing records)
     */
    private function forceResyncClients(): void
    {
        $worker = new SyncClientesWorker();
        $results = $worker->execute(true);

        Tools::log()->warning(
            "Clients FORCE re-sync completed: {$results['success']} new, {$results['updated']} updated, " .
                "{$results['skipped']} skipped, {$results['errors']} errors"
        );

        foreach ($results['messages'] as $message) {
            if (strpos($message, 'ERROR') === 0) {
                Tools::log()->error($message);
            } else {
                Tools::log()->info($message);
            }
        }
    }

    /**
     * Sync products from Rdgarantia
     */
    private function syncProducts(): void
    {
        $worker = new SyncProductosWorker();
        $results = $worker->execute(false);

        Tools::log()->info(
            "Products sync completed: {$results['success']} new, {$results['updated']} updated, " .
                "{$results['skipped']} skipped, {$results['errors']} errors"
        );

        foreach ($results['messages'] as $message) {
            if (strpos($message, 'ERROR') === 0) {
                Tools::log()->error($message);
            } else {
                Tools::log()->info($message);
            }
        }
    }

    /**
     * Force re-sync products (update existing records)
     */
    private function forceResyncProducts(): void
    {
        $worker = new SyncProductosWorker();
        $results = $worker->execute(true);

        Tools::log()->warning(
            "Products FORCE re-sync completed: {$results['success']} new, {$results['updated']} updated, " .
                "{$results['skipped']} skipped, {$results['errors']} errors"
        );

        foreach ($results['messages'] as $message) {
            if (strpos($message, 'ERROR') === 0) {
                Tools::log()->error($message);
            } else {
                Tools::log()->info($message);
            }
        }
    }

    /**
     * Sync agents, clients and products
     */
    private function syncAll(): void
    {
        $this->syncAgents();
        $this->syncClients();
        $this->syncProducts();
    }

    /**
     * Sync invoices from Rdgarantia
     */
    private function syncInvoices(): void
    {
        $worker = new SyncFacturasWorker();
        $results = $worker->execute(false, '2024-01-01');

        Tools::log()->info(
            "Invoices sync completed: {$results['success']} new, {$results['updated']} updated, " .
                "{$results['skipped']} skipped, {$results['errors']} errors"
        );

        foreach ($results['messages'] as $message) {
            if (strpos($message, 'ERROR') === 0) {
                Tools::log()->error($message);
            } else {
                Tools::log()->info($message);
            }
        }
    }

    /**
     * Force re-sync invoices (update existing records)
     */
    private function forceResyncInvoices(): void
    {
        $worker = new SyncFacturasWorker();
        $results = $worker->execute(true, '2024-01-01');

        Tools::log()->warning(
            "Invoices FORCE re-sync completed: {$results['success']} new, {$results['updated']} updated, " .
                "{$results['skipped']} skipped, {$results['errors']} errors"
        );

        foreach ($results['messages'] as $message) {
            if (strpos($message, 'ERROR') === 0) {
                Tools::log()->error($message);
            } else {
                Tools::log()->info($message);
            }
        }
    }

    /**
     * Load province mappings from JSON file
     */
    private function loadProvinces(): void
    {
        if (RdgarantiaProvinceMap::loadMappingsFromFile()) {
            Tools::log()->info('Province mappings loaded successfully');
        } else {
            Tools::log()->error('Failed to load province mappings');
        }
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats(): array
    {
        $stats = [
            'agents_synced' => 0,
            'clients_synced' => 0,
            'products_synced' => 0,
            'invoices_synced' => 0,
            'last_agent_sync' => null,
            'last_client_sync' => null,
            'last_product_sync' => null,
            'last_invoice_sync' => null
        ];

        try {
            $db = $this->dataBase;

            // Count synced agents
            $sql = "SELECT COUNT(*) as total FROM " . RdgarantiaSyncMap::tableName() . " WHERE entity_type = 'agente'";
            $result = $db->select($sql);
            if (!empty($result)) {
                $stats['agents_synced'] = (int)$result[0]['total'];
            }

            // Count synced clients
            $sql = "SELECT COUNT(*) as total FROM " . RdgarantiaSyncMap::tableName() . " WHERE entity_type = 'cliente'";
            $result = $db->select($sql);
            if (!empty($result)) {
                $stats['clients_synced'] = (int)$result[0]['total'];
            }

            // Count synced products
            $sql = "SELECT COUNT(*) as total FROM " . RdgarantiaSyncMap::tableName() . " WHERE entity_type = 'producto'";
            $result = $db->select($sql);
            if (!empty($result)) {
                $stats['products_synced'] = (int)$result[0]['total'];
            }

            // Last agent sync
            $sql = "SELECT MAX(last_sync) as last_sync FROM " . RdgarantiaSyncMap::tableName() . " WHERE entity_type = 'agente'";
            $result = $db->select($sql);
            if (!empty($result) && !empty($result[0]['last_sync'])) {
                $stats['last_agent_sync'] = $result[0]['last_sync'];
            }

            // Last client sync
            $sql = "SELECT MAX(last_sync) as last_sync FROM " . RdgarantiaSyncMap::tableName() . " WHERE entity_type = 'cliente'";
            $result = $db->select($sql);
            if (!empty($result) && !empty($result[0]['last_sync'])) {
                $stats['last_client_sync'] = $result[0]['last_sync'];
            }

            // Last product sync
            $sql = "SELECT MAX(last_sync) as last_sync FROM " . RdgarantiaSyncMap::tableName() . " WHERE entity_type = 'producto'";
            $result = $db->select($sql);
            if (!empty($result) && !empty($result[0]['last_sync'])) {
                $stats['last_product_sync'] = $result[0]['last_sync'];
            }

            // Count synced invoices
            $sql = "SELECT COUNT(*) as total FROM " . RdgarantiaSyncMap::tableName() . " WHERE entity_type = 'factura'";
            $result = $db->select($sql);
            if (!empty($result)) {
                $stats['invoices_synced'] = (int)$result[0]['total'];
            }

            // Last invoice sync
            $sql = "SELECT MAX(last_sync) as last_sync FROM " . RdgarantiaSyncMap::tableName() . " WHERE entity_type = 'factura'";
            $result = $db->select($sql);
            if (!empty($result) && !empty($result[0]['last_sync'])) {
                $stats['last_invoice_sync'] = $result[0]['last_sync'];
            }
        } catch (\Exception $e) {
            Tools::log()->error($e->getMessage());
        }

        return $stats;
    }
}
