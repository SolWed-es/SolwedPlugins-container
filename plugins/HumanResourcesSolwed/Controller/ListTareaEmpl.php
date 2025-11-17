<?php
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

class ListTareaEmpl extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'rrhh';
        $data['title'] = 'Tareas empleado';
        $data['icon'] = 'fas fa-tasks';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewTareas();
    }

    protected function createViewTareas(string $viewName = 'ListTareaEmpl')
    {
        $this->addView($viewName, 'TareaEmpl', 'Tareas', 'fas fa-tasks');
        $this->addSearchFields($viewName, ['nombre', 'direccion']);
        $this->addOrderBy($viewName, ['idtareaempl'], 'ID');
        $this->addOrderBy($viewName, ['nombre'], 'Nombre');
        $this->addOrderBy($viewName, ['fecha'], 'Fecha');
        
        // Filtros
        $this->addFilterSelect($viewName, 'estado', 'estado', 'estado');
        $this->addFilterDatePicker($viewName, 'fecha', 'date', 'fecha');
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
    }
}