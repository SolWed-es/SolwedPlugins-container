<?php
/**
 * This file is part of HHRRSolwedDestinos plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedDestinos Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedDestinos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit Destino.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditDestino extends EditController
{
    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'Destino';
    }

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
        $pagedata['showonmenu'] = true;

        return $pagedata;
    }

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        parent::loadData($viewName, $view);
        
        // Set read-only fields
        $view->disableColumn('lat');
        $view->disableColumn('lon');
        $view->disableColumn('created_at');
        $view->disableColumn('updated_at');
    }
}