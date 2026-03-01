<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Description of CrmInteresContacto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CrmInteresContacto extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $fecha;

    /** @var int */
    public $id;

    /** @var int */
    public $idcontacto;

    /** @var int */
    public $idinteres;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
    }

    public function delete(): bool
    {
        if (false === parent::delete()) {
            return false;
        }

        // force interest update
        $this->getInteres()->save();

        return true;
    }

    /**
     * @return Contacto
     */
    public function getContact()
    {
        $contact = new Contacto();
        $contact->loadFromCode($this->idcontacto);
        return $contact;
    }

    public function getInteres(): CrmInteres
    {
        $interest = new CrmInteres();
        $interest->loadFromCode($this->idinteres);
        return $interest;
    }

    public static function tableName(): string
    {
        return 'crm_intereses_contactos';
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return empty($this->idcontacto) ? parent::url($type, $list) : $this->getContact()->url();
    }

    protected function saveInsert(): bool
    {
        if (false === parent::saveInsert()) {
            return false;
        }

        // force interest update
        $this->getInteres()->save();

        return true;
    }
}
