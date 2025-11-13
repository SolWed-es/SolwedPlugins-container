<?php

namespace FacturaScripts\Plugins\PleskServers\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

class PleskServer extends ModelClass
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

    /** @var int */
    public $port;

    /** @var string */
    public $auth_type;

    /** @var string|null */
    public $api_key;

    /** @var string|null */
    public $username;

    /** @var string|null */
    public $password;

    /** @var bool */
    public $verify_ssl;

    /** @var int */
    public $timeout;

    /** @var bool */
    public $active;

    /** @var string|null */
    public $created_at;

    /** @var string|null */
    public $updated_at;

    public function clear(): void
    {
        parent::clear();
        $this->port = 8443;
        $this->auth_type = 'api_key';
        $this->verify_ssl = true;
        $this->timeout = 30;
        $this->active = true;
    }

    public static function tableName(): string
    {
        return 'plesk_servers';
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

    /**
     * Test connection to the Plesk server
     */
    public function testConnection(): bool
    {
        try {
            // TODO: Implement API call to test connection
            return true;
        } catch (\Exception $e) {
            Tools::log()->error('Error testing Plesk connection: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get API URL for this server
     */
    public function getApiUrl(): string
    {
        $protocol = $this->verify_ssl ? 'https' : 'http';
        return $protocol . '://' . $this->host . ':' . $this->port . '/api/v2';
    }
}
