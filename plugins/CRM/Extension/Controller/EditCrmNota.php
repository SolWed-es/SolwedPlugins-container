<?php

namespace FacturaScripts\Plugins\CRM\Extension\Controller;

use Closure;

class EditCrmNota
{
    protected function createViews(): Closure
    {
        return function(){
            $this->addButton('EditCrmNota', [
                'action' => 'opencalendar',
                'color' => 'primary',
                'icon' => 'fa-solid fa-calendar-alt',
                'label' => 'Calendar'
            ]);
        };
    }

    protected function execPreviousAction(): Closure
    {
        return function($action) {
            switch ($action) {
                case 'opencalendar':
                $this->redirect(FS_ROUTE."/Calendar");
            }
            return true;
        };
    }
}
