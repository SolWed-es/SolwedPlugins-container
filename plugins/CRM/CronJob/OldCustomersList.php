<?php
/**
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\CronJob;

use FacturaScripts\Core\Template\CronJobClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\GrupoClientes;
use FacturaScripts\Plugins\CRM\Model\CrmLista;
use FacturaScripts\Plugins\CRM\Model\CrmListaContacto;

/**
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 * @author Carlos Garcia Gomez          <carlos@facturascripts.com>
 */
final class OldCustomersList extends CronJobClass
{
    const JOB_NAME = 'old-customers-list';

    public static function run(): void
    {
        self::echo("\n\n* JOB: " . self::JOB_NAME . ' ...');

        // obtener clientes agrupados por antigüedad
        $groupByAntiguedad = [
            '1' => self::getClientesByAntiguedad(1, 2),
            '2' => self::getClientesByAntiguedad(2, 3),
            '3' => self::getClientesByAntiguedad(3, 4),
            '4' => self::getClientesByAntiguedad(4, 5),
            '5' => self::getClientesByAntiguedad(5, null)
        ];

        // procesar grupos de clientes
        self::processAgeGroups($groupByAntiguedad);

        // procesar listas de CRM
        self::processAgeLists($groupByAntiguedad);

        self::saveEcho();
    }

    /**
     * Procesa los grupos de clientes: crea, vacía y asigna grupos según antigüedad
     * @param array $groupByAntiguedad Array de clientes agrupados por antigüedad
     */
    protected static function processAgeGroups(array $groupByAntiguedad): void
    {
        // obtener o crear los grupos de clientes
        $grupos = self::getOrCreateAgeGroups();

        // obtener códigos de los grupos de antigüedad
        $codigosGruposAntiguedad = array_map(fn($g) => $g->codgrupo, $grupos);

        // vaciar los grupos de antigüedad existentes (solo CLI1-CLI5)
        self::clearAgeGroups($grupos);

        // asignar grupos a los clientes
        foreach ($groupByAntiguedad as $key => $clientes) {
            $grupo = $grupos[$key];
            $asignados = 0;

            foreach ($clientes as $cliente) {
                // si el cliente tiene un grupo diferente a los de antigüedad, no lo tocamos
                if (!empty($cliente->codgrupo) && !in_array($cliente->codgrupo, $codigosGruposAntiguedad)) {
                    continue;
                }

                // asignar el grupo correspondiente
                $cliente->codgrupo = $grupo->codgrupo;
                if ($cliente->save()) {
                    $asignados++;
                }
            }

            if ($asignados > 0) {
                self::echo("\n-- Asignados " . $asignados . " clientes al grupo " . $grupo->nombre);
            }
        }
    }

    /**
     * Procesa las listas CRM: vacía y rellena las listas con los clientes correspondientes
     * @param array $groupByAntiguedad Array de clientes agrupados por antigüedad
     */
    protected static function processAgeLists(array $groupByAntiguedad): void
    {
        foreach (self::getOrCreateAgeLists() as $key => $crmLista) {
            // eliminar miembros antiguos
            $crmLista->removeAllContactos();
            self::echo("\n-- Vaciada la lista " . $crmLista->nombre . " con " . $crmLista->numcontactos . " miembros");

            // agregar miembros nuevos
            foreach ($groupByAntiguedad[$key] as $cliente) {
                $crmListasContacto = new CrmListaContacto();
                $crmListasContacto->idlista = $crmLista->id();
                $crmListasContacto->idcontacto = $cliente->idcontactofact;
                $crmListasContacto->save();
            }

            self::echo("\n-- Agregados " . count($crmLista->getMembers()) . " miembros a la lista " . $crmLista->nombre);
        }
    }

