<?php

namespace FacturaScripts\Plugins\DonDominio\Model;

use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Tools;

/**
 * Extensión del modelo Cliente para gestionar credenciales/datos del ERP.
 */
class ClienteERP extends Cliente
{
    /** @var string|null */
    public $erp_url;

    /** @var string|null */
    public $erp_user;

    /** @var string|null */
    public $erp_password;

    public static function tableName(): string
    {
        return 'clientes';
    }

    public function install(): string
    {
        $result = parent::install();
        $this->addPluginColumns();
        return $result;
    }

    private function addPluginColumns(): void
    {
        $this->ensureColumnExists('erp_url', "ALTER TABLE %s ADD COLUMN erp_url VARCHAR(255) NULL DEFAULT NULL");
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
}

