<?php

namespace FacturaScripts\Plugins\DonDominio\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Modelo para gestionar dominios de clientes en DonDominio
 */
class Domain extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $id;

    /** @var string */
    public $codcliente;

    /** @var string */
    public $domain;

    /** @var string */
    public $domain_id;

    /** @var string */
    public $contact_id;

    /** @var string */
    public $provider;

    /** @var string */
    public $status;

    /** @var string */
    public $expires_at;

    /** @var bool */
    public $autorenew;

    /** @var string|null */
    public $tld;

    /** @var string|null */
    public $registered_at;

    /** @var string|null */
    public $renewal_mode;

    /** @var bool */
    public $renewable;

    /** @var bool */
    public $transfer_block;

    /** @var bool */
    public $modify_block;

    /** @var bool */
    public $whois_privacy;

    /** @var string|null */
    public $owner_verification;

    /** @var bool */
    public $service_associated;

    /** @var string|null */
    public $tag;

    /** @var bool */
    public $authcode_check;

    /** @var bool */
    public $view_whois;

    /** @var string|null */
    public $registrant_contact;

    /** @var string|null */
    public $admin_contact;

    /** @var string|null */
    public $tech_contact;

    /** @var string|null */
    public $billing_contact;

    /** @var string */
    public $raw_data;

    /** @var string */
    public $synced_at;

    /** @var string */
    public $created_at;

    /** @var string */
    public $updated_at;

    public function getCliente()
    {
        // Optimización: evitar cargar cliente completo si no es necesario
        static $clientesCache = [];

        if (!isset($clientesCache[$this->codcliente])) {
            $cliente = new ClienteDonDominio();
            $cliente->loadFromCode($this->codcliente);
            $clientesCache[$this->codcliente] = $cliente;

            // Limitar cache a 50 clientes para evitar crecimiento excesivo
            if (count($clientesCache) > 50) {
                array_shift($clientesCache);
            }
        }

        return $clientesCache[$this->codcliente];
    }

    public static function tableName(): string
    {
        return 'clientes_dondominio_dominios';
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'domain';
    }

    public function save(): bool
    {
        if (empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');

        return parent::save();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        if ('edit' === $type && $this->codcliente) {
            return 'EditCliente?code=' . $this->codcliente . '&activetab=EditClienteDominio';
        }

        return parent::url($type, $list);
    }





    public function test(): bool
    {
        $this->domain = trim($this->domain);
        if (empty($this->domain)) {
            Tools::log()->warning('domain-cannot-be-empty');
            return false;
        }

        $this->codcliente = trim($this->codcliente);
        if (empty($this->codcliente)) {
            Tools::log()->warning('customer-cannot-be-empty');
            return false;
        }

        $this->provider = $this->provider ?: 'manual';
        $this->autorenew = (bool) $this->autorenew;

        return parent::test();
    }
    

    
    
}
