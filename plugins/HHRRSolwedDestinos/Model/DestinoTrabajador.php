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
 * Model for destination-worker assignments
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class DestinoTrabajador extends ModelClass
{
    use ModelTrait;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     * Destination ID (Foreign Key)
     *
     * @var int
     */
    public $iddestino;

    /**
     * Employee ID (Foreign Key to rrhh_employees)
     *
     * @var int
     */
    public $idemployee;

    /**
     * Assignment date
     *
     * @var string
     */
    public $fecha;

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
        $this->fecha = date('Y-m-d');
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }

    /**
     * Returns the name of the column that is the model's primary key.
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     */
    public static function tableName(): string
    {
        return 'destino_trabajador';
    }

    /**
     * Returns the destination associated with this assignment
     *
     * @return array|null
     */
    public function getDestino()
    {
        if (empty($this->iddestino)) {
            return null;
        }

        // Usar el método correcto para acceder a la base de datos
        $sql = "SELECT * FROM destinos WHERE iddestino = " . intval($this->iddestino);
        $data = self::$dataBase->select($sql);
        
        return !empty($data) ? $data[0] : null;
    }

    /**
     * Returns the employee associated with this assignment
     *
     * @return array|null
     */
    public function getEmployee()
    {
        if (empty($this->idemployee)) {
            return null;
        }

        // Usar el método correcto para acceder a la base de datos
        $sql = "SELECT * FROM rrhh_employees WHERE id = " . intval($this->idemployee);
        $data = self::$dataBase->select($sql);
        
        return !empty($data) ? $data[0] : null;
    }

    /**
     * Checks if the assignment is for a past date
     */
    public function isPast(): bool
    {
        return $this->fecha < date('Y-m-d');
    }

    /**
     * Validates the model data
     */
    public function test(): bool
    {
        if (empty($this->iddestino)) {
            $this->toolBox()->i18nLog()->warning('destination-id-required');
            return false;
        }

        if (empty($this->idemployee)) {
            $this->toolBox()->i18nLog()->warning('employee-id-required');
            return false;
        }

        if (empty($this->fecha)) {
            $this->toolBox()->i18nLog()->warning('assignment-date-required');
            return false;
        }

        // Verify that the destination exists using SQL query
        $destino = $this->getDestino();
        if (!$destino) {
            $this->toolBox()->i18nLog()->warning('destination-not-found');
            return false;
        }

        // Verify that the employee exists
        $employee = $this->getEmployee();
        if (!$employee) {
            $this->toolBox()->i18nLog()->warning('employee-not-found');
            return false;
        }

        // Update timestamp on save
        $this->updated_at = date('Y-m-d H:i:s');

        return parent::test();
    }

    /**
     * Returns true if there are no errors on properties values.
     */
    public function save(): bool
    {
        if ($this->exists()) {
            $this->updated_at = date('Y-m-d H:i:s');
        } else {
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');
        }

        return parent::save();
    }
}