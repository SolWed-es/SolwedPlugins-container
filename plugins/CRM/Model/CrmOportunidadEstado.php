<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Model;

use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Description of CrmOportunidadEstado
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CrmOportunidadEstado extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $color;

    /** @var bool */
    public $editable;

    /** @var string */
    public $icon;

    /** @var int */
    public $id;

    /** @var string */
    public $nombre;

    /** @var int */
    public $orden;

    /** @var bool */
    public $predeterminado;

    /** @var bool */
    public $rechazado;

    /** @var bool */
    public $tab;

    public function clear(): void
    {
        parent::clear();
        $this->editable = true;
        $this->icon = 'fa-solid fa-tag';
        $this->orden = 100;
        $this->predeterminado = false;
        $this->rechazado = false;
        $this->tab = true;
    }

    /**
     * Allows to use this model as source in CodeModel special model.
     *
     * @param string $fieldCode
     *
     * @return CodeModel[]
     */
    public function codeModelAll(string $fieldCode = ''): array
    {
        $results = [];
        $field = empty($fieldCode) ? static::primaryColumn() : $fieldCode;

        $sql = 'SELECT DISTINCT ' . $field . ' AS code, ' . $this->primaryDescriptionColumn() . ' AS description, orden '
            . 'FROM ' . static::tableName() . ' ORDER BY orden ASC';
        foreach (self::$dataBase->selectLimit($sql, CodeModel::ALL_LIMIT) as $d) {
            $results[] = new CodeModel($d);
        }

        return $results;
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public static function tableName(): string
    {
        return 'crm_oportunidades_estados';
    }

    public function test(): bool
    {
        $this->color = Tools::noHtml($this->color);
        $this->icon = Tools::noHtml($this->icon);
        $this->nombre = Tools::noHtml($this->nombre);

        return parent::test();
    }
}
