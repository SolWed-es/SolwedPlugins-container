<?php

namespace FacturaScripts\Plugins\DonDominio\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Modelo para almacenar contactos de DonDominio relacionados con clientes.
 */
class DomainContact extends ModelClass
{
    use ModelTrait;

    public $id;
    public $codcliente;
    public $contact_id;
    public $name;
    public $email;
    public $phone;
    public $tax_number;
    public $country;
    public $verification_status;
    public $daaccepted;
    public $raw_data;
    public $synced_at;
    public $created_at;
    public $updated_at;

    public static function tableName(): string
    {
        return 'clientes_dondominio_dominios_contactos';
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function save(): bool
    {
        $now = Tools::dateTime();
        if (empty($this->created_at)) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        $this->daaccepted = (bool) $this->daaccepted;
        return parent::save();
    }

    public function test(): bool
    {
        $this->codcliente = trim((string) $this->codcliente);
        $this->contact_id = trim((string) $this->contact_id);
        return parent::test();
    }
}
