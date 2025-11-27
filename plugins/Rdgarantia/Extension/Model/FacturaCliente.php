<?php

/**
 * FacturaCliente model extension for RDGarantia plugin
 * Hooks into invoice save events to sync payment status back to RDGarantia
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Extension\Model;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaSyncMap;
use FacturaScripts\Core\Tools;

class FacturaCliente
{
    /**
     * Hook that runs AFTER invoice is updated
     * Syncs payment status to RDgarantia
     */
    public function save(): Closure
    {
        return function () {
            // Check if this is an RDG invoice first (fast filter)
            $syncMap = new RdgarantiaSyncMap();
            $where = [
                new DataBaseWhere('fs_code', $this->idfactura),
                new DataBaseWhere('entity_type', 'factura')
            ];

            if (!$syncMap->loadFromCode('', $where)) {
                // Not an RDG invoice, skip
                return;
            }

            // Get current payment status (AFTER database update)
            $isPaid = $this->pagada;
            $status = $isPaid ? 'unpaid' : 'paid';  // CORRECT - no reversal!

            file_put_contents('/tmp/rdg_model_debug.log', date('Y-m-d H:i:s') . " - [Status: $status] \n", FILE_APPEND);

            // Sync to RDG API
            $rdgId = $syncMap->rdg_id;
            $apiUrl = 'https://gestion.rdgarantia.com/?api=billing-payment&id=' . $rdgId . '&status=' . $status;

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (!isset($data['success']) || !$data['success']) {
                    Tools::log()->error(
                        'RDG Sync: API error - ' . ($data['error'] ?? 'Unknown error')
                    );
                }
            } else {
                Tools::log()->error(
                    'RDG Sync: Failed to sync payment status. HTTP ' . $httpCode
                );
            }
        };
    }
}
