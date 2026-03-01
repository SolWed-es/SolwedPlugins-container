<?php
/**
 * This file is part of HHRRSolwedTurnosSemana plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedTurnosSemana Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit shift week days configuration
 *
 * @author José Ferrán
 */
class EditShiftWeekDay extends EditController
{
    /**
     * Returns the class name of the model to use in the edit view.
     */
    public function getModelClassName(): string
    {
        return 'ShiftWeekDay';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'RRHH';
        $data['title'] = 'daily-schedule';
        $data['icon'] = 'fas fa-calendar-day';
        return $data;
    }
}