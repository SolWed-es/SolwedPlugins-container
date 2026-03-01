<?php

namespace FacturaScripts\Plugins\HumanResourcesSolwed;

use FacturaScripts\Core\Template\InitClass;

class Init extends InitClass
{
    public function init(): void
    {
        $this->loadExtension(new Extension\Controller\ListEmployee());
        $this->loadExtension(new Extension\Controller\EditEmployee());
        $this->loadExtension(new Extension\Controller\ListAttendance());
        $this->loadExtension(new Extension\Controller\EditAttendance());
        $this->loadExtension(new Extension\Model\Attendance());
        $this->loadExtension(new Extension\Model\Contacto());
        $this->loadExtension(new Extension\Controller\Dashboard());
    }

    public function update(): void
    {
    }

    public function uninstall(): void
    {
    }
}
