<?php
/**
 * This file is part of HHRRSolwedTurnosSemana plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedTurnosSemana Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Extension\Model;

/**
 * Extension for Contacto model to add employee relationship
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Contacto
{
    /**
     * Employee ID
     *
     * @var int
     */
    public $idemployee;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->idemployee = null;
    }

    /**
     * Get the employee associated with this contact
     *
     * @return \FacturaScripts\Dinamic\Model\Employee|false
     */
    public function getEmployee()
    {
        if (empty($this->idemployee)) {
            return false;
        }

        $employee = new \FacturaScripts\Dinamic\Model\Employee();
        return $employee->loadFromCode($this->idemployee);
    }

    /**
     * Set the employee for this contact
     *
     * @param int $idemployee
     */
    public function setEmployee($idemployee)
    {
        $this->idemployee = $idemployee;
    }
}