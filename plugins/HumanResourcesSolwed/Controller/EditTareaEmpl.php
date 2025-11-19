<?php
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditTareaEmpl extends EditController
{
    public function getModelClassName(): string
    {
        return 'TareaEmpl';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'rrhh';
        $data['title'] = 'Tareas empleado';
        $data['icon'] = 'fas fa-tasks';
        return $data;
    }
}