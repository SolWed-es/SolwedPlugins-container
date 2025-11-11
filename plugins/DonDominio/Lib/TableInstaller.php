<?php

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;

/**
 * Se encarga de crear las tablas de caché de dominios y contactos si no existen.
 */
class TableInstaller
{
    private const TABLES = [
        'clientes_dondominio_dominios' => <<<'SQL'
CREATE TABLE clientes_dondominio_dominios (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codcliente` VARCHAR(20) NOT NULL,
    `domain` VARCHAR(255) NOT NULL,
    `domain_id` VARCHAR(100) DEFAULT NULL,
    `provider` VARCHAR(20) NOT NULL DEFAULT 'manual',
    `status` VARCHAR(50) DEFAULT NULL,
    `expires_at` DATE DEFAULT NULL,
    `autorenew` TINYINT(1) NOT NULL DEFAULT 0,
    `tld` VARCHAR(16) DEFAULT NULL,
    `registered_at` DATE DEFAULT NULL,
    `renewal_mode` VARCHAR(30) DEFAULT NULL,
    `renewable` TINYINT(1) DEFAULT 0,
    `transfer_block` TINYINT(1) DEFAULT 0,
    `modify_block` TINYINT(1) DEFAULT 0,
    `whois_privacy` TINYINT(1) DEFAULT 0,
    `owner_verification` VARCHAR(30) DEFAULT NULL,
    `service_associated` TINYINT(1) DEFAULT 0,
    `tag` VARCHAR(120) DEFAULT NULL,
    `authcode_check` TINYINT(1) DEFAULT 0,
    `view_whois` TINYINT(1) DEFAULT 0,
    `registrant_contact` VARCHAR(120) DEFAULT NULL,
    `admin_contact` VARCHAR(120) DEFAULT NULL,
    `tech_contact` VARCHAR(120) DEFAULT NULL,
    `billing_contact` VARCHAR(120) DEFAULT NULL,
    `raw_data` LONGTEXT DEFAULT NULL,
    `synced_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_cliente_domain (codcliente, domain),
    KEY idx_cliente_provider (codcliente, provider),
    KEY idx_domain_id (domain_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
SQL,
        'clientes_dondominio_dominios_contactos' => <<<'SQL'
CREATE TABLE clientes_dondominio_dominios_contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codcliente VARCHAR(20) NOT NULL,
    contact_id VARCHAR(80) NOT NULL,
    name VARCHAR(120) NULL,
    email VARCHAR(120) NULL,
    phone VARCHAR(60) NULL,
    tax_number VARCHAR(50) NULL,
    country VARCHAR(8) NULL,
    verification_status VARCHAR(40) NULL,
    daaccepted TINYINT(1) DEFAULT 0,
    raw_data TEXT NULL,
    synced_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uniq_contact_client (contact_id, codcliente),
    KEY idx_codcliente (codcliente)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL,
    ];

    /**
     * Asegura que existan las tablas de caché.
     */
    /**
     * @return array<string,string> Estado por tabla
     */
    public static function ensureTables(?DataBase $dataBase = null): array
    {
        $database = $dataBase ?? new DataBase();
        $database->connect();
        $results = [];

        foreach (self::TABLES as $tableName => $createSql) {
            if ($database->tableExists($tableName)) {
                Tools::log()->debug("Tabla {$tableName} ya existe.");
                $results[$tableName] = 'exists';
                continue;
            }

            $result = $database->exec($createSql);
            if (false === $result) {
                Tools::log()->warning("No se pudo crear {$tableName}. Revisa los logs de la base de datos.");
                $results[$tableName] = 'failed';
            } else {
                Tools::log()->notice("Tabla {$tableName} creada correctamente.");
                $results[$tableName] = 'created';
            }
        }

        self::ensureDomainColumns($database);

        return $results;
    }

    private static function ensureDomainColumns(DataBase $database): void
    {
        if (false === $database->tableExists('clientes_dondominio_dominios')) {
            return;
        }

        $existingColumns = [];
        try {
            $columnsInfo = $database->getColumns('clientes_dondominio_dominios');
            foreach ($columnsInfo as $column) {
                $name = $column['name'] ?? $column['Field'] ?? null;
                if ($name) {
                    $existingColumns[] = strtolower($name);
                }
            }
        } catch (\Throwable $exception) {
            Tools::log()->warning('dondominio-columns-inspection-error', ['%message%' => $exception->getMessage()]);
            return;
        }

        $definitions = [
            'tld' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `tld` VARCHAR(16) NULL AFTER `autorenew`",
            'registered_at' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `registered_at` DATE NULL AFTER `tld`",
            'renewal_mode' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `renewal_mode` VARCHAR(30) NULL AFTER `registered_at`",
            'renewable' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `renewable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `renewal_mode`",
            'transfer_block' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `transfer_block` TINYINT(1) NOT NULL DEFAULT 0 AFTER `renewable`",
            'modify_block' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `modify_block` TINYINT(1) NOT NULL DEFAULT 0 AFTER `transfer_block`",
            'whois_privacy' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `whois_privacy` TINYINT(1) NOT NULL DEFAULT 0 AFTER `modify_block`",
            'owner_verification' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `owner_verification` VARCHAR(30) NULL AFTER `whois_privacy`",
            'service_associated' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `service_associated` TINYINT(1) NOT NULL DEFAULT 0 AFTER `owner_verification`",
            'tag' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `tag` VARCHAR(120) NULL AFTER `service_associated`",
            'authcode_check' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `authcode_check` TINYINT(1) NOT NULL DEFAULT 0 AFTER `tag`",
            'view_whois' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `view_whois` TINYINT(1) NOT NULL DEFAULT 0 AFTER `authcode_check`",
            'registrant_contact' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `registrant_contact` VARCHAR(120) NULL AFTER `view_whois`",
            'admin_contact' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `admin_contact` VARCHAR(120) NULL AFTER `registrant_contact`",
            'tech_contact' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `tech_contact` VARCHAR(120) NULL AFTER `admin_contact`",
            'billing_contact' => "ALTER TABLE clientes_dondominio_dominios ADD COLUMN `billing_contact` VARCHAR(120) NULL AFTER `tech_contact`",
        ];

        foreach ($definitions as $column => $sql) {
            if (in_array(strtolower($column), $existingColumns, true)) {
                continue;
            }

            $database->exec($sql);
        }
    }
}
