<?php
/**
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\CRM\Model\CrmFuente;
use FacturaScripts\Plugins\CRM\Model\CrmOportunidad;
use FacturaScripts\Plugins\CRM\Model\CrmOportunidadEstado;

/**
 * Description of ListCrmOportunidad
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListCrmOportunidad extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'opportunities';
        $data['icon'] = 'fa-solid fa-trophy';
        return $data;
    }

    protected function changeStatusAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $codes = $this->request->request->get('code');
        if (empty($codes)) {
            Tools::log()->error('no-record-selected');
            return true;
        }

        $this->dataBase->beginTransaction();
        foreach (explode(',', $codes) as $code) {
            $oportunidad = new CrmOportunidad();
            if (false === $oportunidad->loadFromCode($code)) {
                Tools::log()->error('record-not-found');
                continue;
            }

            $oportunidad->idestado = $this->request->request->get('new_id_estado');
            if (false === $oportunidad->save()) {
                Tools::log()->error('record-save-error');
                $this->dataBase->rollback();
                return true;
            }
        }

        Tools::log()->notice('record-updated-correctly');
        $this->dataBase->commit();
        return true;
    }

    protected function createViews()
    {
        // pestaña con todas las oportunidades
        $this->createViewOpportunities();

        // añadimos una pestaña para cada estado de oportunidad
        $where = [new DataBaseWhere('tab', true)];
        foreach (CrmOportunidadEstado::all($where, ['orden' => 'ASC']) as $estado) {
            $viewName = 'ListCrmOportunidad-' . $estado->id;
            $this->createViewCustomOpportunities($viewName, $estado->nombre, $estado->icon);
            $this->addFilterSelectWhere(
                $viewName,
                'idestado',
                [['label' => $estado->nombre, 'where' => [new DataBaseWhere('idestado', $estado->id)]]]
            );

            $this->addButton($viewName, [
                'action' => 'change_status',
                'icon' => 'fa-solid fa-bolt',
                'title' => 'change-status',
                'type' => 'modal'
            ]);

            $this->setSettings($viewName, 'megasearch', false);
        }
    }

    protected function createViewCustomOpportunities(string $viewName, string $label, string $icon): void
    {
        $this->addView($viewName, 'CrmOportunidad', $label, $icon)
            ->addOrderBy(['fecha'], 'date')
            ->addOrderBy(['fechamod'], 'last-update', 2)
            ->addOrderBy(['neto'], 'net')
            ->addSearchFields(['descripcion', 'observaciones']);

        $this->setTabColors($viewName);

        // filters
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');

        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->addFilterSelect($viewName, 'nick', 'user', 'nick', $users);
        $this->addFilterSelect($viewName, 'assigned', 'assigned', 'asignado', $users);

        $this->addFilterAutocomplete($viewName, 'idcontacto', 'contact', 'idcontacto', 'contactos', 'idcontacto', 'nombre');

        $agents = Agentes::codeModel();
        $this->addFilterSelect($viewName, 'codagente', 'agents', 'codagente', $agents);

        $interests = $this->codeModel->all('crm_intereses', 'id', 'nombre');
        $this->addFilterSelect($viewName, 'idinteres', 'interest', 'idinteres', $interests);

        $this->setSourceFilter($viewName);
    }

    protected function createViewOpportunities(string $viewName = 'ListCrmOportunidad'): void
    {
        $this->addView($viewName, 'CrmOportunidad', 'all', 'fa-solid fa-trophy')
            ->addOrderBy(['fecha'], 'date')
            ->addOrderBy(['fechamod'], 'last-update', 2)
            ->addOrderBy(['precio_aprox'], 'approximate-price')
            ->addOrderBy(['neto'], 'net')
            ->addSearchFields(['descripcion', 'observaciones']);

        $this->setTabColors($viewName);

        $this->addButton($viewName, [
            'action' => 'change_status',
            'icon' => 'fa-solid fa-bolt',
            'title' => 'change-status',
            'type' => 'modal'
        ]);

        // filtros
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');

        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->addFilterSelect($viewName, 'nick', 'user', 'nick', $users);
        $this->addFilterSelect($viewName, 'assigned', 'assigned', 'asignado', $users);

        $this->addFilterAutocomplete($viewName, 'idcontacto', 'contact', 'idcontacto', 'contactos', 'idcontacto', 'nombre');

        $agents = Agentes::codeModel();
        $this->addFilterSelect($viewName, 'codagente', 'agents', 'codagente', $agents);

        $interests = $this->codeModel->all('crm_intereses', 'id', 'nombre');
        $this->addFilterSelect($viewName, 'idinteres', 'interest', 'idinteres', $interests);

        $this->setSourceFilter($viewName);
    }

    protected function execPreviousAction($action): bool
    {
        if ($action === 'change_status') {
            return $this->changeStatusAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function setSourceFilter(string $viewName): void
    {
        $sourceModel = new CrmFuente();
        $sourceValues = [
            ['label' => Tools::trans('sources'), 'where' => []],
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
    }

    protected function setTabColors(string $viewName): void
    {
        $crmOpoEstado = new CrmOportunidadEstado();
        foreach ($crmOpoEstado->all([], [], 0, 0) as $estado) {
            if (empty($estado->color)) {
                continue;
            }

            $row = $this->tab($viewName)->getRow('status');
            if (empty($row)) {
                continue;
            }

            $row->options[] = [
                'tag' => 'option',
                'children' => [],
                'color' => $estado->color,
                'fieldname' => 'idestado',
                'text' => $estado->id,
                'title' => $estado->nombre
            ];
        }
    }
}
