<?php

namespace FacturaScripts\Plugins\Dominios\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Dominios\Lib\ContactService;
use FacturaScripts\Plugins\Dominios\Lib\DomainService;

/**
 * Extensión del modelo Cliente para integración con el proveedor de dominios
 */
class ClienteDominio extends Cliente
{
    /** @var string */
    public $domain_id;


    /**
     * Obtiene el ID del proveedor de dominios buscando por número fiscal
     */
    public function getDomainId(): ?string
    {
        if (!empty($this->domain_id)) {
            return $this->domain_id;
        }

        $this->syncDomainId();
        return $this->domain_id;
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
        $this->ensureColumnExists('domain_id', "ALTER TABLE %s ADD COLUMN domain_id VARCHAR(50) NULL DEFAULT NULL");
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
     * Guarda el cliente y busca el ID del proveedor si es necesario
     */
    protected function saveInsert(): bool
    {
        $result = parent::saveInsert();

        if ($result) {
            $this->syncDomainId();
        }

        return $result;
    }

    /**
     * Actualiza el cliente y busca el ID del proveedor si es necesario
     */
    protected function saveUpdate(): bool
    {
        $result = parent::saveUpdate();

        if ($result) {
            $this->syncDomainId();
        }

        return $result;
    }

    private function persistDomainId(?string $domainId): void
    {
        if (empty($domainId)) {
            return;
        }

        $updated = static::table()
            ->whereEq($this->primaryColumn(), $this->id())
            ->update(['domain_id' => $domainId]);

        if ($updated) {
            $this->domain_id = $domainId;
        }
    }

    private function syncDomainId(): void
    {
        if (empty($this->cifnif) || !empty($this->domain_id)) {
            return;
        }

        $candidates = ContactService::findContactsByTaxNumber($this->cifnif);
        if (count($candidates) !== 1) {
            return;
        }

        $domainId = ContactService::extractContactIdentifier($candidates[0]);
        if (empty($domainId)) {
            return;
        }

        $this->persistDomainId($domainId);
    }

    /**
     * Sincroniza los dominios del cliente desde el proveedor.
     */
    public function syncDomains(?string $contactId = null): int
    {
        return DomainService::syncDomains($this, $contactId);
    }

    /**
     * Devuelve los dominios almacenados localmente para este cliente.
     */
    public function domains(): array
    {
        return ClienteDominio::all([
            new DataBaseWhere('codcliente', $this->codcliente),
            new DataBaseWhere('provider', 'dominios'),
        ], ['domain' => 'ASC']);
    }

    /**
     * Busca un dominio concreto por nombre para este cliente.
     */
    public function findDomainByName(string $domain): ?ClienteDominio
    {
        return ClienteDominio::findWhere([
            'codcliente' => $this->codcliente,
            'domain' => $domain,
            'provider' => 'dominios',
        ]);
    }
}
