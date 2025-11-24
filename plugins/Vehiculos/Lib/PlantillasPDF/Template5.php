<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Sobreescritura del Template5 de PlantillasPDF para incluir información de vehículos
 * Compatible con PHP 8.1+
 */

namespace FacturaScripts\Plugins\Vehiculos\Lib\PlantillasPDF;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\PlantillasPDF\Lib\PlantillasPDF\Template5 as OriginalTemplate5;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;

/**
 * Extensión de Template5 para incluir información de vehículos en facturas
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Template5 extends OriginalTemplate5
{
    /**
     * Sobreescribe addInvoiceHeader para incluir información de vehículo junto al cliente
     */
    public function addInvoiceHeader($model): void
    {
        // Llamar al método original del padre
        parent::addInvoiceHeader($model);

        // Agregar información del vehículo justo después del header
        $this->addVehicleInfoAfterHeader($model);
    }

    /**
     * Método auxiliar para añadir la sección de información del vehículo después del header
     */
    private function addVehicleInfoAfterHeader(BusinessDocument $model): void
    {
        // Mostrar para Factura, Albarán, Pedido y Presupuesto, siempre que exista cliente
        $validModels = ['FacturaCliente', 'AlbaranCliente', 'PedidoCliente', 'PresupuestoCliente'];
        if (!in_array($model->modelClassName(), $validModels, true) || empty($model->codcliente)) {
            return;
        }

        // Intentar resolver el vehículo asociado al documento de forma robusta
        $vehicle = null;

        // 1) Si existe método getVehiculo() en el modelo, usarlo (evita consultas adicionales)
        try {
            if (method_exists($model, 'getVehiculo')) {
                $v = $model->getVehiculo();
                if ($v instanceof Vehiculo) {
                    $vehicle = $v;
                }
            }
        } catch (\Throwable $th) {
            // ignorar y continuar con otros métodos
        }

        // 2) Si el documento tiene propiedad idmaquina, intentar cargarla
        if ($vehicle === null && property_exists($model, 'idmaquina') && !empty($model->idmaquina)) {
            $tmp = new Vehiculo();
            if ($tmp->loadFromCode((int)$model->idmaquina)) {
                $vehicle = $tmp;
            }
        }

        // 3) Consulta directa a la tabla del documento para idmaquina (si existe la columna)
        if ($vehicle === null) {
            $map = [
                'FacturaCliente' => ['table' => 'facturascli', 'pk' => 'idfactura'],
                'AlbaranCliente' => ['table' => 'albaranescli', 'pk' => 'idalbaran'],
                'PedidoCliente' => ['table' => 'pedidoscli', 'pk' => 'idpedido'],
                'PresupuestoCliente' => ['table' => 'presupuestoscli', 'pk' => 'idpresupuesto'],
            ];
            $cfg = $map[$model->modelClassName()] ?? null;
            if ($cfg !== null) {
                try {
                    $dataBase = new DataBase();
                    $sql = 'SELECT idmaquina FROM ' . $cfg['table'] . ' WHERE ' . $cfg['pk'] . ' = ' . (int)$model->primaryColumnValue();
                    $data = $dataBase->select($sql);
                    $idmaquina = (!empty($data) && !empty($data[0]['idmaquina'])) ? (int)$data[0]['idmaquina'] : null;
                    if (!empty($idmaquina)) {
                        $tmp = new Vehiculo();
                        if ($tmp->loadFromCode($idmaquina)) {
                            $vehicle = $tmp;
                        }
                    }
                } catch (\Throwable $th) {
                    // Si la columna no existe o hay error, continuar con fallback por cliente
                }
            }
        }

        // 4) Fallback: primer vehículo del cliente
        if ($vehicle === null) {
            $vehicles = (new Vehiculo())->all(
                [new DataBaseWhere('codcliente', $model->codcliente)],
                ['matricula' => 'ASC'],
                0,
                1
            );
            if (empty($vehicles)) {
                return; // no hay vehículo que mostrar
            }
            $vehicle = $vehicles[0];
        }

        // Preparar datos principales: Marca, Modelo, Matrícula, Kilómetros
        $brand = trim((string)($vehicle->marca ?? ''));
        $modelName = trim((string)($vehicle->modelo ?? ''));
        $plate = trim((string)($vehicle->matricula ?? ''));
        $kilometers = $vehicle->kilometros ?? null;

        // Si no hay ningún dato, no mostrar nada
        if ($brand === '' && $modelName === '' && $plate === '' && $kilometers === null) {
            return;
        }

        $i18n = Tools::lang();

        // Render en una fila horizontal usando clases existentes
        $html = '<table class="table-big table-border"><tr>';
        $html .= '<td><b>' . $i18n->trans('brand') . ':</b> ' . htmlspecialchars($brand, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
        $html .= '<td><b>' . $i18n->trans('model') . ':</b> ' . htmlspecialchars($modelName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
        $html .= '<td><b>' . $i18n->trans('license-plate') . ':</b> ' . htmlspecialchars(strtoupper($plate), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
        if ($kilometers !== null) {
            $html .= '<td><b>' . $i18n->trans('kilometers') . ':</b> ' . number_format($kilometers, 0, ',', '.') . ' km</td>';
        }
        $html .= '</tr></table>';

        $this->writeHTML($html);
    }
}
