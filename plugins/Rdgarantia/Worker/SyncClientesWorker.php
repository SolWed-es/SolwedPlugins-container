<?php

/**
 * Sync Clientes Worker
 * Synchronizes sellers/clients from Rdgarantia to FacturaScripts
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Worker;

use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Contacto;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaSyncMap;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaProvinceMap;
use FacturaScripts\Core\Tools;

class SyncClientesWorker
{
    /** @var string */
    private $apiEndpoint = 'https://gestion.rdgarantia.com/?api=sellers';

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
            // Fetch sellers from Rdgarantia API
            $response = $this->fetchFromAPI();

            if (!$response || !isset($response['success']) || $response['success'] !== true) {
                $this->addError('Failed to fetch sellers from Rdgarantia API');
                return $this->results;
            }

            if (empty($response['data'])) {
                $this->addMessage('No sellers found in Rdgarantia');
                return $this->results;
            }

            // Process each seller
            foreach ($response['data'] as $sellerData) {
                $this->processSeller($sellerData);
            }
        } catch (\Exception $e) {
            $this->addError('Exception: ' . $e->getMessage());
        }

        return $this->results;
    }

    /**
     * Fetch sellers from Rdgarantia API
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
     * Process a single seller
     */
    private function processSeller(array $sellerData): void
    {
        try {
            $rdgId = $sellerData['id'] ?? 0;

            if ($rdgId === 0) {
                $this->addError('Invalid seller ID');
                return;
            }

            // Check if already synced
            $existingCode = RdgarantiaSyncMap::getFsCode($rdgId, 'cliente');

            if ($existingCode) {
                // Already synced
                if (!$this->forceUpdate) {
                    // Skip if not forcing update
                    $this->results['skipped']++;
                    return;
                }

                // Force update: load existing client and update fields
                $cliente = new Cliente();
                if (!$cliente->loadFromCode($existingCode)) {
                    $this->addError("Client code {$existingCode} not found in FS for RDG ID {$rdgId}");
                    return;
                }

                $codcliente = $existingCode;
                $isUpdate = true;
            } else {
                // New client: generate code and create
                $codcliente = $this->generateClientCode($sellerData);
                $cliente = new Cliente();
                $cliente->codcliente = $codcliente;
                $isUpdate = false;
            }
            // Solwed addition - Use 'name' as primary field since 'businessName' is often empty
            $cliente->nombre = $sellerData['name'] ?? ($sellerData['businessName'] ?: $sellerData['fullName']);
            $cliente->razonsocial = $sellerData['name'] ?? '';
            $cliente->cifnif = $sellerData['cifNif'] ?? '';
            // Solwed addition - Check if it's a physical person (has surname)
            $cliente->personafisica = !empty($sellerData['surname']);
            $cliente->email = $sellerData['email'] ?? '';
            $cliente->telefono1 = $sellerData['phone'] ?? '';
            $cliente->telefono2 = $sellerData['administrationPhone'] ?? '';
            $cliente->fax = $sellerData['fax'] ?? '';
            $cliente->debaja = !($sellerData['active'] ?? true);

            if (!$isUpdate) {
                $cliente->fechaalta = $sellerData['creationDateFormatted'] ?? date('Y-m-d');
            }

            // Map agent if exists
            if (!empty($sellerData['idAgent'])) {
                $agentCode = RdgarantiaSyncMap::getFsCode($sellerData['idAgent'], 'agente');
                if ($agentCode) {
                    $cliente->codagente = $agentCode;
                }
            }

            // Custom Rdgarantia fields
            $cliente->rdg_import_ref = (string)$rdgId;
            $cliente->rdg_multibrand = $sellerData['multiBrand'] ?? '';
            $cliente->rdg_owner = $sellerData['owner'] ?? '';
            $cliente->rdg_sales_responsible = $sellerData['salesResponsible'] ?? '';
            $cliente->rdg_admin_responsible = $sellerData['administrationResponsible'] ?? '';
            $cliente->rdg_workshop_responsible = $sellerData['workshopResponsible'] ?? '';
            $cliente->rdg_admin_email = $sellerData['emailAdministration'] ?? '';
            $cliente->rdg_workshop_phone = $sellerData['workshopPhone'] ?? '';
            $cliente->rdg_iva_percent = $sellerData['ivaPercent'] ?? 21;
            $cliente->rdg_latitude = $sellerData['latitude'] ?? '';
            $cliente->rdg_longitude = $sellerData['longitude'] ?? '';
            $cliente->rdg_last_sync = date('Y-m-d H:i:s');

            if (!$cliente->save()) {
                $this->addError("Failed to save client: {$cliente->nombre}");
                return;
            }

            // Create/Update Contacto for address
            if (!empty($sellerData['address'])) {
                $this->createContacto($cliente, $sellerData);
            }

            // Save mapping (only for new records)
            if (!$isUpdate) {
                $this->saveMapping($rdgId, $codcliente, 'cliente');
            }

            if ($isUpdate) {
                $this->results['updated']++;
                $this->addMessage("Client updated: {$cliente->nombre} ({$codcliente})");
            } else {
                $this->results['success']++;
                $this->addMessage("Client synced: {$cliente->nombre} ({$codcliente})");
            }
        } catch (\Exception $e) {
            $this->addError("Error processing seller {$sellerData['id']}: " . $e->getMessage());
        }
    }

    /**
     * Generate unique client code
     */
    private function generateClientCode(array $sellerData): string
    {
        // Try CIF/NIF first (cleaned, first 6 chars)
        if (!empty($sellerData['cifNif'])) {
            $base = preg_replace('/[^A-Z0-9]/i', '', strtoupper($sellerData['cifNif']));
            $base = substr($base, 0, 8);
        } else {
            // Solwed addition - Use 'name' field (business name) or name initials + ID
            $name = $sellerData['name'] ?? '';
            $nameClean = preg_replace('/[^A-Z0-9]/i', '', strtoupper($name));

            if (!empty($nameClean) && strlen($nameClean) > 3) {
                // Use business/person name (first 5-6 chars)
                $base = substr($nameClean, 0, 6) . $sellerData['id'];
            } else {
                // Very short name or empty, use initials + ID
                $surname = $sellerData['surname'] ?? '';
                $surnameClean = preg_replace('/[^A-Z0-9]/i', '', strtoupper($surname));
                $base = substr($nameClean, 0, 2) . substr($surnameClean, 0, 2) . $sellerData['id'];
            }
        }

        // Remove any remaining invalid characters and limit to 10 chars
        $base = substr(preg_replace('/[^A-Z0-9]/i', '', $base), 0, 10);

        // Ensure we have at least something
        if (empty($base)) {
            $base = 'CL' . $sellerData['id'];
        }

        // Ensure uniqueness
        $code = $base;
        $suffix = 1;
        while ($this->codeExists($code)) {
            $code = substr($base, 0, 8) . $suffix;
            $suffix++;
            if ($suffix > 99) {
                $code = 'C' . uniqid();
                break;
            }
        }

        return substr($code, 0, 10);
    }

    /**
     * Check if client code already exists
     */
    private function codeExists(string $code): bool
    {
        $cliente = new Cliente();
        return $cliente->load($code);
    }

    /**
     * Create or update Contacto record for client address
     */
    private function createContacto(Cliente $cliente, array $sellerData): void
    {
        // Prepare province name from RdgarantiaProvinceMap
        if (!empty($sellerData['provinceId'])) {
            $provinceId = (int)$sellerData['provinceId'];
            $provinceName = RdgarantiaProvinceMap::getProvinceName($provinceId);
            if (!empty($provinceName)) {
                $sellerData['province'] = $provinceName;
            }
        }
        // Check if client already has a linked contact
        if (!empty($cliente->idcontactofact)) {
            // Load existing contact and update it
            $contacto = new Contacto();
            if ($contacto->loadFromCode($cliente->idcontactofact)) {
                // Update existing contact
                $contacto->nombre = $sellerData['name'] ?? '';
                $contacto->apellidos = $sellerData['surname'] ?? '';
                $contacto->empresa = $sellerData['businessName'] ?? '';
                $contacto->email = $sellerData['email'] ?? '';
                $contacto->telefono1 = $sellerData['phone'] ?? '';
                $contacto->telefono2 = $sellerData['administrationPhone'] ?? '';
                $contacto->cifnif = $sellerData['cifNif'] ?? '';
                $contacto->direccion = $sellerData['address'] ?? '';
                $contacto->codpostal = $sellerData['postalCode'] ?? '';
                $contacto->ciudad = $sellerData['location'] ?? '';
                $contacto->provincia = $sellerData['province'] ?? '';
                $contacto->save();
                return;
            }
        }

        // No existing contact, create new one
        $contacto = new Contacto();
        $contacto->nombre = $sellerData['name'] ?? '';
        $contacto->apellidos = $sellerData['surname'] ?? '';
        $contacto->empresa = $sellerData['businessName'] ?? '';
        $contacto->email = $sellerData['email'] ?? '';
        $contacto->telefono1 = $sellerData['phone'] ?? '';
        $contacto->telefono2 = $sellerData['administrationPhone'] ?? '';
        $contacto->cifnif = $sellerData['cifNif'] ?? '';
        $contacto->direccion = $sellerData['address'] ?? '';
        $contacto->codpostal = $sellerData['postalCode'] ?? '';
        $contacto->ciudad = $sellerData['location'] ?? '';
        $contacto->provincia = $sellerData['province'] ?? '';
        $contacto->codcliente = $cliente->codcliente;

        $contacto->save();

        // Link contacto to cliente
        $cliente->idcontactofact = $contacto->idcontacto;
        $cliente->idcontactoenv = $contacto->idcontacto;
        $cliente->save();
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
