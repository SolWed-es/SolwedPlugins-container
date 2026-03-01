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
use FacturaScripts\Plugins\HumanResources\Model\Employee;

/**
 * Model for employee route assignments
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EmployeeRoute extends ModelClass
{
    use ModelTrait;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     * Employee ID (Foreign Key)
     *
     * @var int
     */
    public $idemployee;

    /**
     * Route ID (Foreign Key)
     *
     * @var int
     */
    public $idruta;

    /**
     * Assignment date
     *
     * @var string
     */
    public $fecha_asignacion;

    /**
     * Active status
     *
     * @var bool
     */
    public $activo;

    /**
     * Notes
     *
     * @var string
     */
    public $notas;

    /**
     * Creation date
     *
     * @var string
     */
    public $created_at;

    /**
     * Last update
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
        $this->fecha_asignacion = date('Y-m-d');
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
        return 'rrhh_employeeroutes';
    }

    /**
     * Returns the name of the controller for this model.
     */
    public function modelClassName(): string
    {
        return 'EmployeeRoute';
    }

    /**
     * Returns the employee associated with this assignment
     *
     * @return Employee|null
     */
    public function getEmployee()
    {
        if (empty($this->idemployee)) {
            return null;
        }

        $employee = new Employee();
        return $employee->get($this->idemployee);
    }

    /**
     * Returns the route associated with this assignment
     *
     * @return Ruta|null
     */
    public function getRuta()
    {
        if (empty($this->idruta)) {
            return null;
        }

        $ruta = new Ruta();
        return $ruta->get($this->idruta);
    }

    /**
     * Check if the assignment is currently active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->activo;
    }
}
