<?php
/**
 * This file is part of HHRRSolwedDestinos plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedDestinos Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedDestinos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list Ruta.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListRuta extends ListController
{
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Rutas';
        $pagedata['icon'] = 'fas fa-route';
        $pagedata['menu'] = 'rrhh';
        $pagedata['ordernum'] = 120;
        $pagedata['showonmenu'] = true;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsRutas();
    }

    /**
     * Create rutas view
     */
    protected function createViewsRutas(string $viewName = 'ListRuta')
    {
        $this->addView($viewName, 'Ruta', 'Rutas', 'fas fa-route');
        $this->addOrderBy($viewName, ['fecha_inicio'], 'start-date', 2); // DESC por defecto
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addOrderBy($viewName, ['fecha_fin'], 'end-date');

        // Filtros
        $this->addFilterAutocomplete($viewName, 'nombre', 'name', 'nombre', 'nombre');
        $this->addFilterPeriod($viewName, 'fecha_inicio', 'start-date', 'fecha_inicio');

        // Configurar botones
        $this->setSettings($viewName, 'btnNew', true);
        $this->setSettings($viewName, 'btnDelete', true);
        
        // CORRECCIÓN: Configurar el botón de editar para que use el controlador EditRuta
        $this->setSettings($viewName, 'btnEdit', true);
        $this->setSettings($viewName, 'editController', 'EditRuta');
    }
}