<?php
/**
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;


/**
 * Description of EditCrmFuente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCrmFuente extends EditController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'source';
        $data['icon'] = 'fa-solid fa-file-import';
        return $data;
    }

    public function getModelClassName(): string
    {
        return 'CrmFuente';
    }

    protected function createViews()
    {
        parent::createViews();

        $this->setTabsPosition('bottom');

        $this->createViewsContacts();
    }

    protected function createViewsContacts(string $viewName = 'ListCrmContacto'): void
    {
        $this->addListView($viewName, 'Contacto', 'contacts', 'fa-solid fa-users')
            ->addSearchFields(['nombre', 'apellidos', 'email', 'empresa', 'observaciones', 'telefono1', 'telefono2'])
            ->addOrderBy(['email'], 'email')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['empresa'], 'company')
            ->setSettings('btnDelete', false);
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $id = $this->getViewModelValue($mainViewName, 'id');

        switch ($viewName) {
            case 'ListCrmContacto':
                $where = [new DataBaseWhere('idfuente', $id)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
