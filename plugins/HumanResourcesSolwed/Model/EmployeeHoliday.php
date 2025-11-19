<?php
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Model;

use FacturaScripts\Plugins\HumanResources\Model\EmployeeHoliday as ParentEmployeeHoliday;

class EmployeeHoliday extends ParentEmployeeHoliday
{
    /**
     * Estado por defecto de la solicitud.
     *
     * @var string
     */
    public $holidaystatus = 'Solicitadas';

    /**
     * Año pre-calculado para las agrupaciones.
     *
     * @var int|null
     */
    public ?int $year_group = null;
}
