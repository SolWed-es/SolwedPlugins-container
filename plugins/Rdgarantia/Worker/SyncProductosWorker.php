<?php

/**
 * Sync Productos Worker
 * Synchronizes products from Rdgarantia to FacturaScripts
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Worker;

use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaSyncMap;
use FacturaScripts\Core\Tools;

class SyncProductosWorker
{
    /** @var string */
    private $apiEndpoint = 'https://gestion.rdgarantia.com/?api=products';

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

    /**
     * Execute the sync process
     * @param bool $forceUpdate If true, updates existing records instead of skipping
     */
    public function execute(bool $forceUpdate = false): array
    {
        $this->forceUpdate = $forceUpdate;

        try {
            // Fetch products from Rdgarantia API
            $response = $this->fetchFromAPI();

            if (!$response || !isset($response['success']) || $response['success'] !== true) {
                $this->addError('Failed to fetch products from Rdgarantia API');
                return $this->results;
            }

            if (empty($response['data'])) {
                $this->addMessage('No products found in Rdgarantia');
                return $this->results;
            }

            // Process each product
            foreach ($response['data'] as $productData) {
                $this->processProduct($productData);
            }
        } catch (\Exception $e) {
            $this->addError('Exception: ' . $e->getMessage());
        }

        return $this->results;
    }

    /**
     * Fetch products from Rdgarantia API
     */
    private function fetchFromAPI(): ?array
    {
        $ch = curl_init($this->apiEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Process a single product
     */
    private function processProduct(array $productData): void
    {
        try {
            $rdgId = $productData['id'] ?? 0;

            if ($rdgId === 0) {
                $this->addError('Invalid product ID');
                return;
            }

            // Check if already synced
            $existingRef = RdgarantiaSyncMap::getFsCode($rdgId, 'producto');

            if ($existingRef) {
                // Already synced
                if (!$this->forceUpdate) {
                    // Skip if not forcing update
                    $this->results['skipped']++;
                    return;
                }

                // Force update: load existing product and update fields
                $producto = new Producto();
                if (!$producto->loadFromCode($existingRef)) {
                    $this->addError("Product reference {$existingRef} not found in FS for RDG ID {$rdgId}");
                    return;
                }

                $referencia = $existingRef;
                $isUpdate = true;
            } else {
                // New product: generate reference and create
                $referencia = $this->generateProductRef($productData);
                $producto = new Producto();
                $producto->referencia = $referencia;
                $isUpdate = false;
            }
            $producto->descripcion = $productData['name'] ?? '';
            $producto->nostock = true; // Service/warranty products don't have stock
            $producto->secompra = false; // Not a purchasable item
            $producto->sevende = true; // Is sellable
            $producto->tipo = 'servicios'; // Service type
            $producto->precio = 0; // Price will be set per contract
            $producto->observaciones = 'Vehicle Type: ' . ($productData['idVehiclesType'] ?? 'N/A') .
                ($productData['farmEquipmentType'] ? ' - ' . $productData['farmEquipmentType'] : '');

            // Fecha alta (only for new products)
            if (!$isUpdate && !empty($productData['dateAddFormatted'])) {
                $producto->fechaalta = $productData['dateAddFormatted'];
            }

            if (!$producto->save()) {
                $this->addError("Failed to save product: {$producto->descripcion}");
                return;
            }

            // Save mapping (only for new records)
            if (!$isUpdate) {
                $this->saveMapping($rdgId, $referencia, 'producto');
            }

            if ($isUpdate) {
                $this->results['updated']++;
                $this->addMessage("Product updated: {$producto->descripcion} ({$referencia})");
            } else {
                $this->results['success']++;
                $this->addMessage("Product synced: {$producto->descripcion} ({$referencia})");
            }
        } catch (\Exception $e) {
            $this->addError("Error processing product {$productData['id']}: " . $e->getMessage());
        }
    }

    /**
     * Generate unique product reference
     */
    private function generateProductRef(array $productData): string
    {
        // Use RDG prefix + product ID for uniqueness
        $base = 'RDG' . str_pad($productData['id'], 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        $ref = $base;
        $suffix = 1;
        while ($this->refExists($ref)) {
            $ref = $base . '-' . $suffix;
            $suffix++;
            if ($suffix > 99) {
                $ref = 'RDG' . uniqid();
                break;
            }
        }

        return $ref;
    }

    /**
     * Check if product reference already exists
     */
    private function refExists(string $ref): bool
    {
        $producto = new Producto();
        return $producto->loadFromCode($ref);
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
        $map->save();
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
