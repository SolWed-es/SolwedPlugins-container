<?php
/**
 * This file is part of HHRRSolwedDestinos plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedDestinos Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedDestinos\Extension\Model;

/**
 * Extension for Attendance model to add location field
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Attendance
{
    /**
     * Location where the attendance was recorded
     *
     * @var string
     */
    public $localizacion;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->localizacion = '';
    }

    /**
     * Get the location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->localizacion ?? '';
    }

    /**
     * Set the location
     *
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->localizacion = $location;
    }
}