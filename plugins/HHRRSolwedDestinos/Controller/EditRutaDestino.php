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
 * Controller to edit RutaDestino.
 * Manages the assignment of destinations to routes.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditRutaDestino extends EditController
{
    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'RutaDestino';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Editar asignación de destino a ruta';
        $pagedata['icon'] = 'fas fa-route';
        $pagedata['menu'] = 'rrhh';
        $pagedata['ordernum'] = 120;
        $pagedata['showonmenu'] = false; // Ocultar del menú principal

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
        switch ($viewName) {
            case 'EditRutaDestino':
                parent::loadData($viewName, $view);
                break;
        }
    }
}