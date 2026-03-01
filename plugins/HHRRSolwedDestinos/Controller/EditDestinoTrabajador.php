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
 * Controller to edit DestinoTrabajador.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditDestinoTrabajador extends EditController
{
    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'DestinoTrabajador';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Editar asignación de destino';
        $pagedata['icon'] = 'fas fa-user-tag';
        $pagedata['menu'] = 'rrhh';
        $pagedata['ordernum'] = 110;
        $pagedata['showonmenu'] = false; // Ocultar del menú

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
        $view->disableColumn('created_at');
        $view->disableColumn('updated_at');
    }
}