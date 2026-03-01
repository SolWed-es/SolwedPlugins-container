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
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\User;

/**
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class CrmOportunidad extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $asignado;

    /** @var string */
    public $codagente;

    /** @var string */
    public $coddivisa;

    /** @var string */
    public $descripcion;

    /** @var bool */
    public $editable;

    /** @var string */
    public $fecha;

    /** @var string */
    public $fechamod;

    /** @var string */
    public $fecha_cierre;

    /** @var string */
    public $hora;

    /** @var int */
    public $id;

    /** @var int */
    public $idcontacto;

    /** @var int */
    public $idestado;

    /** @var int */
    public $idfuente;

    /** @var int */
    public $idinteres;

    /** @var int */
    public $idpresupuesto;

    /** @var float */
    public $neto;

    /** @var float */
    public $netoeuros;

    /** @var string */
    public $nick;

    /** @var string */
    public $observaciones;

    /** @var float */
    public $precio_aprox;

    /** @var bool */
    public $rechazado;

    /** @var float */
    public $tasaconv;

    public function clear(): void
    {
        parent::clear();
        $this->precio_aprox = 0.0;
        $this->fecha = Tools::date();
        $this->fechamod = Tools::dateTime();
        $this->hora = Tools::hour();
        $this->neto = 0.0;
        $this->netoeuros = 0.0;
        $this->tasaconv = 1.0;

        // set estado
        foreach (CrmOportunidadEstado::all([], [], 0, 0) as $estado) {
            if ($estado->predeterminado) {
                $this->editable = $estado->editable;
                $this->idestado = $estado->id;
                $this->rechazado = $estado->rechazado;
                break;
            }
        }
    }

    public function getContacto(): Contacto
    {
        $contact = new Contacto();
        $contact->loadFromCode($this->idcontacto);
        return $contact;
    }

    public function getEstado(): CrmOportunidadEstado
    {
        $estado = new CrmOportunidadEstado();
        $estado->loadFromCode($this->idestado);
        return $estado;
    }

    public function getNotas(): array
    {
        $where = [new DataBaseWhere('idoportunidad', $this->id)];
        $order = ['fecha' => 'DESC', 'hora' => 'DESC'];
        return CrmNota::all($where, $order, 0, 0);
    }

    public function getPresupuesto(): PresupuestoCliente
    {
        $presupuesto = new PresupuestoCliente();
        $presupuesto->loadFromCode($this->idpresupuesto);
        return $presupuesto;
    }

    public function install(): string
    {
        // needed dependency
        new CrmOportunidadEstado();
        new PresupuestoCliente();

        return parent::install();
    }

    public function notifyNewFile($senderNick)
    {
        $user = new User();
        if ($user->loadFromCode($this->nick) && $this->nick != $senderNick) {
            $this->sendNotificationNewFile($senderNick, $user->email, $user->nick);
        }

        if ($this->asignado && $user->loadFromCode($this->asignado) && $this->asignado != $senderNick) {
            $this->sendNotificationNewFile($senderNick, $user->email, $user->nick);
        }

        $agent = new Agente();
        if ($this->codagente && $agent->loadFromCode($this->codagente) && $agent->email != Users::get($senderNick)->email) {
            $this->sendNotificationNewFile($senderNick, $agent->getContact()->email, $agent->nombre);
        }
    }

    public function primaryDescriptionColumn(): string
    {
        return 'id';
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        // save contact interest
        if ($this->idinteres && $this->idcontacto) {
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

    public static function tableName(): string
    {
        return 'crm_oportunidades';
    }

    public function test(): bool
    {
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->observaciones = Tools::noHtml($this->observaciones);

        return parent::test();
    }

    protected function onChange(string $field): bool
    {
        switch ($field) {
            case 'asignado':
                $this->sendNotificationAssigned();
                break;

            case 'codagente':
                $this->sendNotificationAgent();
                break;

            case 'idestado':
                $estado = $this->getEstado();
                $this->editable = $estado->editable;
                $this->idestado = $estado->id;
                $this->rechazado = $estado->rechazado;
                $this->fecha_cierre = $estado->editable ? null : Tools::date();
                break;
        }

        return parent::onChange($field);
    }

    protected function onInsert(): void
    {
        if ($this->asignado) {
            $this->sendNotificationAssigned();
        }

        if ($this->codagente) {
            $this->sendNotificationAgent();
        }

        parent::onInsert();
    }

    protected function saveInsert(): bool
    {
        // si hay agente, continuamos
        if (false === empty($this->codagente)) {
            return parent::saveInsert();
        }

        // obtenemos el contacto y el usuario actual
        $contact = $this->getContacto();
        $user = Session::user();

        // si el contacto tiene agente, se lo asignamos
        if (false === empty($contact->codagente)) {
            $this->codagente = $contact->codagente;
        } elseif (false === empty($user->codagente)) {
            // si el usuario tiene agente, se lo asignamos
            $this->codagente = $user->codagente;
        }

        return parent::saveInsert();
    }

    protected function saveUpdate(): bool
    {
        $this->fechamod = Tools::dateTime();

        return parent::saveUpdate();
    }

    protected function sendNotificationAgent(): void
    {
        $agent = new Agente();
        if (false === $agent->loadFromCode($this->codagente) || empty($agent->email)) {
            return;
        }

        MailNotifier::send('new-opportunity-agent', $agent->email, $agent->nombre, [
            'number' => $this->id,
            'user' => $this->nick,
            'contact' => $this->getContacto()->fullName(),
            'url' => Tools::siteUrl() . '/EditCrmOportunidad?code=' . $this->id
        ]);
    }

    protected function sendNotificationAssigned(): void
    {
        $assigned = Users::get($this->asignado);
        if (empty($assigned->email)) {
            return;
        }

        MailNotifier::send('new-opportunity-assignee', $assigned->email, $assigned->nick, [
            'number' => $this->id,
            'user' => $this->nick,
            'contact' => $this->getContacto()->fullName(),
            'url' => Tools::siteUrl() . '/EditCrmOportunidad?code=' . $this->id
        ]);
    }

    public function sendNotificationNewFile(string $senderNick, string $email, string $name): void
    {
        if (empty($email)) {
            return;
        }

        MailNotifier::send('new-opportunity-file', $email, $name, [
            'number' => $this->id,
            'senderNick' => $senderNick,
            'contact' => $this->getContacto()->fullName(),
            'url' => Tools::siteUrl() . '/EditCrmOportunidad?code=' . $this->id
        ]);
    }
}
