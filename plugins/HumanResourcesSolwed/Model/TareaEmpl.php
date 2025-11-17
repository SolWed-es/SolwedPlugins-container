<?php
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;

class TareaEmpl extends Base\ModelClass
{
    use ModelTrait;

    /** @var int */
    public $idtareaempl;
    
    /** @var string */
    public $nombre;
    
    /** @var string */
    public $direccion;
    
    /** @var string */
    public $estado;
    
    /** @var string */
    public $fecha;

    public function clear()
    {
        parent::clear();
        $this->fecha = date('Y-m-d');
        $this->estado = 'pendiente';
    }

    public function test(): bool
    {
        if (empty($this->nombre)) {
            $this->toolBox()->i18nLog()->warning('El nombre es obligatorio');
            return false;
        }

        if (empty($this->direccion)) {
            $this->toolBox()->i18nLog()->warning('La dirección es obligatoria');
            return false;
        }

        return parent::test();
    }

    public static function primaryColumn(): string
    {
        return 'idtareaempl';
    }

    public static function tableName(): string
    {
        return 'tareasempl';
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return parent::url($type, 'ListTareaEmpl?activetab=List');
    }
}