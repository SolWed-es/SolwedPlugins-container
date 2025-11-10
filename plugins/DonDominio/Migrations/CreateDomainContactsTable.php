<?php

namespace FacturaScripts\Plugins\DonDominio\Migrations;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Template\MigrationClass;

class CreateDomainContactsTable extends MigrationClass
{
    const MIGRATION_NAME = 'create_domain_contacts_table_v1';

    public function run(): void
    {
        $db = self::db();
        if ($db->tableExists('clientes_dondominio_dominios_contactos')) {
            return;
        }

        $sql = <<<SQL
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
SQL;

        $db->exec($sql);
    }
}
