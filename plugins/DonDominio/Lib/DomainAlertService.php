<?php

namespace FacturaScripts\Plugins\DonDominio\Lib;

/**
 * Servicio que proporciona alertas sobre dominios próximos a expirar usando la caché local.
 */
class DomainAlertService
{
    public static function getExpiringDomainsCount(int $days = 30): int
    {
        $service = new DomainSyncService();
        return $service->countExpiringDomains($days);
    }

    public static function getExpiringDomainsForClient(string $codcliente, int $days = 30): array
    {
        $service = new DomainSyncService();
        return $service->getExpiringDomainsForClient($codcliente, $days);
    }
}
