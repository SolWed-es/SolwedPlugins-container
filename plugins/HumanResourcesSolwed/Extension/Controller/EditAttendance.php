<?php
/**
 * This file is part of HumanResourcesSolwed plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HumanResourcesSolwed Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Extension\Controller;

use Closure;

class EditAttendance
{
    public function execPreviousAction(): Closure
    {
        return function($action) {
            // Capturar el idturno del POST antes de procesar
            if ($this->request->isMethod('POST') && $action === 'save') {
                $idturno = $this->request->request->get('idturno');
                if ($idturno !== null && $idturno !== '') {
                    // Asignar directamente al modelo
                    $model = $this->getModel();
                    $model->idturno = $idturno;
                }
            }

            return parent::execPreviousAction($action);
        };
    }
}
