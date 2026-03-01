<?php
/**
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;

use FacturaScripts\Dinamic\Model\Cliente;

class EditServicioAT
{
    public function createViews(): Closure
    {
        return function () {
            $this->addEditListView('EditCrmNota', 'CrmNota', 'notes', 'fa-regular fa-sticky-note');
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ('EditCrmNota' !== $viewName) {
                return;
            }

            // cargamos las notas
            $mvn = $this->getMainViewName();
            $where = [
                new DataBaseWhere('tipodocumento', 'servicio de cliente'),
                new DataBaseWhere('iddocumento', $this->getViewModelValue($mvn, 'idservicio')),
            ];
            $orderBy = ['fecha' => 'DESC', 'hora' => 'DESC'];
            $view->loadData('', $where, $orderBy);

            // asignamos los valores predeterminados para la nueva nota
            $customer = new Cliente();
            if ($customer->loadFromCode($this->getViewModelValue($mvn, 'codcliente'))) {
                $view->model->idcontacto = $customer->idcontactofact;
            }
        };
    }
}
