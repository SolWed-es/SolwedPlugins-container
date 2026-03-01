<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Description of CrmLista
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CrmLista extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $fecha;

    /** @var int */
    public $id;

    /** @var string */
    public $nombre;

    /** @var int */
    public $numcontactos;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
        $this->numcontactos = 0;
    }

    /**
     * @return CrmListaContacto[]
     */
    public function getMembers(): array
    {
        $where = [new DataBaseWhere('idlista', $this->id())];
        return CrmListaContacto::all($where, [], 0, 0);
    }

    /**
     * Elimina todos los miembros de la lista
     * @return bool False si ha fallado algun delete
     */
    public function removeAllContactos(): bool
    {
        $where = [new Where('idlista', $this->id())];
        $crmListasContactos = CrmListaContacto::all($where);

        foreach ($crmListasContactos as $crmListasContacto) {
            if (false === $crmListasContacto->delete()) {
                return false;
            }
        }

        return true;
    }

    public static function tableName(): string
    {
        return 'crm_listas';
    }

    public function test(): bool
    {
        $this->nombre = Tools::noHtml($this->nombre);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListContacto?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveUpdate(): bool
    {
        // get the number of contacts in this list
        $where = [new DataBaseWhere('idlista', $this->id())];
        $this->numcontactos = CrmListaContacto::count($where);

        return parent::saveUpdate();
    }
}
