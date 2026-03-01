<?php
/**
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;
use FacturaScripts\Dinamic\Model\CrmCampaign as DinCampaign;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class CrmCampaignEmail extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $creation_date;

    /** @var string */
    public $email;

    /** @var int */
    public $id;

    /** @var int */
    public $id_campaign;

    /** @var int */
    public $id_contacto;

    /** @var string */
    public $outgoing_email;

    /** @var string */
    public $sent;

    public function clear(): void
    {
        parent::clear();
        $this->creation_date = Tools::dateTime();
    }

    public function delete(): bool
    {
        if (false === parent::delete()) {
            return false;
        }

        $this->getCampaign()->save();

        return true;
    }

    public function getCampaign(): CrmCampaign
    {
        $campaign = new DinCampaign();
        $campaign->loadFromCode($this->id_campaign);
        return $campaign;
    }

    public function getContact(): DinContacto
    {
        $contact = new DinContacto();
        $contact->loadFromCode($this->id_contacto);
        return $contact;
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        $this->getCampaign()->save();

        return true;
    }

    public static function tableName(): string
    {
        return 'crm_campaigns_emails';
    }

    public function test(): bool
    {
        $this->creation_date = $this->creation_date ?? Tools::dateTime();
        $this->email = Tools::noHtml($this->email);
        $this->outgoing_email = Tools::noHtml($this->outgoing_email);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return empty($this->id_contacto) ? parent::url($type, $list) : $this->getContact()->url();
    }
}
