<?php
/**
 * This file is part of HumanResourcesSolwed plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HumanResourcesSolwed Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list Destino.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListDestino extends ListController
{
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Destinos';
        $pagedata['icon'] = 'fas fa-map-marker-alt';
        $pagedata['menu'] = 'rrhh';
        $pagedata['ordernum'] = 100;
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsDestinos();
    }

    /**
     * Create destinos view
     */
    protected function createViewsDestinos(string $viewName = 'ListDestino')
    {
        $this->addView($viewName, 'Destino', 'Destinos', 'fas fa-map-marker-alt');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addOrderBy($viewName, ['ciudad'], 'city');
        $this->addOrderBy($viewName, ['fecha_inicio'], 'start-date');
        $this->addOrderBy($viewName, ['activo'], 'active');

        // Add search fields
        $this->addSearchFields($viewName, ['nombre', 'direccion', 'ciudad', 'referencia']);

        // Add filters
        $this->addFilterSelect($viewName, 'activo', 'active', 'activo', [
            '' => '------',
            '1' => 'yes',
            '0' => 'no'
        ]);
    }
}