    /**
     * Devuelve una lista de clientes filtrados por antigüedad que tengan facturas pagadas este año
     * - Si no tiene fecha de alta se ignora
     * - Si no tiene idcontactofact se ignora
     * - Si está dado de baja se ignora
     * - Si no tiene factura pagada este año se ignora
     * @param int $minYears Antigüedad mínima en años (>= minYears)
     * @param int|null $maxYears Antigüedad máxima en años (< maxYears), null para sin límite
     * @return Cliente[]
     */
    protected static function getClientesByAntiguedad(int $minYears, ?int $maxYears): array
    {
        // calcular fechas límite para la antigüedad del cliente
        $fechaMax = date('Y-m-d', strtotime("-{$minYears} year"));
        $fechaMin = $maxYears ? date('Y-m-d', strtotime("-{$maxYears} year")) : null;

        // filtrar clientes por fecha de alta
        $where = [
            Where::lte('fechaalta', $fechaMax),
            Where::isNotNull('idcontactofact'),
            Where::eq('debaja', false)
        ];
        if ($fechaMin) {
            $where[] = Where::gt('fechaalta', $fechaMin);
        }

        // calcular fechas del año actual
        $inicioAnio = date('Y-01-01');
        $finAnio = date('Y-12-31');

        $clientes = [];

        // por cada cliente, verificar que tenga al menos una factura pagada este año
        foreach (Cliente::all($where) as $cliente) {
            $factura = new FacturaCliente();
            $whereFactura = [
                Where::eq('codcliente', $cliente->codcliente),
                Where::eq('pagada', true),
                Where::gte('fecha', $inicioAnio),
                Where::lte('fecha', $finAnio)
            ];

            // si tiene factura pagada este año, incluir
            if ($factura->loadWhere($whereFactura)) {
                $clientes[] = $cliente;
            }
        }

        return $clientes;
    }

    /**
     * Vacía los grupos de clientes de antigüedad, quitando el codgrupo a todos los clientes
     * @param GrupoClientes[] $grupos
     */
    protected static function clearAgeGroups(array $grupos): void
    {
        $totalVaciados = 0;

        foreach ($grupos as $grupo) {
            $where = [Where::eq('codgrupo', $grupo->codgrupo)];
            $clientes = Cliente::all($where);

            foreach ($clientes as $cliente) {
                $cliente->codgrupo = null;
                if ($cliente->save()) {
                    $totalVaciados++;
                }
            }
        }

        if ($totalVaciados > 0) {
            self::echo("\n-- Vaciados " . $totalVaciados . " clientes de los grupos de antigüedad");
        }
    }

    /**
     * Devuelve los grupos de clientes por antigüedad y si no existen los crea
     * @return GrupoClientes[]
     */
    public static function getOrCreateAgeGroups(): array
    {
        $grupos = [];
        $nombres = [
            '1' => 'Clientes de 1 año',
            '2' => 'Clientes de 2 años',
            '3' => 'Clientes de 3 años',
            '4' => 'Clientes de 4 años',
            '5' => 'Clientes de 5 años o más'
        ];
        $codigos = [
            '1' => 'CLI1',
            '2' => 'CLI2',
            '3' => 'CLI3',
            '4' => 'CLI4',
            '5' => 'CLI5'
        ];

        // para cada grupo
        foreach ($nombres as $key => $nombre) {
            // buscar si el grupo existe
            $grupo = new GrupoClientes();
            if (false === $grupo->loadWhereEq('codgrupo', $codigos[$key])) {
                // no existe, lo creamos
                $grupos[$key] = new GrupoClientes();
                $grupos[$key]->codgrupo = $codigos[$key];
                $grupos[$key]->nombre = $nombre;
                $grupos[$key]->save();
                continue;
            }

            // existe, lo usamos
            $grupos[$key] = $grupo;
        }

        return $grupos;
    }

    /**
     * Devuelve la lista de clientes por antigüedad y si no existe la crea
     * @return CrmLista[]
     */
    public static function getOrCreateAgeLists(): array
    {
        $listas = [];
        $nombres = [
            '1' => 'Clientes de 1 año',
            '2' => 'Clientes de 2 años',
            '3' => 'Clientes de 3 años',
            '4' => 'Clientes de 4 años',
            '5' => 'Clientes de 5 años o más'
        ];

        // para cada nombre de la lista
        foreach ($nombres as $key => $nombre) {
            // buscar si la lista existe
            $lista = new CrmLista();
            if (false === $lista->loadWhereEq('nombre', Tools::noHtml($nombre))) {
                // no existe, la creamos
                $listas[$key] = new CrmLista();
                $listas[$key]->nombre = $nombre;
                $listas[$key]->save();
                continue;
            }

            // existe, la usamos
            $listas[$key] = $lista;
        }

        return $listas;
    }
}
