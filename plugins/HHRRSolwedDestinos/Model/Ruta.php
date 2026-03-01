<?php
/**
 * This file is part of HHRRSolwedDestinos plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedDestinos Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedDestinos\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;

/**
 * Model for routes
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Ruta extends ModelClass
{
    use ModelTrait;

    /**
     * Route name
     *
     * @var string
     */
    public $nombre;

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
     * Optimized status
     *
     * @var bool
     */
    public $optimizada;

    /**
     * Extra data in JSON format
     *
     * @var string
     */
    public $datos_extra;

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
        $this->optimizada = false;
        $this->fecha_inicio = date('Y-m-d');
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }

    /**
     * Returns the name of the column that is the model's primary key.
     */
    public static function primaryColumn(): string
    {
        return 'idruta';
    }

    /**
     * Returns the name of the table that uses this model.
     */
    public static function tableName(): string
    {
        return 'rutas';
    }

    /**
     * Returns the name of the controller for this model.
     */
    public function modelClassName(): string
    {
        return 'Ruta';
    }

    /**
     * Returns all destination assignments for this route
     *
     * @return DestinoRuta[]
     */
    public function getDestinoRutas()
    {
        $destinoRuta = new DestinoRuta();
        return $destinoRuta->all([new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('idruta', $this->idruta)], ['orden' => 'ASC']);
    }

    /**
     * Returns all destinations in this route ordered by position
     *
     * @return Destino[]
     */
    public function getDestinos()
    {
        $destinos = [];
        $destinoRutas = $this->getDestinoRutas();
        
        foreach ($destinoRutas as $destinoRuta) {
            $destino = new Destino();
            if ($destino->loadFromCode($destinoRuta->iddestino)) {
                $destinos[] = $destino;
            }
        }
        
        return $destinos;
    }

    /**
     * Validates the model data
     */
    public function test(): bool
    {
        if (empty($this->nombre)) {
            $this->toolBox()->i18nLog()->warning('route-name-required');
            return false;
        }

        if (empty($this->fecha_inicio)) {
            $this->toolBox()->i18nLog()->warning('route-start-date-required');
            return false;
        }

        // Validate date format and logic
        if (!empty($this->fecha_fin) && $this->fecha_fin < $this->fecha_inicio) {
            $this->toolBox()->i18nLog()->warning('route-end-date-before-start-date');
            return false;
        }

        // Update timestamp on save
        $this->updated_at = date('Y-m-d H:i:s');

        return parent::test();
    }
}