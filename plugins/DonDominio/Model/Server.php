<?php

namespace FacturaScripts\Plugins\DonDominio\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

class Server extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string|null */
    public $description;

    /** @var string */
    public $host;

    /** @var string|null */
    public $ip_address;

    /** @var int */
    public $port;

    /** @var string */
    public $username;

    /** @var string */
    public $password;

    /** @var string|null */
    public $created_at;

    /** @var string|null */
    public $updated_at;

    public function clear(): void
    {
        parent::clear();
        $this->port = 8443;
    }

    public static function tableName(): string
    {
        return 'dondominio_servers';
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function install(): string
    {
        // XML table definition handles the installation.
        return '';
    }

    protected function saveInsert(): bool
    {
        $now = Tools::dateTime();
        $this->created_at = $now;
        $this->updated_at = $now;
        return parent::saveInsert();
    }

    protected function saveUpdate(): bool
    {
        $this->updated_at = Tools::dateTime();
        return parent::saveUpdate();
    }
}
