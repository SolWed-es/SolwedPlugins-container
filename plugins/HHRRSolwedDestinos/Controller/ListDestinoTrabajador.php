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
 * Controller to list DestinoTrabajador.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListDestinoTrabajador extends ListController
{
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Asignaciones de destino';
        $pagedata['icon'] = 'fas fa-user-tag';
        $pagedata['menu'] = 'rrhh';
        $pagedata['ordernum'] = 105;
        $pagedata['showonmenu'] = true; // Solo este aparece en el menú

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsDestinoTrabajador();
    }

    /**
     * Create DestinoTrabajador view
     *
     * @param string $viewName
     */
    protected function createViewsDestinoTrabajador(string $viewName = 'ListDestinoTrabajador')
    {
        $this->addView($viewName, 'DestinoTrabajador', 'assignments', 'fas fa-user-tag');
        $this->addOrderBy($viewName, ['fecha'], 'date', 2);
        $this->addOrderBy($viewName, ['id'], 'code');
        
        // Add search fields
        $this->addSearchFields($viewName, ['fecha']);
        
        // Add filters - corregir el método addFilterSelect
        $this->addFilterDatePicker($viewName, 'fecha', 'date', 'fecha');
        
        // Crear el filtro de destino correctamente usando iddestino
        $destinations = $this->codeModel->all('destinos', 'iddestino', 'nombre');
        $this->addFilterSelect($viewName, 'iddestino', 'destination', 'iddestino', $destinations);
    }
}