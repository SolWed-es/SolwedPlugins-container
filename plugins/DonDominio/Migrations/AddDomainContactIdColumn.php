<?php

namespace FacturaScripts\Plugins\DonDominio\Migrations;

use FacturaScripts\Core\Template\MigrationClass;

class AddDomainContactIdColumn extends MigrationClass
{
    const MIGRATION_NAME = 'add_domain_contact_id_column_v1';

    public function run(): void
    {
        $db = self::db();
        if (!$db->tableExists('clientes_dondominio_dominios')) {
            return;
        }

        $columns = $db->getColumns('clientes_dondominio_dominios');
        if (isset($columns['contact_id'])) {
            return;
        }

        $sql = <<<'SQL'
ALTER TABLE clientes_dondominio_dominios
ADD COLUMN contact_id VARCHAR(80) NULL;
SQL;

        $db->exec($sql);
    }
}
