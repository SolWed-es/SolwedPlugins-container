<?php

namespace FacturaScripts\Plugins\HumanResourcesSolwed;

use FacturaScripts\Core\Template\InitClass;

class Init extends InitClass
{
    public function init(): void
    {
        $this->loadExtension(new Extension\Controller\ListEmployee());
        $this->loadExtension(new Extension\Controller\EditEmployee());
    }

    public function update(): void
    {
    }

    public function uninstall(): void
    {
    }
}
