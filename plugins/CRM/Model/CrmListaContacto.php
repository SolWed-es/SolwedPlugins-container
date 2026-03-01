<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;
use FacturaScripts\Dinamic\Model\CrmLista as DinLista;

/**
 * Description of CrmListaContacto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CrmListaContacto extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $fecha;

    /** @var int */
    public $id;

    /** @var int */
    public $idcontacto;

    /** @var int */
    public $idlista;

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

        // actualizamos la lista
        $this->getLista()->save();

        return true;
    }

    /**
     * @return Contacto
     */
    public function getContact()
    {
        $contact = new DinContacto();
        $contact->loadFromCode($this->idcontacto);
        return $contact;
    }

    /**
     * @return CrmLista
     */
    public function getLista()
    {
        $lista = new DinLista();
        $lista->loadFromCode($this->idlista);
        return $lista;
    }

    public function install(): string
    {
        new DinContacto();
        new DinLista();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'crm_listas_contactos';
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

        // actualizamos la lista
        $this->getLista()->save();

        return true;
    }
}
