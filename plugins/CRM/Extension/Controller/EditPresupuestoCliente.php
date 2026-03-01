<?php
/**
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;

use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Plugins\CRM\Model\CrmNota;
use FacturaScripts\Plugins\CRM\Model\CrmOportunidad;

/**
 * Description of EditPresupuestoCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditPresupuestoCliente
{
    public function getCrmNotes(): Closure
    {
        return function () {
            // direct notes
            $crmNote = new CrmNota();
            $where = [
                new DataBaseWhere('documento', $this->getViewModelValue($this->getMainViewName(), 'codigo')),
                new DataBaseWhere('idoportunidad', null, 'IS'),
                new DataBaseWhere('tipodocumento', 'presupuesto de cliente')
            ];
            $order = ['fecha' => 'DESC', 'hora' => 'DESC'];
            $notes = $crmNote->all($where, $order, 0, 0);

            // opportunity notes
            foreach ($this->views['crm']->cursor as $oportunity) {
                foreach ($oportunity->getNotas() as $note) {
                    $notes[] = $note;
                }
            }

            return $notes;
        };
    }

    protected function createViews(): Closure
    {
        return function () {
            $viewName = 'crm';
            $this->addHTMLView($viewName, 'Tab/CrmOportunidad', 'CrmOportunidad', 'crm', 'fa-regular fa-sticky-note');
        };
    }

    protected function editCrmNoteAction(): Closure
    {
        return function () {
            $nota = new CrmNota();
            $id = $this->request->request->get('id');
            if (false === $nota->loadFromCode($id)) {
                return;
            }

            $nota->observaciones = $this->request->request->get('observaciones');
            $nota->fechaaviso = $this->request->request->get('fechaaviso');
            if (empty($nota->fechaaviso)) {
                $nota->fechaaviso = null;
            }

            if ($nota->save()) {
                Tools::log()->notice('record-updated-correctly');
                return;
            }

            Tools::log()->warning('record-updated-error');
        };
    }

    protected function execPreviousAction(): Closure
    {
        return function ($action) {
            switch ($action) {
                case 'edit-crm-note':
                    $this->editCrmNoteAction();
                    break;

                case 'new-crm-note':
                    $this->newCrmNoteAction();
                    break;
            }
        };
    }

    protected function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName === 'crm') {
                $code = $this->getViewModelValue($this->getMainViewName(), 'idpresupuesto');
                $where = [new DataBaseWhere('idpresupuesto', $code)];
                $view->loadData('', $where);
            }
        };
    }

    protected function newCrmNoteAction(): Closure
    {
        return function () {
            $presupuesto = new PresupuestoCliente();
            if (false === $presupuesto->loadFromCode($this->request->query->get('code'))) {
                Tools::log()->warning('record-not-found');
                return;
            }

            $oportunidad = new CrmOportunidad();
            $id = $this->request->request->get('idoportunidad');
            if (empty($id) || false === $oportunidad->loadFromCode($id)) {
                $oportunidad->codagente = $presupuesto->codagente;
                $oportunidad->coddivisa = $presupuesto->coddivisa;
                $oportunidad->descripcion = Tools::trans('estimation') . ' ' . $presupuesto->codigo;
                $oportunidad->idcontacto = $presupuesto->idcontactofact;
                $oportunidad->idpresupuesto = $presupuesto->idpresupuesto;
                $oportunidad->nick = $this->user->nick;
                $oportunidad->tasaconv = $presupuesto->tasaconv;
                $oportunidad->save();
            }

            $nota = new CrmNota();
            $nota->idcontacto = $oportunidad->idcontacto;
            $nota->idoportunidad = $oportunidad->id;
            $nota->nick = $this->user->nick;
            $nota->observaciones = $this->request->request->get('observaciones');
            $nota->fechaaviso = $this->request->request->get('fechaaviso');
            if (empty($nota->fechaaviso)) {
                $nota->fechaaviso = null;
            }

            if ($nota->save()) {
                Tools::log()->notice('record-updated-correctly');
                return;
            }

            Tools::log()->warning('record-updated-error');
        };
    }
}
