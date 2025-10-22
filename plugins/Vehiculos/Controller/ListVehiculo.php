<?php
namespace FacturaScripts\Plugins\Vehiculos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Listado simplificado de Vehiculo (nuevo modelo fachada) usando la tabla existente.
 */
class ListVehiculo extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'Vehiculos';
        $data['icon'] = 'fa-solid fa-car';
        $data['showonmenu'] = true;
        $data['menuorder'] = 85; // Posición relativa dentro de Ventas
        return $data;
    }

    protected function createViews(): void
    {
        $this->addView('ListVehiculo', 'Vehiculo', 'Vehiculos', 'fa-solid fa-car')
            ->addOrderBy(['matricula'], 'license-plate')
            ->addOrderBy(['marca'], 'manufacturer')
            ->addOrderBy(['modelo'], 'model')
            ->addSearchFields(['matricula','bastidor','marca','modelo'])
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre');
    }
}
