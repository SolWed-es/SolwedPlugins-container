<?php
/**
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;


/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class EditAgente
{
    public function createViews()
    {
        return function () {
            $this->createViewContacts();
        };
    }

    protected function createViewContacts(): Closure
    {
        return function (string $viewName = 'ListCrmContacto') {
            $this->addListView($viewName, 'Contacto', 'contacts', 'fa-solid fa-address-book')
                ->addOrderBy(['email'], 'email')
                ->addOrderBy(['nombre'], 'name')
                ->addOrderBy(['empresa'], 'company')
                ->addOrderBy(['fechaalta'], 'creation-date', 2)
                ->addSearchFields(['nombre', 'apellidos', 'email', 'empresa', 'observaciones', 'telefono1', 'telefono2'])
                ->disableColumn('agent', true);

            // disable buttons
            $this->tab($viewName)
                ->setSettings('btnDelete', false)
                ->setSettings('btnNew', false);
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName !== 'ListCrmContacto') {
                return;
            }

            $codagente = $this->getViewModelValue($this->getMainViewName(), 'codagente');
            $where = [new DataBaseWhere('codagente', $codagente)];
            $view->loadData('', $where);
        };
    }
}
