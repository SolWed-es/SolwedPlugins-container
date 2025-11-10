<?php

namespace FacturaScripts\Plugins\DonDominio\Lib;

/**
 * Adaptador para obtener los dominios paginados usando la caché local.
 */
class DomainListingService
{
    private $syncService;

    public function __construct($syncService = null)
    {
        $this->syncService = $syncService ?? new DomainSyncService();
    }

    /**
     * Devuelve dominios de la caché con filtros y paginación.
     *
     * @param array{query?:string,status?:string} $filters
     */
    public function listDomains(array $filters, int $offset, int $limit): array
    {
        return $this->syncService->listDomains($filters, $offset, $limit);
    }

    public function countExpiringDomains(int $days = 30): int
    {
        return $this->syncService->countExpiringDomains($days);
    }

    public function getExpiringDomainsForClient(string $codcliente, int $days = 30): array
    {
        return $this->syncService->getExpiringDomainsForClient($codcliente, $days);
    }

    public function getClientContacts(string $codcliente): array
    {
        return $this->syncService->getClientContacts($codcliente);
    }
}
