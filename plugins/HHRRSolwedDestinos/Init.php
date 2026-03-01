<?php

namespace FacturaScripts\Plugins\HHRRSolwedDestinos;

use FacturaScripts\Core\Base\InitClass;

class Init extends InitClass
{
    public function init()
    {
        // Se ejecuta cada vez que se activa el plugin.
    }

    public function update()
    {
        // Se ejecuta cada vez que se actualiza el plugin.
        $this->createTable('rrhh_employeeroutes');
    }
}
