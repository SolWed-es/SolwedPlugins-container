<?php

/**
 * Controller to import RDGarantia invoices into FacturaScripts
 * Creates invoice and redirects to native FS edit page
 * Solwed addition - Part of RDGarantia invoice integration
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\LineaFacturaCliente;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaSyncMap;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaProvinceMap;
use FacturaScripts\Plugins\Rdgarantia\Lib\RdgInvoiceMapper;
use FacturaScripts\Core\Tools;

class EditRdgarantiaInvoice extends Controller
{
    /**
     * Returns basic page attributes
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'Import Invoice from RDGarantia';
        $data['icon'] = 'fa-solid fa-file-import';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Main execution - import invoice and redirect to native FS edit page
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Get rdg_id parameter
        $rdgId = (int)$this->request->get('rdg_id', 0);
        Tools::log()->info("EditRdgarantiaInvoice - Processing rdg_id: {$rdgId}");

        if ($rdgId <= 0) {
            Tools::log()->error('Missing or invalid rdg_id parameter. URL parameters: ' . http_build_query($_GET));
            Tools::log()->error('missing-rdg-id-parameter');
            $this->redirect('ListFacturaCliente');
            return;
        }

        // Fetch invoice data from RDG
        $rdgData = $this->fetchRdgInvoice($rdgId);
        if (!$rdgData) {
            Tools::log()->error('Failed to fetch invoice data from RDGarantia API');
            Tools::log()->error('failed-to-fetch-rdg-invoice');
            $this->redirect('ListFacturaCliente');
            return;
        }

        Tools::log()->info("RDG Invoice Data: " . print_r($rdgData, true));

        // Validate data
        $validation = RdgInvoiceMapper::validateRdgInvoiceData($rdgData);
        if (!$validation['valid']) {
            foreach ($validation['errors'] as $error) {
                Tools::log()->error($error);
                Tools::log()->error($error);
            }
            $this->redirect('ListFacturaCliente');
            return;
        }

        // Create the invoice
        $codigo = $this->createInvoice($rdgId, $rdgData);
        if (!$codigo) {
            Tools::log()->error('Failed to create invoice');
            Tools::log()->error('failed-to-create-invoice');
            $this->redirect('ListFacturaCliente');
            return;
        }

        // Store invoice mapping in sync table for payment status synchronization
        $invoiceSyncMap = new RdgarantiaSyncMap();
        $invoiceSyncMap->rdg_id = $rdgId;
        $invoiceSyncMap->fs_code = $codigo;
        $invoiceSyncMap->entity_type = 'factura';
        $invoiceSyncMap->last_sync = date('Y-m-d H:i:s');
        $invoiceSyncMap->created_at = date('Y-m-d H:i:s');

        if ($invoiceSyncMap->save()) {
            Tools::log()->info("Sync mapping saved: RDG {$rdgId} -> FS {$codigo}");
        } else {
            Tools::log()->warning("Failed to save sync mapping for invoice {$codigo}");
        }

        // Success - redirect to native FS edit page
        Tools::log()->info("Invoice created successfully: {$codigo}. Redirecting to edit page.");
        Tools::log()->notice('invoice-imported-successfully');
        $this->redirect('EditFacturaCliente?code=' . $codigo);
    }

    /**
     * Fetch invoice data from RDG API
     */
    private function fetchRdgInvoice(int $idBill): ?array
    {
        $apiUrl = 'https://gestion.rdgarantia.com/?api=billing&id=' . $idBill;
        Tools::log()->info("Fetching RDG invoice from: {$apiUrl}");

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        Tools::log()->info("RDG API response - HTTP Code: {$httpCode}, Response length: " . strlen($response));

        if ($curlError) {
            Tools::log()->error("cURL Error: {$curlError}");
            return null;
        }

        if ($httpCode !== 200 || !$response) {
            Tools::log()->error("RDG API returned HTTP {$httpCode} or empty response");
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Tools::log()->error("JSON decode error: " . json_last_error_msg());
            return null;
        }

        if (!isset($data['success']) || $data['success'] !== true || empty($data['data'])) {
            Tools::log()->error("RDG API returned invalid response structure");
            return null;
        }

        Tools::log()->info("Successfully fetched RDG invoice data");
        return $data['data'];
    }

    /**
     * Create complete invoice using FS API
     * Returns the invoice codigo on success, null on failure
     */
    private function createInvoice(int $rdgId, array $rdgData): ?string
    {
        Tools::log()->info("createInvoice called for RDG ID: {$rdgId}, idUser: " . ($rdgData['idUser'] ?? 'null'));

        // Get client code
        $codcliente = RdgarantiaSyncMap::getFsCode($rdgData['idUser'] ?? 0, 'cliente');
        if (!$codcliente) {
            $businessName = $rdgData['billingInfo']['businessName'] ?? 'Unknown';
            Tools::log()->error("Client not synced. RDG ID: {$rdgData['idUser']}, Name: {$businessName}");
            return null;
        }

        // Prepare line items for API
        $lineas = [];
        if (!empty($rdgData['concepts'])) {
            foreach ($rdgData['concepts'] as $concept) {
                $cantidad = (!empty($concept['quantity']) && $concept['quantity'] > 0) ?
                    (float)$concept['quantity'] : 1;

                $pvpunitario = 0;
                if (!empty($concept['unityPrice']) && $concept['unityPrice'] > 0) {
                    $pvpunitario = (float)$concept['unityPrice'];
                } elseif (!empty($concept['price'])) {
                    $pvpunitario = (float)$concept['price'] / $cantidad;
                }

                $lineas[] = [
                    'descripcion' => $concept['concept'] ?? 'No description',
                    'cantidad' => $cantidad,
                    'pvpunitario' => $pvpunitario,
                    'dtopor' => !empty($concept['discount']) ? (float)$concept['discount'] : 0,
                    'codimpuesto' => 'IVA' . (!empty($concept['iva']) ? (int)$concept['iva'] : 21)
                ];
            }
        }

        // Always use today's date for FS invoice to avoid date validation issues
        $fecha = date('d-m-Y');

        // Keep original RDG date in observations
        $rdgFecha = $rdgData['dateFormatted'] ?? null;
        if ($rdgFecha) {
            $ts = is_numeric($rdgFecha) ? (int)$rdgFecha : strtotime($rdgFecha);
            $rdgFecha = $ts ? date('d-m-Y', $ts) : (string)$rdgFecha;
        } else {
            $rdgFecha = 'N/A';
        }

        // Prepare API request data
        $postData = [
            'codcliente' => $codcliente,
            'codserie' => 'RDG',  // Use dedicated RDG series to avoid date conflicts
            'fecha' => $fecha,
            'observaciones' => "Importado desde RDGarantia ID: {$rdgId}\nFecha RDG Original: {$rdgFecha}",
            'lineas' => json_encode($lineas)
        ];

        Tools::log()->info("Calling FS API to create invoice - Client: {$codcliente}, Lines: " . count($lineas));
        Tools::log()->info("API Request Data: " . print_r($postData, true));
        Tools::log()->info("Line items detail: " . print_r($lineas, true));

        // Call FS API (internal self-call within container)
        $apiUrl = 'http://localhost/api/3/crearFacturaCliente';
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Token: SbZXu30ED09pEzsVADUu',
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Tools::log()->error("cURL Error calling FS API: {$curlError}");
        }

        Tools::log()->info("FS API response - HTTP {$httpCode}: " . substr($response, 0, 500));

        if ($httpCode !== 200) {
            Tools::log()->error("FS API failed with HTTP {$httpCode}. Full response: " . $response);
            return null;
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Tools::log()->error("JSON decode error from API: " . json_last_error_msg());
            return null;
        }

        Tools::log()->info("FS API decoded response: " . print_r($result, true));

        if (empty($result['doc']['codigo'])) {
            Tools::log()->error("FS API did not return invoice codigo. Response: " . print_r($result, true));
            return null;
        }

        $codigo = $result['doc']['idfactura'];
        Tools::log()->info("Invoice created via API with codigo: {$codigo}");

        return $codigo;
    }
}
