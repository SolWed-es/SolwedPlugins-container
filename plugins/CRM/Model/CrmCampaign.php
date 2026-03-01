<?php
/**
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;
use FacturaScripts\Dinamic\Model\CrmCampaignEmail as DinCampaignEmail;
use FacturaScripts\Dinamic\Model\CrmLista as DinLista;
use FacturaScripts\Dinamic\Model\User;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class CrmCampaign extends ModelClass
{
    use ModelTrait;

    const STATUS_DRAFT = 0;
    const STATUS_SENDING = 1;
    const STATUS_SENT = 2;

    /** @var string */
    public $content;

    /** @var string */
    public $creation_date;

    /** @var int */
    public $id;

    /** @var string */
    public $last_nick;

    /** @var string */
    public $last_update;

    /** @var string */
    public $name;

    /** @var string */
    public $nick;

    /** @var int */
    public $num_emails;

    /** @var int */
    public $num_sent;

    /** @var int */
    public $send_end_hour;

    /** @var int */
    public $send_start_hour;

    /** @var int */
    public $status;

    /** @var string */
    public $subject;

    public function addContact(DinContacto $contact, string $outgoing_email = ''): bool
    {
        $member = new DinCampaignEmail();
        $where = [
            new DataBaseWhere('id_campaign', $this->id),
            new DataBaseWhere('outgoing_email', $outgoing_email),
            new DataBaseWhere('email', $contact->email),
        ];
        if ($member->loadFromCode('', $where)) {
            return true;
        }

        $member->id_campaign = $this->id;
        $member->id_contacto = $contact->idcontacto;
        $member->outgoing_email = $outgoing_email;
        $member->email = $contact->email;

        return $member->save();
    }

    public function addList(int $id_list, string $outgoing_email = ''): bool
    {
        // comprobamos que la lista de contactos existe
        $list = new DinLista();
        if (false === $list->loadFromCode($id_list)) {
            Tools::log()->error('list-not-found');
            return false;
        }

        // obtenemos los contactos de la lista
        $contacts = [];
        foreach ($list->getMembers() as $member) {
            $contact = $member->getContact();
            if (false === empty($contact->email) && false === in_array($contact, $contacts)) {
                $contacts[] = $contact;
            }
        }

        // comprobamos que hay contactos en la lista
        if (empty($contacts)) {
            Tools::log()->error('no-contacts-in-list');
            return false;
        }

        // añadimos los contactos a la campaña, si no existen
        $add = 0;
        foreach ($contacts as $contact) {
            if ($this->addContact($contact, $outgoing_email)) {
                $add++;
            }
        }

        Tools::log()->notice('added-contacts-campaign', [
            '%count%' => $add,
        ]);

        // guardamos la campaña
        $this->save();

        return true;
    }

    /** Comprueba si el momento actual está dentro del horario configurado. */
    public function canSendNow(?string $dateTime = null): bool
    {
        $currentDateTime = $dateTime ?? Tools::dateTime();

        if (false === Validator::datetime($currentDateTime)) {
            Tools::log()->error('invalid-datetime', [
                '%datetime%' => $currentDateTime,
            ]);
        }

        $currentHour = (int)date('H', strtotime($currentDateTime));

        return $currentHour >= $this->send_start_hour && $currentHour <= $this->send_end_hour;
    }

    public function clear(): void
    {
        parent::clear();
        $this->creation_date = Tools::dateTime();
        $this->nick = Session::user()->nick;
        $this->num_emails = 0;
        $this->num_sent = 0;
        $this->send_start_hour = 8;
        $this->send_end_hour = 16;
        $this->status = self::STATUS_DRAFT;
    }

    public function getAttachments(): array
    {
        $attachments = [];

        $where = [
            new DataBaseWhere('model', $this->modelClassName()),
            new DataBaseWhere('modelid', $this->id()),
        ];
        foreach (AttachedFileRelation::all($where, [], 0, 0) as $attachment) {
            $attachments[] = $attachment->getFile();
        }

        return $attachments;
    }

    public function getEmails(): array
    {
        $where = [new DataBaseWhere('id_campaign', $this->id)];
        return DinCampaignEmail::all($where, [], 0, 0);
    }

    public function getPendingEmails(int $limit = 50): array
    {
        $where = [
            new DataBaseWhere('id_campaign', $this->id),
            new DataBaseWhere('sent', null),
        ];
        return DinCampaignEmail::all($where, [], 0, $limit);
    }

    public function install(): string
    {
        // dependencias
        new User();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'crm_campaigns';
    }

    public function test(): bool
    {
        $this->content = Tools::noHtml($this->content);
        $this->creation_date = $this->creation_date ?? Tools::dateTime();
        $this->name = Tools::noHtml($this->name);
        $this->nick = $this->nick ?? Session::user()->nick;
        $this->subject = Tools::noHtml($this->subject);

        return parent::test() && $this->testHours();
    }

    public function url(string $type = 'auto', string $list = 'ListContacto?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveUpdate(): bool
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();

        $this->updateStats();

        return parent::saveUpdate();
    }

    protected function testHours(): bool
    {
        $startHour = (int)$this->send_start_hour;
        $endHour = (int)$this->send_end_hour;

        if ($startHour < 0 || $startHour > 23) {
            Tools::log()->warning('invalid-send-start-hour');
            return false;
        }
        if ($endHour < 0 || $endHour > 23) {
            Tools::log()->warning('invalid-send-end-hour');
            return false;
        }

        // verificar que no están al reves
        if ($startHour > $endHour) {
            Tools::log()->warning('invalid-send-hour-range');
            return false;
        }

        $this->send_start_hour = $startHour;
        $this->send_end_hour = $endHour;

        return true;
    }

    protected function updateStats(): void
    {
        $this->num_emails = 0;
        $this->num_sent = 0;

        $emails = $this->getEmails();
        if (empty($emails)) {
            return;
        }

        $this->num_emails = count($emails);

        foreach ($emails as $email) {
            if ($email->sent) {
                $this->num_sent++;
            }
        }

        if ($this->status === self::STATUS_SENDING && $this->num_sent >= $this->num_emails) {
            $this->status = self::STATUS_SENT;
        }
    }
}
