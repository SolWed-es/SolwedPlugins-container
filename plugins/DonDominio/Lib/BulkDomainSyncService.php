<?php

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;

class BulkDomainSyncService
{
    public static function syncAllClients(): array
    {
         = new DataBase();
        ->connect();

         = ->select("SELECT codcliente, cifnif FROM clientes WHERE cifnif IS NOT NULL AND TRIM(cifnif) <> ''");
        if (empty()) {
            return ['processed' => 0, 'synced' => 0, 'errors' => 0];
        }

         = new DomainSyncService();
         = ['processed' => 0, 'synced' => 0, 'errors' => 0];

        foreach ( as ) {
            ++['processed'];
             = ['codcliente'];

            try {
                if (->syncClientIfNeeded(, true)) {
                    ++['synced'];
                }
            } catch (\Throwable ) {
                ++['errors'];
                Tools::log()->warning('dondominio-sync-client-error', [
                    '%codcliente%' => ,
                    '%message%' => ->getMessage(),
                ]);
            }
        }

        return ;
    }
}
