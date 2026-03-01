<?php
/**
 * This file is part of HHRRSolwedTurnos plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedTurnos Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedTurnos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit Shift.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditShift extends EditController
{
    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'Shift';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'shift';
        $pagedata['icon'] = 'fas fa-clock';
        $pagedata['menu'] = 'rrhh';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}