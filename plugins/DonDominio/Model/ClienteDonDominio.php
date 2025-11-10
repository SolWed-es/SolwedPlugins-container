<?php

namespace FacturaScripts\Plugins\DonDominio\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioContactService;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioDomainService;

/**
 * Extensión del modelo Cliente para integración con DonDominio
 */
class ClienteDonDominio extends Cliente
{
    /** @var string */
    public $dondominio_id;

    /** @var string */
    public $mail_server_url;

    /** @var string */
    public $web_server_url;

    /** @var string */
    public $erp_url;

    /** @var string */
    public $mail_username;

    /** @var string */
    public $web_username;

    /** @var string */
    public $erp_user;

    /** @var string */
    public $erp_password;


    /**
     * Obtiene el ID de DonDominio buscando por número fiscal
     */
    public function getDonDominioId(): ?string
    {
        if (!empty($this->dondominio_id)) {
            return $this->dondominio_id;
        }

        $this->syncDonDominioId();
        return $this->dondominio_id;
    }

    /**
     * Obtiene el nombre de la tabla
     */
    public static function tableName(): string
    {
        return 'clientes';
    }

    /**
     * Instala el modelo y agrega la columna si es necesaria
     */
    public function install(): string
    {
        $result = parent::install();

        $this->addPluginColumns();

        return $result;
    }

    /**
     * Agrega las columnas necesarias a la tabla si no existen
     */
    private function addPluginColumns(): void
    {
        $this->ensureColumnExists('dondominio_id', "ALTER TABLE %s ADD COLUMN dondominio_id VARCHAR(50) NULL DEFAULT NULL");
        $this->ensureColumnExists('mail_server_url', "ALTER TABLE %s ADD COLUMN mail_server_url VARCHAR(255) NULL DEFAULT NULL");
        $this->ensureColumnExists('web_server_url', "ALTER TABLE %s ADD COLUMN web_server_url VARCHAR(255) NULL DEFAULT NULL");
        $this->ensureColumnExists('erp_url', "ALTER TABLE %s ADD COLUMN erp_url VARCHAR(255) NULL DEFAULT NULL");
        $this->ensureColumnExists('mail_username', "ALTER TABLE %s ADD COLUMN mail_username VARCHAR(100) NULL DEFAULT NULL");
        $this->ensureColumnExists('web_username', "ALTER TABLE %s ADD COLUMN web_username VARCHAR(100) NULL DEFAULT NULL");
        $this->ensureColumnExists('erp_user', "ALTER TABLE %s ADD COLUMN erp_user VARCHAR(100) NULL DEFAULT NULL");
        $this->ensureColumnExists('erp_password', "ALTER TABLE %s ADD COLUMN erp_password VARCHAR(255) NULL DEFAULT NULL");
    }

    private function ensureColumnExists(string $columnName, string $sqlTemplate): void
    {
        try {
            $db = static::db();

            $columns = $db->getColumns(static::tableName());
            foreach ($columns as $column) {
                if ($column['name'] === $columnName) {
                    return;
                }
            }

            $sql = sprintf($sqlTemplate, static::tableName());
            $db->exec($sql);
            Tools::log()->notice(sprintf('Columna %s agregada a la tabla clientes', $columnName));
        } catch (\Exception $e) {
            Tools::log()->error(sprintf('Error al agregar columna %s: %s', $columnName, $e->getMessage()));
        }
    }

    /**
     * Guarda el cliente y busca el ID de DonDominio si es necesario
     */
    protected function saveInsert(): bool
    {
        $result = parent::saveInsert();

        if ($result) {
            $this->syncDonDominioId();
        }

        return $result;
    }

    /**
     * Actualiza el cliente y busca el ID de DonDominio si es necesario
     */
    protected function saveUpdate(): bool
    {
        $result = parent::saveUpdate();

        if ($result) {
            $this->syncDonDominioId();
        }

        return $result;
    }

    private function persistDonDominioId(?string $donDominioId): void
    {
        if (empty($donDominioId)) {
            return;
        }

        $updated = static::table()
            ->whereEq($this->primaryColumn(), $this->id())
            ->update(['dondominio_id' => $donDominioId]);

        if ($updated) {
            $this->dondominio_id = $donDominioId;
        }
    }

    private function syncDonDominioId(): void
    {
        if (empty($this->cifnif) || !empty($this->dondominio_id)) {
            return;
        }

        $candidates = DonDominioContactService::findContactsByTaxNumber($this->cifnif);
        if (count($candidates) !== 1) {
            return;
        }

        $donDominioId = DonDominioContactService::extractContactIdentifier($candidates[0]);
        if (empty($donDominioId)) {
            return;
        }

        $this->persistDonDominioId($donDominioId);
    }

    /**
     * Sincroniza los dominios del cliente desde DonDominio.
     */
    public function syncDomains(?string $contactId = null): int
    {
        return DonDominioDomainService::syncDomains($this, $contactId);
    }

    /**
     * Devuelve los dominios almacenados localmente para este cliente.
     */
    public function domains(): array
    {
        return ClienteDonDominio::all([
            new DataBaseWhere('codcliente', $this->codcliente),
            new DataBaseWhere('provider', 'dondominio'),
        ], ['domain' => 'ASC']);
    }

    /**
     * Busca un dominio concreto por nombre para este cliente.
     */
    public function findDomainByName(string $domain): ?ClienteDonDominio
    {
        return ClienteDonDominio::findWhere([
            'codcliente' => $this->codcliente,
            'domain' => $domain,
            'provider' => 'dondominio',
        ]);
    }
}
