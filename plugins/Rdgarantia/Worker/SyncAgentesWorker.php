<?php

/**
 * Sync Agentes Worker
 * Synchronizes agents from Rdgarantia to FacturaScripts
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Worker;

use FacturaScripts\Core\Model\Agente;
use FacturaScripts\Core\Model\Contacto;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaSyncMap;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaProvinceMap;
use FacturaScripts\Core\Tools;

class SyncAgentesWorker
{
    /** @var string */
    private $apiEndpoint = 'https://gestion.rdgarantia.com/?api=agents';

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
            // Fetch agents from Rdgarantia API
            $response = $this->fetchFromAPI();

            if (!$response || !isset($response['success']) || $response['success'] !== true) {
                $this->addError('Failed to fetch agents from Rdgarantia API');
                return $this->results;
            }

            if (empty($response['data'])) {
                $this->addMessage('No agents found in Rdgarantia');
                return $this->results;
            }

            // Process each agent
            foreach ($response['data'] as $agentData) {
                $this->processAgent($agentData);
            }
        } catch (\Exception $e) {
            $this->addError('Exception: ' . $e->getMessage());
        }

        return $this->results;
    }

    /**
     * Fetch agents from Rdgarantia API
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
     * Process a single agent
     */
    private function processAgent(array $agentData): void
    {
        try {
            $rdgId = $agentData['id'] ?? 0;

            if ($rdgId === 0) {
                $this->addError('Invalid agent ID');
                return;
            }

            // Check if already synced
            $existingCode = RdgarantiaSyncMap::getFsCode($rdgId, 'agente');

            if ($existingCode) {
                // Already synced
                if (!$this->forceUpdate) {
                    // Skip if not forcing update
                    $this->results['skipped']++;
                    return;
                }

                // Force update: load existing agent and update fields
                $agente = new Agente();
                if (!$agente->loadFromCode($existingCode)) {
                    $this->addError("Agent code {$existingCode} not found in FS for RDG ID {$rdgId}");
                    return;
                }

                $codagente = $existingCode;
                $isUpdate = true;
            } else {
                // New agent: generate code and create
                $codagente = $this->generateAgentCode($agentData);
                $agente = new Agente();
                $agente->codagente = $codagente;
                $isUpdate = false;
            }

            // Update/Set all fields (full overwrite)
            $agente->nombre = $agentData['fullName'] ?? '';
            $agente->cifnif = $agentData['dni'] ?? '';
            $agente->email = $agentData['email'] ?? '';
            $agente->telefono1 = $agentData['phone'] ?? '';
            $agente->debaja = !($agentData['active'] ?? true);

            if (!$isUpdate) {
                $agente->fechaalta = $agentData['creationDateFormatted'] ?? date('Y-m-d');
            }

            // Custom Rdgarantia fields
            $agente->rdg_import_ref = (string)$rdgId;
            $agente->rdg_dni = $agentData['dni'] ?? '';
            $agente->rdg_last_sync = date('Y-m-d H:i:s');

            if (!$agente->save()) {
                $this->addError("Failed to save agent: {$agente->nombre}");
                return;
            }

            // Create/Update Contacto for address
            if (!empty($agentData['address'])) {
                $this->createContacto($agente, $agentData);
            }

            // Save mapping (only for new records)
            if (!$isUpdate) {
                $this->saveMapping($rdgId, $codagente, 'agente');
            }

            if ($isUpdate) {
                $this->results['updated']++;
                $this->addMessage("Agent updated: {$agente->nombre} ({$codagente})");
            } else {
                $this->results['success']++;
                $this->addMessage("Agent synced: {$agente->nombre} ({$codagente})");
            }
        } catch (\Exception $e) {
            $this->addError("Error processing agent {$agentData['id']}: " . $e->getMessage());
        }
    }

    /**
     * Generate unique agent code
     */
    private function generateAgentCode(array $agentData): string
    {
        // Start with name initials + ID
        $name = $agentData['name'] ?? '';
        $surname = $agentData['surname'] ?? '';

        // Clean and get first letters
        $nameClean = preg_replace('/[^A-Z0-9]/i', '', strtoupper($name));
        $surnameClean = preg_replace('/[^A-Z0-9]/i', '', strtoupper($surname));

        $base = substr($nameClean, 0, 2) . substr($surnameClean, 0, 2) . $agentData['id'];

        // Remove any remaining invalid characters and limit to 10 chars
        $base = substr(preg_replace('/[^A-Z0-9]/i', '', $base), 0, 10);

        // Ensure we have at least something
        if (empty($base)) {
            $base = 'AG' . $agentData['id'];
        }

        // Ensure uniqueness
        $code = $base;
        $suffix = 1;
        while ($this->codeExists($code)) {
            $code = substr($base, 0, 8) . $suffix;
            $suffix++;
            if ($suffix > 99) {
                $code = 'A' . uniqid();
                break;
            }
        }

        return substr($code, 0, 10);
    }

    /**
     * Check if agent code already exists
     */
    private function codeExists(string $code): bool
    {
        $agente = new Agente();
        return $agente->loadFromCode($code);
    }

    /**
     * Create or update Contacto record for agent address
     */
    private function createContacto(Agente $agente, array $agentData): void
    {
        // Check if agent already has a linked contact
        if (!empty($agente->idcontacto)) {
            // Load existing contact and update it
            $contacto = new Contacto();
            if ($contacto->loadFromCode($agente->idcontacto)) {
                // Update existing contact
                $contacto->nombre = $agentData['name'] ?? '';
                $contacto->apellidos = $agentData['surname'] ?? '';
                $contacto->email = $agentData['email'] ?? '';
                $contacto->telefono1 = $agentData['phone'] ?? '';
                $contacto->cifnif = $agentData['dni'] ?? '';
                $contacto->direccion = $agentData['address'] ?? '';
                $contacto->codpostal = $agentData['postalCode'] ?? '';
                $contacto->ciudad = $agentData['location'] ?? '';
                $contacto->provincia = $agentData['province'] ?? '';
                $contacto->save();
                return;
            }
        }

        // No existing contact, create new one
        $contacto = new Contacto();
        $contacto->nombre = $agentData['name'] ?? '';
        $contacto->apellidos = $agentData['surname'] ?? '';
        $contacto->email = $agentData['email'] ?? '';
        $contacto->telefono1 = $agentData['phone'] ?? '';
        $contacto->cifnif = $agentData['dni'] ?? '';
        $contacto->direccion = $agentData['address'] ?? '';
        $contacto->codpostal = $agentData['postalCode'] ?? '';
        $contacto->ciudad = $agentData['location'] ?? '';
        $contacto->provincia = $agentData['province'] ?? '';
        $contacto->codagente = $agente->codagente;

        $contacto->save();

        // Link contacto to agente
        $agente->idcontacto = $contacto->idcontacto;
        $agente->save();
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
