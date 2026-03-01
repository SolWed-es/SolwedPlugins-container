<?php
/**
 * Extensión para modificar el modelo AttendanceUser.
 *
 * @author Yo mismo
 */

namespace FacturaScripts\Plugins\HumanResourcesSolwed\Model\Join;

use FacturaScripts\Plugins\HumanResources\Model\Join\AttendanceUser as ParentClass;

class AttendanceUser extends ParentClass

{
    protected function getFields(): array {
        return [
            'id' => 'attendances.id',
            'authorized' => 'attendances.authorized',
            'origin' => 'attendances.origin',
            'checkdate' => 'attendances.checkdate',
            'checktime' => 'attendances.checktime',
            'idemployee' => 'attendances.idemployee',
            'kind' => 'attendances.kind',
            'credentialid' => 'attendances.credentialid',
            'inputdelay' => 'attendances.inputdelay',
            'idabsenceconcept' => 'attendances.idabsenceconcept',
            'note' => 'attendances.note',
            'nick' => 'employees.nick',
            'localizacion' => 'attendances.localizacion',
        ];
    }
}
