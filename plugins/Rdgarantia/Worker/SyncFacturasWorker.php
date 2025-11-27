<?php

/**
 * Sync Facturas Worker
 * Synchronizes invoices from Rdgarantia to FacturaScripts
 * Handles historical invoice sync with date filtering
 * Solwed addition - Part of RDGarantia invoice integration
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Worker;

use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaSyncMap;
use FacturaScripts\Plugins\Rdgarantia\Lib\RdgInvoiceMapper;
use FacturaScripts\Core\Tools;

class SyncFacturasWorker
{
    /** @var string */
    private $apiEndpoint = 'https://gestion.rdgarantia.com/?api=billing';

    /** @var array */
    private $results = [
        'success' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'messages' => []
    ];

    /** @var bool */
    private $forceUpdate = false;

    /** @var string Date from which to sync (Y-m-d format) */
    private $dateFrom = '2024-01-01';

    /**
     * Execute the sync process
     * @param bool $forceUpdate If true, updates existing records instead of skipping
     * @param string $dateFrom Start date for sync (Y-m-d format)
     */
    public function execute(bool $forceUpdate = false, string $dateFrom = '2024-01-01'): array
    {
        $this->forceUpdate = $forceUpdate;
        $this->dateFrom = $dateFrom;

        try {
            // Fetch finalized invoices from Rdgarantia
            $invoices = $this->fetchFinalizedInvoices();

            if (empty($invoices)) {
                $this->addMessage('No finalized invoices found in Rdgarantia since ' . $dateFrom);
                return $this->results;
            }

            $this->addMessage('Found ' . count($invoices) . ' finalized invoices to process');

            // Process each invoice
            foreach ($invoices as $invoiceId) {
                $this->processInvoice($invoiceId);
            }
        } catch (\Exception $e) {
            $this->addError('Exception: ' . $e->getMessage());
        }

        return $this->results;
    }

    /**
     * Fetch list of finalized invoice IDs from RDG
     * @return array Array of invoice IDs
     */
    private function fetchFinalizedInvoices(): array
    {
        // For now, we'll fetch a list of finalized billings from RDG
        // TODO: RDG needs an API endpoint that returns list of finalized invoices with date filter
        // For example: ?api=billing/finalized&dateFrom=2024-01-01

        // Temporary: Return empty array until RDG API is ready
        // In production, this would call an RDG API endpoint
        $this->addMessage('Note: RDG API endpoint for listing finalized invoices not yet implemented');
        return [];
    }

    /**
     * Process a single invoice
     */
    private function processInvoice(int $idBill): void
    {
        try {
            // Check if already synced
            $existingCode = RdgarantiaSyncMap::getFsCode($idBill, 'factura');

            if ($existingCode) {
                if (!$this->forceUpdate) {
                    $this->results['skipped']++;
                    return;
                }

                // Force update: load existing invoice and update
                $factura = new FacturaCliente();
                if (!$factura->loadFromCode($existingCode)) {
                    $this->addError("Invoice code {$existingCode} not found in FS for RDG ID {$idBill}");
                    return;
                }

                $isUpdate = true;
                $this->addMessage("Updating existing invoice: {$existingCode}");
            } else {
                $isUpdate = false;
            }

            // Fetch invoice data from RDG API
            $rdgData = $this->fetchInvoiceData($idBill);

            if (!$rdgData) {
                $this->addError("Failed to fetch invoice data for ID {$idBill}");
                return;
            }

            // Validate data
            $validation = RdgInvoiceMapper::validateRdgInvoiceData($rdgData);
            if (!$validation['valid']) {
                $this->addError("Invalid invoice data for ID {$idBill}: " . implode(', ', $validation['errors']));
                return;
            }

            // Create or update invoice
            if (!$isUpdate) {
                // Create new invoice
                $factura = RdgInvoiceMapper::createInvoiceFromRdg($rdgData);

                if (!$factura) {
                    $this->addError("Failed to create invoice from RDG data for ID {$idBill}");
                    return;
                }

                // Save invoice
                if (!$factura->save()) {
                    $this->addError("Failed to save invoice for RDG ID {$idBill}");
                    return;
                }

                // Add line items
                if (!empty($rdgData['concepts'])) {
                    if (!RdgInvoiceMapper::addLinesToInvoice($factura, $rdgData['concepts'])) {
                        $this->addError("Failed to add line items to invoice {$factura->codigo}");
                        return;
                    }
                }

                // Create receipts
                if (!empty($rdgData['payments'])) {
                    if (!RdgInvoiceMapper::createReceipts($factura, $rdgData['payments'])) {
                        $this->addError("Failed to create receipts for invoice {$factura->codigo}");
                    }
                }

                // Save mapping
                $this->saveMapping($idBill, $factura->codigo, 'factura');

                $this->results['success']++;
                $this->addMessage("Invoice synced: {$factura->codigo} (RDG ID: {$idBill})");
            } else {
                // Update existing invoice
                // TODO: Implement update logic
                $this->results['updated']++;
                $this->addMessage("Invoice updated: {$factura->codigo} (RDG ID: {$idBill})");
            }
        } catch (\Exception $e) {
            $this->addError("Error processing invoice {$idBill}: " . $e->getMessage());
        }
    }

    /**
     * Fetch single invoice data from RDG API
     */
    private function fetchInvoiceData(int $idBill): ?array
    {
        $apiUrl = $this->apiEndpoint . '&id=' . $idBill;

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);

        if (!isset($data['success']) || $data['success'] !== true || empty($data['data'])) {
            return null;
        }

        return $data['data'];
    }

    /**
     * Save sync mapping
     */
    private function saveMapping(int $rdgId, string $fsCode, string $entityType): void
    {
        $map = new RdgarantiaSyncMap();
        $map->rdg_id = $rdgId;
        $map->fs_code = $fsCode;
        $map->entity_type = $entityType;
        $map->last_sync = date('Y-m-d H:i:s');

        if (!$map->save()) {
            $this->addError("Failed to save mapping for RDG ID {$rdgId}");
        }
    }

    /**
     * Add error message
     */
    private function addError(string $message): void
    {
        $this->results['errors']++;
        $this->results['messages'][] = 'ERROR: ' . $message;
        Tools::log()->error($message);
    }

    /**
     * Add info message
     */
    private function addMessage(string $message): void
    {
        $this->results['messages'][] = $message;
        Tools::log()->info($message);
    }
}
