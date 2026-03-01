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

/**
 * Description of CrmFuente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CrmFuente extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $descripcion;

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

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public static function tableName(): string
    {
        return 'crm_fuentes2';
    }

    public function test(): bool
    {
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->nombre = Tools::noHtml($this->nombre);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'AdminCRM?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveUpdate(): bool
    {
        // get the number of contacts with this source
        $where = [new DataBaseWhere('idfuente', $this->id())];
        $this->numcontactos = Contacto::count($where);

        return parent::saveUpdate();
    }
}
