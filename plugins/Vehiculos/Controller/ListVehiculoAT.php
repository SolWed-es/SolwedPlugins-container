<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Vehicle listing controller - optimized for PHP 8.1+
 */

namespace FacturaScripts\Plugins\Vehiculos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Vehicle listing controller
 * Handles vehicle management interface with filters and search capabilities
 */
class ListVehiculoAT extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'Vehiculos';
        $data['icon'] = 'fa-solid fa-car';
        $data['showonmenu'] = true;
        return $data;
    }

    protected function createViews(): void 
    { 
        $this->createViewsVehicles(); 
    }

    /**
     * Create vehicles view with filters and search capabilities
     */
    protected function createViewsVehicles(string $viewName = 'ListVehiculoAT'): void
    {
        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');

        $fuelTypes = [
            ['code' => 'Gasolina', 'description' => 'Gasolina'],
            ['code' => 'Diésel', 'description' => 'Diésel'],
            ['code' => 'Eléctrico', 'description' => 'Eléctrico'],
            ['code' => 'Híbrido', 'description' => 'Híbrido'],
            ['code' => 'GLP', 'description' => 'GLP'],
            ['code' => 'GNC', 'description' => 'GNC']
        ];

        $this->addView($viewName, 'MaquinaAT', 'Vehiculos', 'fa-solid fa-car')
            ->addOrderBy(['idmaquina'], 'code', 2)
            ->addOrderBy(['fecha'], 'date')
            ->addOrderBy(['matricula'], 'license-plate')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['codfabricante'], 'manufacturer')
            ->addSearchFields(['descripcion', 'idmaquina', 'nombre', 'numserie', 'matricula', 'bastidor', 'color'])
            ->addFilterPeriod('fecha', 'date', 'fecha')
            ->addFilterSelect('codfabricante', 'manufacturer', 'codfabricante', $manufacturers)
            ->addFilterSelect('combustible', 'fuel-type', 'combustible', $fuelTypes)
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterSelect('codagente', 'agent', 'codagente', $agents)
            ->addFilterNumber('km-gt', 'kilometers', 'kilometros', '>=')
            ->addFilterNumber('km-lt', 'kilometers', 'kilometros', '<=');
    }
}
