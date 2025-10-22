<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Sobreescritura del Template1 de PlantillasPDF para incluir información de vehículos
 * Compatible con PHP 8.1+
 */

namespace FacturaScripts\Plugins\Vehiculos\Lib\PlantillasPDF;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Plugins\Vehiculos\Model\MaquinaAT;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\PlantillasPDF\Lib\PlantillasPDF\Template1 as OriginalTemplate1;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Base\DataBase;

/**
 * Extensión de Template1 para incluir información de vehículos en facturas
 * 
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Template1 extends OriginalTemplate1
{
    // ... (hereda get() original del padre; eliminamos sobrecarga y configuraciones no usadas)

    /**
     * Sobreescribe addInvoiceHeader para incluir información de vehículo junto al cliente
     */
    public function addInvoiceHeader($model): void
    {
        parent::addInvoiceHeader($model);
        // Insertar bloque de vehículo justo después del bloque de cliente
        $this->printInvoiceVehicle($model);
    }

    /**
     * Sobreescribe addInvoiceLines para incluir información de vehículo justo después
     */
    public function addInvoiceLines($model): void
    {
        // Ejecutar el comportamiento original de addInvoiceLines
        $lines = $model->getLines();
        $tHead = '<thead><tr>';
        foreach ($this->getInvoiceLineFields() as $field) {
            $tHead .= '<th align="' . $field['align'] . '">' . $field['title'] . '</th>';
        }
        $tHead .= '</tr></thead>';

        $tBody = '';
        $numlinea = 1;
        $tLines = [];
        foreach ($lines as $line) {
            $tLines[] = $line;
            $line->numlinea = $numlinea;
            $tBody .= '<tr>';
            foreach ($this->getInvoiceLineFields() as $field) {
                $tBody .= '<td align="' . $field['align'] . '" valign="top">' . $this->getInvoiceLineValue($line, $field) . '</td>';
            }
            $tBody .= '</tr>';
            $numlinea++;

            if (property_exists($line, 'salto_pagina') && $line->salto_pagina) {
                $this->writeHTML('<div class="table-lines"><table class="table-big table-list">' . $tHead . $tBody . '</table></div>');
                $this->writeHTML($this->getInvoiceTotalsPartial($model, $tLines));
                $this->mpdf->AddPage();
                $tBody = '';
                $tLines = [];
            }
        }

        $this->writeHTML('<div class="table-lines"><table class="table-big table-list">' . $tHead . $tBody . '</table></div>');

        // Continuar con el comportamiento original de los totales
        $copier = new \DeepCopy\DeepCopy();
        $clonedPdf = $copier->copy($this->mpdf);
        $clonedPdf->writeHTML($this->getInvoiceTotalsFinal($model));

        if (count($clonedPdf->pages) > count($this->mpdf->pages)) {
            $this->mpdf->AddPage();
        }

        $this->writeHTML($this->getInvoiceTotalsFinal($model));
    }

    /**
     * Método auxiliar para añadir la sección de información del vehículo después del header
     */
    private function printInvoiceVehicle(BusinessDocument $model): void
    {
        // Solo facturas de cliente con idmaquina definido
        if ($model->modelClassName() !== 'FacturaCliente' || empty($model->codcliente)) {
            return;
        }
        if (!property_exists($model, 'idmaquina') || empty($model->idmaquina)) {
            return; // no hay vehículo seleccionado explícitamente
        }

        $veh = new MaquinaAT();
        if (!$veh->loadFromCode($model->idmaquina)) {
            return; // idmaquina inválido
        }
        // Validar que el vehículo pertenece al mismo cliente de la factura
        if (!empty($veh->codcliente) && $veh->codcliente !== $model->codcliente) {
            return; // no pertenece a este cliente
        }

        $i18n = $this->toolBox()->i18n();
        // Soportar dos variantes de modelo: uno con marca/modelo y otro con fabricante/nombre
        $marca = '';
        $modelo = '';

        if (property_exists($veh, 'marca') && !empty($veh->marca)) {
            $marca = $veh->marca; // Variante plugin Vehiculos
        } elseif (property_exists($veh, 'codfabricante') && !empty($veh->codfabricante)) {
            // Variante plugin Servicios con fabricante
            $fab = $veh->getFabricante();
            if ($fab && !empty($fab->nombre)) {
                $marca = $fab->nombre;
            }
        }

        if (property_exists($veh, 'modelo') && !empty($veh->modelo)) {
            $modelo = $veh->modelo; // Variante plugin Vehiculos
        } elseif (property_exists($veh, 'nombre') && !empty($veh->nombre)) {
            $modelo = $veh->nombre; // Variante plugin Servicios
        }

        $matricula = property_exists($veh, 'matricula') && !empty($veh->matricula) ? strtoupper($veh->matricula) : '';
        $bastidor  = property_exists($veh, 'bastidor') && !empty($veh->bastidor) ? strtoupper($veh->bastidor) : '';
        $codmotor  = property_exists($veh, 'codmotor') && !empty($veh->codmotor) ? strtoupper($veh->codmotor) : '';

        // Si no hay ningún dato relevante, no mostramos nada
        if (empty($marca) && empty($modelo) && empty($matricula) && empty($bastidor) && empty($codmotor)) {
            return;
        }

        // Bloque compacto justo tras el cliente
        $html  = '<div style="margin-top:8px;border:1px solid #ddd;padding:6px 10px;font-size:11px;">';
        $html .= '<strong style="color:' . $this->get('color1') . ';font-size:12px;">Vehículo:</strong> ';
        $parts = [];
        if ($marca)    { $parts[] = 'Marca: <strong>'      . htmlspecialchars($marca)    . '</strong>'; }
        if ($modelo)   { $parts[] = 'Modelo: <strong>'     . htmlspecialchars($modelo)   . '</strong>'; }
        if ($matricula){ $parts[] = 'Matrícula: <strong>'   . htmlspecialchars($matricula). '</strong>'; }
        if ($bastidor) { $parts[] = 'Bastidor: <strong>'    . htmlspecialchars($bastidor) . '</strong>'; }
        if ($codmotor) { $parts[] = 'Cod. Motor: <strong>'  . htmlspecialchars($codmotor) . '</strong>'; }
        $html .= implode(' &nbsp;|&nbsp; ', $parts);
        $html .= '</div>';

        $this->writeHTML($html);
    }

    // Métodos auxiliares originales removidos (cards/listas) para simplificar la plantilla
}
