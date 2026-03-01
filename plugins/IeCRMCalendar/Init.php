<?php

namespace FacturaScripts\Plugins\IeCRMCalendar;
use FacturaScripts\Core\Template\InitClass;

final class Init extends InitClass
{
    public function init(): void
    {
        $this->loadExtension(new Extension\Model\CrmNota());
    }

    public function update(): void
    {
    }

    public function uninstall(): void
    {
    }
}

