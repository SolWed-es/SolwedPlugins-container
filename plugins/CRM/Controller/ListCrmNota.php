<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\CRM\Model\CrmFuente;

/**
 * Description of ListCrmNota
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListCrmNota extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'notes';
        $data['icon'] = 'fa-regular fa-sticky-note';
        return $data;
    }

    protected function commonFilters(string $viewName): void
    {
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');

        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->addFilterSelect($viewName, 'nick', 'user', 'nick', $users);

        $interests = $this->codeModel->all('crm_intereses', 'id', 'nombre');
        $this->addFilterSelect($viewName, 'idinteres', 'interest', 'idinteres', $interests);

        $i18n = Tools::lang();
        $sourceModel = new CrmFuente();
        $sourceValues = [
            ['label' => $i18n->trans('sources'), 'where' => []],
            ['label' => '------', 'where' => []],
        ];
        foreach ($sourceModel->all([], ['nombre' => 'ASC'], 0, 0) as $source) {
            $sqlIn = 'SELECT idcontacto FROM contactos WHERE idfuente = ' . $this->dataBase->var2str($source->id);
            $sourceValues[] = [
                'label' => $source->nombre,
                'where' => [new DataBaseWhere('idcontacto', $sqlIn, 'IN')]
            ];
        }
        $this->addFilterSelectWhere($viewName, 'idfuente', $sourceValues, 'source');

        $this->addFilterSelectWhere(
            $viewName,
            'fecha_notificado',
            [
                ['label' => $i18n->trans('notified'), 'where' => []],
                ['label' => '------', 'where' => []],
                ['label' => $i18n->trans('yes'), 'where' => [new DataBaseWhere('fecha_notificado', null, 'IS NOT')]],
                ['label' => $i18n->trans('no'), 'where' => [new DataBaseWhere('fecha_notificado', null)]]
            ],
            'notified'
        );
    }

    protected function createViews()
    {
        $this->createViewNotes();
        $this->createViewNotices();
    }

    protected function createViewNotes(string $viewName = 'ListCrmNota'): void
    {
        $this->addView($viewName, 'CrmNota', 'notes', 'fa-regular fa-sticky-note')
            ->addOrderBy(['fecha', 'hora'], 'date', 2)
            ->addOrderBy(['fechaaviso', 'notice_hour'], 'notice-date')
            ->addSearchFields(['observaciones']);

        $this->commonFilters($viewName);
    }

    protected function createViewNotices(string $viewName = 'ListCrmNota-notices'): void
    {
        $this->addView($viewName, 'CrmNota', 'notices', 'fa-regular fa-calendar-check')
            ->addOrderBy(['fecha', 'hora'], 'date')
            ->addOrderBy(['fechaaviso', 'notice_hour'], 'notice-date', 2)
            ->addSearchFields(['observaciones']);

        $this->commonFilters($viewName);
    }

    /**
     * @param string $viewName
     * @param ListView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListCrmNota-notices':
                $where = $this->permissions->onlyOwnerData ? $this->getOwnerFilter($view->model) : [];
                $where[] = new DataBaseWhere('fechaaviso', null, 'IS NOT');
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
