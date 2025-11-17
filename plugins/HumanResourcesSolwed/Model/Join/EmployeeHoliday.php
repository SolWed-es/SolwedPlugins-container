<?php
/**
 * Extensión para modificar el modelo EmployeeHoliday.
 *
 * @author Yo mismo
 */

namespace FacturaScripts\Plugins\HumanResourcesSolwed\Model\Join;

use FacturaScripts\Plugins\HumanResources\Model\Join\EmployeeHoliday as ParentClass;

class EmployeeHoliday extends ParentClass
{
    protected function getFields(): array {
        return [
            'id' => 'holidays.id',
            'startdate' => 'holidays.startdate',
            'enddate' => 'holidays.enddate',
            'idemployee' => 'holidays.idemployee',
            'applyto' => 'holidays.applyto',
            'totaldays' => 'holidays.totaldays',
            'authorized' => 'holidays.authorized',
            'holidaystatus' => 'holidays.holidaystatus',
            'note' => 'holidays.note',
            'nick' => 'employees.nick',
            'name' => 'employees.nombre',
            'credentialid' => 'employees.credentialid',
        ];
    }
}
