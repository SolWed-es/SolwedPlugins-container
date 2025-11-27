<?php

/**
 * Helper class to map RDGarantia invoice data to FacturaScripts format
 * Provides reusable mapping functions for invoice sync
 * Solwed addition - Part of RDGarantia invoice integration
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Lib;

use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\LineaFacturaCliente;
use FacturaScripts\Dinamic\Model\ReciboCliente;
use FacturaScripts\Plugins\Rdgarantia\Model\RdgarantiaSyncMap;
use FacturaScripts\Core\Tools;

class RdgInvoiceMapper
{
    /**
     * Create FS invoice from RDG invoice data
     * @param array $rdgData RDG invoice data from API
     * @return FacturaCliente|null
     */
    public static function createInvoiceFromRdg(array $rdgData): ?FacturaCliente
    {
        $invoice = new FacturaCliente();

        // Map client
        if (!empty($rdgData['idUser'])) {
            $clientCode = RdgarantiaSyncMap::getFsCode($rdgData['idUser'], 'cliente');
            if ($clientCode) {
                $invoice->codcliente = $clientCode;
            } else {
                // Client not synced yet
                $businessName = $rdgData['billingInfo']['businessName'] ?? 'Unknown';
                Tools::log()->warning("Client RDG ID {$rdgData['idUser']} not found in sync. Business name: {$businessName}");
                return null; // Cannot create invoice without client
            }
        } else {
            // Manual invoice without idUser
            if (!empty($rdgData['billingInfo']['businessName'])) {
                Tools::log()->warning("Manual invoice - no client mapping available");
                return null;
            }
        }

        // Set date
        if (!empty($rdgData['date'])) {
            $invoice->fecha = date('d-m-Y', $rdgData['date']);
        } else {
            $invoice->fecha = date('d-m-Y'); // Use today if no date
        }

        // Set payment method
        if (!empty($rdgData['idPaymentMethod'])) {
            $codpago = self::mapPaymentMethod($rdgData['idPaymentMethod']);
            if ($codpago) {
                $invoice->codpago = $codpago;
            }
        }

        // Set observations with RDG reference
        $invoice->observaciones = 'Imported from RDGarantia. Original ID: ' . $rdgData['id'];
        if (!empty($rdgData['billingNumber'])) {
            $invoice->observaciones .= ' - Original Number: ' . $rdgData['billingNumber'];
        }

        // Set number if invoice already has billingNumber in RDG
        if (!empty($rdgData['billingNumber'])) {
            $invoice->numero = $rdgData['billingNumber'];
        }

        return $invoice;
    }

    /**
     * Create line items from RDG concepts
     * @param FacturaCliente $invoice FS invoice model
     * @param array $concepts RDG concepts array
     * @return bool
     */
    public static function addLinesToInvoice(FacturaCliente $invoice, array $concepts): bool
    {
        if (empty($concepts)) {
            return false;
        }

        foreach ($concepts as $concept) {
            $line = new LineaFacturaCliente();
            $line->idfactura = $invoice->idfactura;
            $line->descripcion = $concept['concept'] ?? 'Sin descripción';

            // Quantity: use quantity if > 0, otherwise 1
            $line->cantidad = (!empty($concept['quantity']) && $concept['quantity'] > 0) ?
                (float)$concept['quantity'] : 1;

            // Unit price: use unityPrice if available, otherwise calculate from price
            if (!empty($concept['unityPrice']) && $concept['unityPrice'] > 0) {
                $line->pvpunitario = (float)$concept['unityPrice'];
            } elseif (!empty($concept['price'])) {
                $line->pvpunitario = (float)$concept['price'] / $line->cantidad;
            } else {
                $line->pvpunitario = 0;
            }

            // Discount
            $line->dtopor = !empty($concept['discount']) ? (float)$concept['discount'] : 0;

            // Tax (IVA)
            $line->iva = !empty($concept['iva']) ? (float)$concept['iva'] : 21;

            // Try to map product reference
            $referencia = self::mapProductReference($concept);
            if ($referencia) {
                $line->referencia = $referencia;
            }

            if (!$line->save()) {
                Tools::log()->error('Failed to save invoice line: ' . $line->descripcion);
                return false;
            }
        }

        return true;
    }

    /**
     * Create receipts from RDG payment installments
     * @param FacturaCliente $invoice FS invoice model
     * @param array $payments RDG payments array
     * @return bool
     */
    public static function createReceipts(FacturaCliente $invoice, array $payments): bool
    {
        if (empty($payments)) {
            return true; // No payments to create
        }

        foreach ($payments as $payment) {
            $receipt = new ReciboCliente();
            $receipt->idfactura = $invoice->idfactura;
            $receipt->codcliente = $invoice->codcliente;
            $receipt->coddivisa = $invoice->coddivisa;
            $receipt->importe = !empty($payment['pay']) ? (float)$payment['pay'] : 0;

            // Set due date from payment time
            if (!empty($payment['time'])) {
                $receipt->vencimiento = date('d-m-Y', $payment['time']);
            } else {
                $receipt->vencimiento = $invoice->fecha;
            }

            // Set payment method
            $receipt->codpago = $invoice->codpago;

            $receipt->numero = $invoice->numero;
            $receipt->observaciones = 'Payment installment ' . ($payment['order'] ?? 1) . ' from RDGarantia';

            if (!$receipt->save()) {
                Tools::log()->error('Failed to save receipt for invoice: ' . $invoice->codigo);
                return false;
            }
        }

        return true;
    }

    /**
     * Map RDG payment method ID to FS codpago
     * @param int $idPaymentMethod RDG payment method ID
     * @return string|null FS payment method code
     */
    private static function mapPaymentMethod(int $idPaymentMethod): ?string
    {
        // TODO: Implement payment method mapping
        // For now, return default payment method
        // This mapping should be stored in a database table or config

        // Common mappings:
        // 1 = CONT (Contado)
        // 2 = TRANS (Transferencia)
        // etc.

        $mapping = [
            1 => 'CONT',  // Cash/Contado
            2 => 'TRANS', // Bank transfer
            3 => 'TARJ',  // Card
            // Add more mappings as needed
        ];

        return $mapping[$idPaymentMethod] ?? null;
    }

    /**
     * Map product reference from RDG concept data
     * @param array $concept RDG concept data
     * @return string|null Product reference
     */
    private static function mapProductReference(array $concept): ?string
    {
        // If concept has idWarranty, try to map warranty -> product
        if (!empty($concept['idWarranty'])) {
            // TODO: Implement warranty -> product mapping
            // This would require a warranty sync or product mapping table
        }

        // If concept has idRenewal, try to map renewal -> product
        if (!empty($concept['idRenewal'])) {
            // TODO: Implement renewal -> product mapping
        }

        return null; // No mapping found
    }

    /**
     * Validate RDG invoice data before creating FS invoice
     * @param array $rdgData RDG invoice data
     * @return array Array with 'valid' => bool and 'errors' => array
     */
    public static function validateRdgInvoiceData(array $rdgData): array
    {
        $errors = [];

        // Check required fields
        if (empty($rdgData['id'])) {
            $errors[] = 'Missing invoice ID';
        }

        if (empty($rdgData['idUser']) && empty($rdgData['billingInfo']['businessName'])) {
            $errors[] = 'Missing client information';
        }

        if (empty($rdgData['concepts']) || !is_array($rdgData['concepts'])) {
            $errors[] = 'Missing invoice line items';
        }

        if (empty($rdgData['date'])) {
            $errors[] = 'Missing invoice date';
        }

        if (empty($rdgData['total']) || $rdgData['total'] <= 0) {
            $errors[] = 'Invalid invoice total';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
