<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Users;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\MailNotifier;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;
use FacturaScripts\Dinamic\Model\User;

/**
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CrmNota extends ModelClass
{
    use ModelTrait;

    /** @var bool */
    public $automatica;

    /** @var string */
    public $documento;

    /** @var string */
    public $fecha;

    /** @var string */
    public $fechaaviso;

    /** @var string */
    public $fecha_notificado;

    /** @var string */
    public $hora;

    /** @var int */
    public $id;

    /** @var int */
    public $idcontacto;

    /** @var int */
    public $iddocumento;

    /** @var int */
    public $idinteres;

    /** @var int */
    public $idoportunidad;

    /** @var string */
    public $nick;

    /** @var string */
    public $notice_hour;

    /** @var string */
    public $observaciones;

    /** @var bool */
    public $paratodos;

    /** @var string */
    public $tipodocumento;

    public function clear(): void
    {
        parent::clear();
        $this->automatica = false;
        $this->fecha = Tools::date();
        $this->hora = Tools::hour();
        $this->nick = Session::user()->nick;
        $this->paratodos = false;
    }

    public function getContact(): DinContacto
    {
        $contact = new DinContacto();
        $contact->loadFromCode($this->idcontacto);
        return $contact;
    }

    public function install(): string
    {
        // needed dependencies
        new CrmInteres();
        new CrmOportunidad();

        return parent::install();
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        // save contact interest
        if ($this->idinteres) {
            $relation = new CrmInteresContacto();
            $where = [
                new DataBaseWhere('idcontacto', $this->idcontacto),
                new DataBaseWhere('idinteres', $this->idinteres)
            ];
            if (false === $relation->loadFromCode('', $where)) {
                $relation->idcontacto = $this->idcontacto;
                $relation->idinteres = $this->idinteres;
                $relation->save();
            }
        }

        return true;
    }

    protected function saveInsert(): bool
    {
        if (false === parent::saveInsert()) {
            return false;
        }

        // si la nota pertenece a una oportunidad, debemos notificar
        $oportunidad = new CrmOportunidad();
        if (empty($this->idoportunidad) || false === $oportunidad->loadFromCode($this->idoportunidad)) {
            return true;
        }

        $oppoAuthor = new User();
        if ($oportunidad->nick && $this->nick != $oportunidad->nick && $oppoAuthor->loadFromCode($oportunidad->nick)) {
            $this->sendNotificationNewNote($this->nick, $oppoAuthor->email, $oppoAuthor->nick, $oportunidad->id);
        }

        $oppoAssigned = new User();
        if ($oportunidad->asignado && $this->nick != $oportunidad->asignado && $oppoAssigned->loadFromCode($oportunidad->asignado)) {
            $this->sendNotificationNewNote($this->nick, $oppoAssigned->email, $oppoAssigned->nick, $oportunidad->id);
        }

        $agent = new Agente();
        if ($oportunidad->codagente && $agent->loadFromCode($oportunidad->codagente) && $agent->email && $agent->email != Users::get($this->nick)->email) {
            $this->sendNotificationNewNote($this->nick, $agent->email, $agent->nombre, $oportunidad->id);
        }

        return true;
    }

    public static function tableName(): string
    {
        return 'crm_notas';
    }

    public function test(): bool
    {
        if ($this->isDirty('fechaaviso')) {
            $this->fecha_notificado = null;
        }

        $this->observaciones = Tools::noHtml($this->observaciones);

        return parent::test();
    }

    public function sendNotificationNewNote(string $senderNick, string $email, string $name, int $number): void
    {
        if (empty($email)) {
            return;
        }

        MailNotifier::send('new-opportunity-note', $email, $name, [
            'number' => $number,
            'senderNick' => $senderNick,
            'url' => Tools::siteUrl() . '/EditCrmOportunidad?code=' . $number
        ]);
    }
}
