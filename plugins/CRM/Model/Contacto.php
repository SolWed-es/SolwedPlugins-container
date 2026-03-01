<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Contacto as ParentModel;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\MailNotifier;
use FacturaScripts\Dinamic\Model\Agente;

/**
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Contacto extends ParentModel
{
    /** @var int */
    public $idfuente;

    public function delete(): bool
    {
        $interests = $this->getIntereses();
        $lists = $this->getListas();

        if (false === parent::delete()) {
            return false;
        }

        if (!empty($this->idfuente)) {
            // actualizamos la fuente
            $this->getFuente()->save();
        }

        // actualizamos los intereses
        foreach ($interests as $interest) {
            $interest->save();
        }

        // actualizamos las listas
        foreach ($lists as $list) {
            $list->save();
        }

        return true;
    }

    public function getFuente(): CrmFuente
    {
        $fuente = new CrmFuente();
        $fuente->loadFromCode($this->idfuente);
        return $fuente;
    }

    public function getIntereses(): array
    {
        $list = [];

        $where = [new DataBaseWhere('idcontacto', $this->idcontacto)];
        foreach (CrmInteresContacto::all($where, [], 0, 0) as $item) {
            $list[] = $item->getInteres();
        }

        return $list;
    }

    public function getListas(): array
    {
        $list = [];

        $where = [new DataBaseWhere('idcontacto', $this->idcontacto)];
        foreach (CrmListaContacto::all($where, [], 0, 0) as $item) {
            $list[] = $item->getLista();
        }

        return $list;
    }

    public function install(): string
    {
        // dependencias
        new CrmFuente();
        new CrmInteres();

        return parent::install();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(): bool
    {
        if (false === parent::saveInsert()) {
            return false;
        }

        if ($this->idfuente) {
            // actualizamos la fuente
            $this->getFuente()->save();
        }

        if ($this->codagente) {
            $this->sendNotificationAgent();
        }

        return true;
    }

    protected function saveUpdate(): bool
    {
        // cargamos el agente original
        $original_agent = $this->getOriginal('codagente');

        if (false === parent::saveUpdate()) {
            return false;
        }

        // comprobamos si se ha cambiado el agente
        if ($this->codagente && $this->codagente != $original_agent) {
            $this->sendNotificationAgent();
        }

        return true;
    }

    protected function sendNotificationAgent(): void
    {
        $agent = new Agente();
        if (false === $agent->loadFromCode($this->codagente) || empty($agent->email)) {
            return;
        }

        MailNotifier::send('new-contact-agent', $agent->email, $agent->nombre, [
            'contact' => $this->fullName(),
            'url' => Tools::siteUrl() . '/EditContacto?code=' . $this->idcontacto
        ]);
    }
}
