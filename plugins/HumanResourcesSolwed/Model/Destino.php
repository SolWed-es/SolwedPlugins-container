<?php
/**
 * This file is part of HumanResourcesSolwed plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HumanResourcesSolwed Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */

namespace FacturaScripts\Plugins\HumanResourcesSolwed\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;

/**
 * Model for destinations
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Destino extends ModelClass
{
    use ModelTrait;

    /**
     * Destination name
     *
     * @var string
     */
    public $nombre;

    /**
     * Destination address
     *
     * @var string
     */
    public $direccion;

    /**
     * City
     *
     * @var string
     */
    public $ciudad;

    /**
     * Postal code
     *
     * @var string
     */
    public $cp;

    /**
     * Latitude
     *
     * @var float
     */
    public $lat;

    /**
     * Longitude
     *
     * @var float
     */
    public $lon;

    /**
     * Duration in minutes
     *
     * @var int
     */
    public $duracion_min;

    /**
     * Entry time
     *
     * @var string
     */
    public $hora_entrada;

    /**
     * Exit time
     *
     * @var string
     */
    public $hora_salida;

    /**
     * Start date
     *
     * @var string
     */
    public $fecha_inicio;

    /**
     * End date
     *
     * @var string
     */
    public $fecha_fin;

    /**
     * Active status
     *
     * @var bool
     */
    public $activo;

    /**
     * Reference status
     *
     * @var bool
     */
    public $referencia;

    /**
     * Creation timestamp
     *
     * @var string
     */
    public $created_at;

    /**
     * Update timestamp
     *
     * @var string
     */
    public $updated_at;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->activo = true;
        $this->referencia = false;
        $this->fecha_inicio = date('Y-m-d');
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }

    /**
     * Returns the name of the column that is the model's primary key.
     */
    public static function primaryColumn(): string
    {
        return 'iddestino';
    }

    /**
     * Returns the name of the table that uses this model.
     */
    public static function tableName(): string
    {
        return 'destinos';
    }

    /**
     * Returns the name of the controller for this model.
     */
    public function modelClassName(): string
    {
        return 'Destino';
    }

    /**
     * Returns all worker assignments for this destination
     *
     * @return DestinoTrabajador[]
     */
    public function getDestinoTrabajadores()
    {
        $destinoTrabajador = new DestinoTrabajador();
        return $destinoTrabajador->all([new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('iddestino', $this->iddestino)]);
    }

    /**
     * Returns all route assignments for this destination
     *
     * @return RutaDestino[]
     */
    public function getDestinoRutas()
    {
        $destinoRuta = new RutaDestino();
        return $destinoRuta->all([new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('iddestino', $this->iddestino)]);
    }

    /**
     * Validates the model data
     */
    public function test(): bool
    {
        if (empty($this->nombre)) {
            $this->toolBox()->i18nLog()->warning('destination-name-required');
            return false;
        }

        if (empty($this->direccion)) {
            $this->toolBox()->i18nLog()->warning('destination-address-required');
            return false;
        }

        if (empty($this->ciudad)) {
            $this->toolBox()->i18nLog()->warning('destination-city-required');
            return false;
        }

        if (empty($this->duracion_min) || $this->duracion_min <= 0) {
            $this->toolBox()->i18nLog()->warning('destination-duration-required');
            return false;
        }

        // Validate time fields if provided
        if (!empty($this->hora_entrada) && !$this->validateTimeFormat($this->hora_entrada)) {
            $this->toolBox()->i18nLog()->warning('invalid-entry-time-format');
            return false;
        }

        if (!empty($this->hora_salida) && !$this->validateTimeFormat($this->hora_salida)) {
            $this->toolBox()->i18nLog()->warning('invalid-exit-time-format');
            return false;
        }

        // Update timestamp on save
        $this->updated_at = date('Y-m-d H:i:s');

        return parent::test();
    }

    /**
     * Validates time format (HH:MM or HH:MM:SS)
     */
    private function validateTimeFormat(string $time): bool
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time);
    }

    /**
     * Load data from database
     */
    public function loadFromCode($code, array $where = [], array $order = []): bool
    {
        return parent::loadFromCode($code, $where, $order);
    }
}
