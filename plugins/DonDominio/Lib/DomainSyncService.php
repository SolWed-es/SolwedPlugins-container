<?php

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Plugins\DonDominio\Model\Domain;
use FacturaScripts\Plugins\DonDominio\Model\DomainContact;

/**
 * Servicio que sincroniza dominios y contactos de DonDominio en caché local.
 */
class DomainSyncService
{
    private const TTL_SECONDS = 3600;

    private $dataBase;
    private $client;

    public function __construct(DataBase $dataBase = null, ?\Dondominio\API\API $client = null)
    {
        $this->dataBase = $dataBase ?? new DataBase();
        $this->dataBase->connect();
        $this->client = $client ?? DonDominioApiClient::get();
    }

    /**
     * Lista dominios almacenados aplicando filtros de consulta paginada.
     */
    public function listDomains(array $filters, int $offset, int $limit): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);
        $items = $this->queryDomains($filters, $offset, $limit);
        $clients = $this->collectClients($items);
        $refreshed = false;

        foreach ($clients as $codcliente) {
            if ($this->syncClientIfNeeded($codcliente)) {
                $refreshed = true;
            }
        }

        if ($refreshed) {
            $items = $this->queryDomains($filters, $offset, $limit);
        }

        $total = $this->countDomains($filters);

        return [
            'items' => $items,
            'total' => $total,
            'has_more' => ($offset + count($items)) < $total,
        ];
    }

    /**
     * Sincroniza datos si están desactualizados o se fuerza.
     */
    public function syncClientIfNeeded(string $codcliente, bool $force = false): bool
    {
        if ($force) {
            return $this->refreshClientData($codcliente);
        }

        if ($this->client === null) {
            return false;
        }

        if ($this->needsRefresh($codcliente)) {
            return $this->refreshClientData($codcliente);
        }

        return false;
    }

    /**
     * Obtiene los contactos cacheados para un cliente.
     */
    public function getClientContacts(string $codcliente): array
    {
        $this->syncClientIfNeeded($codcliente);

        $contacts = $this->fetchCachedContacts($codcliente);
        if (empty($contacts)) {
            return $this->buildFallbackContacts($codcliente);
        }

        $result = [];
        foreach ($contacts as $contact) {
            $domains = $this->loadDomainsForContact($codcliente, $contact->contact_id);
            $result[] = [
                'id' => $contact->contact_id,
                'name' => $contact->name ?: ($contact->contact_id ?? 'Sin nombre'),
                'email' => $contact->email,
                'phone' => $contact->phone,
                'company' => $contact->tax_number ?: '',
                'tax_number' => $contact->tax_number,
                'country' => $contact->country,
                'verification_status' => $contact->verification_status,
                'daaccepted' => (bool) $contact->daaccepted,
                'domains_count' => count($domains),
                'domains' => $domains,
                'synced_at' => $contact->synced_at,
            ];
        }

        return $result;
    }

    private function fetchCachedContacts(string $codcliente): array
    {
        try {
            return DomainContact::all([
                new DataBaseWhere('codcliente', $codcliente),
            ], ['name' => 'ASC']);
        } catch (\Throwable $exception) {
            Tools::log()->error('dondominio-cache-contacts-error', [
                '%codcliente%' => $codcliente,
                '%message%' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    private function buildFallbackContacts(string $codcliente): array
    {
        $domains = Domain::all([
            new DataBaseWhere('codcliente', $codcliente),
            new DataBaseWhere('provider', 'dondominio'),
        ], ['domain' => 'ASC']);

        if (empty($domains)) {
            return [];
        }

        $mapped = [];
        foreach ($domains as $domain) {
            $mapped[] = $this->mapDomainForPortal($domain);
        }

        return [[
            'id' => 'fallback',
            'name' => Tools::lang()->trans('dondominio-contact-not-found'),
            'email' => '',
            'phone' => '',
            'company' => '',
            'tax_number' => '',
            'country' => '',
            'verification_status' => '',
            'daaccepted' => false,
            'domains_count' => count($mapped),
            'domains' => $mapped,
            'synced_at' => null,
        ]];
    }

    /**
     * Devuelve los dominios próximos a expirar para un cliente.
     */
    public function getExpiringDomainsForClient(string $codcliente, int $days = 30): array
    {
        $this->syncClientIfNeeded($codcliente);

        $dateFrom = date('Y-m-d H:i:s');
        $dateTo = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $sql = sprintf(
            "SELECT domain, expires_at FROM clientes_dondominio_dominios WHERE codcliente = '%s' AND provider = 'dondominio' AND expires_at BETWEEN '%s' AND '%s' ORDER BY expires_at ASC LIMIT 20;",
            $this->escape($codcliente),
            $dateFrom,
            $dateTo
        );

        $rows = $this->dataBase->selectLimit($sql, 0, 0);
        $result = [];
        foreach ($rows as $row) {
            if (empty($row['domain']) || empty($row['expires_at'])) {
                continue;
            }
            $result[] = [
                'name' => $row['domain'],
                'expiration' => $row['expires_at'],
            ];
        }

        return $result;
    }

    /**
     * Cuenta los dominios que expiran en el intervalo indicado.
     */
    public function countExpiringDomains(int $days = 30): int
    {
        $dateFrom = date('Y-m-d H:i:s');
        $dateTo = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $sql = sprintf(
            "SELECT COUNT(*) AS total FROM clientes_dondominio_dominios WHERE provider = 'dondominio' AND expires_at BETWEEN '%s' AND '%s';",
            $dateFrom,
            $dateTo
        );

        $rows = $this->dataBase->selectLimit($sql, 0, 0);
        return (int) ($rows[0]['total'] ?? 0);
    }

    private function needsRefresh(string $codcliente): bool
    {
        $sql = sprintf(
            "SELECT MAX(synced_at) AS last_synced FROM clientes_dondominio_dominios_contactos WHERE codcliente = '%s';",
            $this->escape($codcliente)
        );

        $rows = $this->dataBase->selectLimit($sql, 0, 0);
        $last = $rows[0]['last_synced'] ?? null;
        if (empty($last)) {
            return true;
        }

        $timestamp = strtotime($last);
        if (false === $timestamp) {
            return true;
        }

        return (time() - $timestamp) >= self::TTL_SECONDS;
    }

    private function refreshClientData(string $codcliente): bool
    {
        if ($this->client === null) {
            return false;
        }

        $cliente = new Cliente();
        if (false === $cliente->loadFromCode($codcliente) || !$cliente->exists()) {
            return false;
        }

        $taxNumber = trim((string) $cliente->cifnif);
        if ($taxNumber === '') {
            return false;
        }

        $contacts = DonDominioContactService::findContactsByTaxNumber($taxNumber);
        $syncedAt = Tools::dateTime();

        $this->dataBase->beginTransaction();
        try {
            Domain::table()->whereEq('codcliente', $codcliente)->delete();
            DomainContact::table()->whereEq('codcliente', $codcliente)->delete();

            foreach ($contacts as $contact) {
                $contactId = DonDominioContactService::extractContactIdentifier($contact);
                if (empty($contactId)) {
                    continue;
                }

                $contactModel = $this->createContactModel($contact, $codcliente, $syncedAt, $contactId);
                $contactModel->save();

                $domains = $this->fetchDomainsForContact($contactId);
                foreach ($domains as $domainData) {
                    $domainModel = $this->createDomainModel($domainData, $codcliente, $contactId, $syncedAt);
                    if (!empty($domainModel->domain)) {
                        $domainModel->save();
                    }
                }
            }

            $this->dataBase->commit();
            return true;
        } catch (\Throwable $exception) {
            $this->dataBase->rollback();
            Tools::log()->error('dondominio-sync-error', [
                '%codcliente%' => $codcliente,
                '%message%' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    private function fetchDomainsForContact(string $contactId): array
    {
        if ($this->client === null) {
            return [];
        }

        try {
            $response = $this->client->domain_list([
                'owner' => $contactId,
                'pageLength' => 100,
                'infoType' => 'status',
            ]);

            $data = $this->unwrapResponse($response);
            $list = $this->extractList($data, ['domains', 'list', 'items']);
            $result = [];

            foreach ($list as $domain) {
                if (is_array($domain) && !empty($this->resolveDomainName($domain))) {
                    $result[] = $domain;
                }
            }

            return $result;
        } catch (\Throwable $exception) {
            Tools::log()->warning('dondominio-domain-list-error', [
                '%contact%' => $contactId,
                '%message%' => $exception->getMessage(),
            ]);
        }

        return [];
    }

    private function loadDomainsForContact(string $codcliente, string $contactId): array
    {
        $models = Domain::all([
            new DataBaseWhere('codcliente', $codcliente),
            new DataBaseWhere('contact_id', $contactId),
        ], ['domain' => 'ASC']);

        $domains = [];
        foreach ($models as $model) {
            $domains[] = $this->mapDomainForPortal($model);
        }

        return $domains;
    }

    private function collectClients(array $items): array
    {
        $clients = [];
        foreach ($items as $item) {
            if (!empty($item['codcliente'])) {
                $clients[$item['codcliente']] = true;
            }
        }

        return array_keys($clients);
    }

    private function queryDomains(array $filters, int $offset, int $limit): array
    {
        $where = $this->buildWhere($filters);
        $sql = <<<SQL
SELECT d.*, COALESCE(NULLIF(c.nombre, ''), NULLIF(c.razonsocial, ''), c.codcliente) AS cliente_nombre
FROM clientes_dondominio_dominios d
LEFT JOIN clientes c ON c.codcliente = d.codcliente
WHERE {$where}
ORDER BY d.domain ASC
SQL;

        return $this->dataBase->selectLimit($sql, $limit, $offset);
    }

    private function countDomains(array $filters): int
    {
        $where = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM clientes_dondominio_dominios d WHERE {$where};";
        $rows = $this->dataBase->selectLimit($sql, 0, 0);
        return (int) ($rows[0]['total'] ?? 0);
    }

    private function buildWhere(array $filters): string
    {
        $conditions = ["d.provider = 'dondominio'"];

        if (!empty($filters['query'])) {
            $like = $this->escapeForLike($filters['query']);
            $conditions[] = "d.domain LIKE CONCAT('%', " . $this->quote($like) . ", '%')";
        }

        if (!empty($filters['status'])) {
            $conditions[] = "LOWER(d.status) = LOWER(" . $this->quote($filters['status']) . ")";
        }

        return implode(' AND ', $conditions);
    }

    private function createContactModel(array $contactData, string $codcliente, string $syncedAt, string $contactId): DomainContact
    {
        $contact = new DomainContact();
        $contact->codcliente = $codcliente;
        $contact->contact_id = $contactId;
        $contact->name = $contactData['contactName'] ?? '';
        $contact->email = $contactData['email'] ?? '';
        $contact->phone = $contactData['phone'] ?? '';
        $contact->tax_number = $contactData['identNumber'] ?? '';
        $contact->country = $contactData['country'] ?? '';
        $contact->verification_status = $contactData['verificationstatus'] ?? '';
        $contact->daaccepted = $contactData['daaccepted'] ?? false;
        $contact->raw_data = $this->encodeRaw($contactData);
        $contact->synced_at = $syncedAt;
        return $contact;
    }

    private function createDomainModel(array $domainData, string $codcliente, string $contactId, string $syncedAt): Domain
    {
        $model = new Domain();
        $model->codcliente = $codcliente;
        $model->contact_id = $contactId;
        $model->domain = $this->resolveDomainName($domainData);
        $model->domain_id = $domainData['domainID'] ?? null;
        $model->status = $domainData['status'] ?? 'unknown';
        $model->expires_at = $this->normalizeExpiration($domainData['tsExpir'] ?? $domainData['expiration'] ?? null);
        $model->autorenew = $this->isAutoRenew($domainData);
        $model->provider = 'dondominio';
        $model->raw_data = $this->encodeRaw($domainData);
        $model->synced_at = $syncedAt;
        return $model;
    }

    private function mapDomainForPortal(Domain $domain): array
    {
        $raw = $this->decodeRaw($domain->raw_data);
        return [
            'name' => $domain->domain,
            'domainID' => $domain->domain_id,
            'status' => $domain->status,
            'expiration' => $domain->expires_at,
            'tsExpir' => $this->toTimestamp($domain->expires_at),
            'nameservers' => $this->extractNameservers($raw),
            'autorenew' => (bool) $domain->autorenew,
            'synced_at' => $domain->synced_at,
        ];
    }

    private function escape(string $value): string
    {
        return $this->dataBase->escapeString($value);
    }

    private function quote(string $value): string
    {
        return "'" . $this->escape($value) . "'";
    }

    private function escapeForLike(string $value): string
    {
        $value = addcslashes($value, '%_');
        return $this->dataBase->escapeString($value);
    }

    private function resolveDomainName(array $domainData): string
    {
        foreach (['domain', 'name', 'fqdn'] as $key) {
            if (!empty($domainData[$key])) {
                return (string) $domainData[$key];
            }
        }

        return '';
    }

    private function normalizeExpiration($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int) $value);
        }

        $timestamp = strtotime((string) $value);
        return false === $timestamp ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private function isAutoRenew(array $domainData): bool
    {
        $value = $domainData['autorenew'] ?? ($domainData['renewalMode'] ?? null);
        if (is_bool($value)) {
            return $value;
        }

        return 'autorenew' === strtolower((string) $value);
    }

    private function encodeRaw(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function decodeRaw(?string $json): array
    {
        if (empty($json)) {
            return [];
        }

        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function extractList(mixed $data, array $keys): array
    {
        if (!is_array($data)) {
            return [];
        }

        foreach ($keys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        return [];
    }

    private function unwrapResponse(mixed $response): mixed
    {
        if (is_object($response) && method_exists($response, 'getResponseData')) {
            return $response->getResponseData();
        }

        return $response;
    }

    private function extractNameservers(array $data): array
    {
        $candidates = [
            $data['nameservers'] ?? null,
            $data['list'] ?? null,
            $data['items'] ?? null,
            $data
        ];

        $result = [];
        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            foreach ($candidate as $entry) {
                if (is_string($entry)) {
                    $entry = trim($entry);
                    if ($entry !== '') {
                        $result[] = $entry;
                    }
                    continue;
                }

                if (is_array($entry)) {
                    $host = $entry['host'] ?? $entry['hostname'] ?? $entry['name'] ?? $entry['nameserver'] ?? null;
                    if (is_string($host)) {
                        $host = trim($host);
                        if ($host !== '') {
                            $result[] = $host;
                        }
                    }
                }
            }

            if (!empty($result)) {
                return array_values(array_unique($result));
            }
        }

        return [];
    }

    private function toTimestamp(?string $value): ?int
    {
        if (empty($value)) {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : $timestamp;
    }

}
