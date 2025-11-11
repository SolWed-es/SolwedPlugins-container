<?php

namespace FacturaScripts\Plugins\DonDominio\Lib;

use DateTime;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Model\Cliente as BaseCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\DonDominio\Model\ClienteDonDominio;
use FacturaScripts\Plugins\DonDominio\Model\Domain;

final class DonDominioDomainService
{
    /**
     * Synchronises the domains from DonDominio for the given customer and stores them locally.
     * 
     * @param BaseCliente|Cliente|ClienteDonDominio $cliente
     */
    public static function syncDomains(BaseCliente $cliente, ?string $contactId = null): int
    {
        $contactId = self::resolveContactId($cliente, $contactId);
        if (empty($contactId)) {
            return 0;
        }

        $client = DonDominioApiClient::get();
        if (null === $client) {
            return 0;
        }

        try {
            $response = $client->domain_list([
                'owner' => $contactId,
                'infoType' => 'status',
            ]);
        } catch (\Throwable $exception) {
            Tools::log()->warning('dondominio-sync-error', ['%message%' => $exception->getMessage()]);
            return 0;
        }

        $domains = self::extractDomains($response);
        if (!empty($domains)) {
            $debugPath = Tools::folder('MyFiles', 'Tmp', 'dondominio_last_domains.json');
            @file_put_contents($debugPath, json_encode([
                'contactId' => $contactId,
                'domains' => $domains,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        ClienteDonDominio::deleteProviderDomains($cliente->codcliente, 'dondominio');

        $count = 0;
        foreach ($domains as $domainData) {
            $mapped = self::mapDomainData($domainData);
            if (empty($mapped['domain'])) {
                continue;
            }

            $mapped['provider'] = 'dondominio';
            $mapped['raw_data'] = $domainData;
            $mapped['synced_at'] = Tools::dateTime();

            ClienteDonDominio::upsertDonDominio($cliente->codcliente, $mapped);
            ++$count;
        }

        return $count;
    }

    /**
     * Returns the locally stored domains after synchronisation.
     * 
     * @param Cliente|ClienteDonDominio $cliente
     * @return array
     */
    public static function getStoredDomains($cliente): array
    {
        if (!($cliente instanceof \FacturaScripts\Core\Model\Cliente)) {
            throw new \InvalidArgumentException('Expected instance of Cliente or ClienteDonDominio');
        }

        if (empty($cliente->codcliente)) {
            return [];
        }

        try {
            (new DomainSyncService())->syncClientIfNeeded($cliente->codcliente);
        } catch (\Throwable $exception) {
            Tools::log()->warning('dondominio-cache-sync-error', [
                '%codcliente%' => $cliente->codcliente,
                '%message%' => $exception->getMessage(),
            ]);
        }

        $models = Domain::all([
            new DataBaseWhere('codcliente', $cliente->codcliente),
            new DataBaseWhere('provider', 'dondominio'),
        ], ['domain' => 'ASC']);

        if (empty($models)) {
            return [];
        }

        $extendedClient = $cliente instanceof ClienteDonDominio
            ? $cliente
            : self::loadClienteDonDominio($cliente->codcliente);
        $accessInfo = self::buildAccessInfo($extendedClient);

        $result = [];
        foreach ($models as $domain) {
            $result[] = self::mapStoredDomain($domain, $accessInfo);
        }

        return $result;
    }

    private static function loadClienteDonDominio(string $codcliente): ?ClienteDonDominio
    {
        if ('' === trim($codcliente)) {
            return null;
        }

        $extended = new ClienteDonDominio();
        return $extended->loadFromCode($codcliente) ? $extended : null;
    }

    private static function buildAccessInfo(?ClienteDonDominio $cliente): array
    {
        return [
            'web_link' => $cliente?->web_server_url,
            'mail_link' => $cliente?->mail_server_url,
            'erp_action' => $cliente?->erp_url,
            'erp_user' => $cliente?->erp_user,
            'erp_pass' => $cliente?->erp_password,
            'erp_user_field' => 'username',
            'erp_pass_field' => 'password',
        ];
    }

    private static function mapStoredDomain(Domain $domain, array $accessInfo): array
    {
        $webLink = $accessInfo['web_link'] ?? null;
        if (empty($webLink)) {
            $webLink = self::resolveWebLinkFromDomain($domain);
        }

        return [
            'id' => $domain->id,
            'domain' => $domain->domain,
            'status' => $domain->status,
            'expires_at' => $domain->expires_at,
            'autorenew' => (bool)$domain->autorenew,
            'nameservers' => self::extractNameserversFromRaw($domain->raw_data),
            'web_link' => $webLink,
            'mail_link' => $accessInfo['mail_link'] ?? null,
            'erp_action' => $accessInfo['erp_action'] ?? null,
            'erp_user' => $accessInfo['erp_user'] ?? null,
            'erp_pass' => $accessInfo['erp_pass'] ?? null,
            'erp_user_field' => $accessInfo['erp_user_field'] ?? 'username',
            'erp_pass_field' => $accessInfo['erp_pass_field'] ?? 'password',
            'tld' => $domain->tld,
            'created' => $domain->registered_at,
            'renewal_mode' => $domain->renewal_mode,
            'renewable' => (bool)$domain->renewable,
            'transfer_block' => (bool)$domain->transfer_block,
            'modify_block' => (bool)$domain->modify_block,
            'whois_privacy' => (bool)$domain->whois_privacy,
            'owner_verification' => $domain->owner_verification,
            'service_associated' => (bool)$domain->service_associated,
            'tag' => $domain->tag,
            'authcode_check' => (bool)$domain->authcode_check,
            'view_whois' => (bool)$domain->view_whois,
            'registrant' => $domain->registrant_contact,
            'admin' => $domain->admin_contact,
            'tech' => $domain->tech_contact,
            'billing' => $domain->billing_contact,
        ];
    }

    /**
     * Obtiene la lista de dominios disponibles en DonDominio para un cliente basado en su DNI
     * 
     * @param BaseCliente|Cliente|ClienteDonDominio $cliente
     * @return array Array asociativo con domain => label para el selector
     */
    public static function getAvailableDomainsForSelector(BaseCliente $cliente, ?string $forcedContactId = null): array
    {
        $contactId = $forcedContactId ?? self::resolveContactId($cliente, null);
        if (empty($contactId)) {
            return [];
        }

        $client = DonDominioApiClient::get();
        if (null === $client) {
            return [];
        }

        try {
            $response = $client->domain_list([
                'owner' => $contactId,
                'infoType' => 'status',
            ]);
        } catch (\Throwable $exception) {
            Tools::log()->warning('dondominio-domains-list-error', ['%message%' => $exception->getMessage()]);
            return [];
        }

        $domains = self::extractDomains($response);
        $options = [];

        foreach ($domains as $domainData) {
            $mapped = self::mapDomainData($domainData);
            $domain = $mapped['domain'] ?? '';
            $domainId = $mapped['domain_id'] ?? null;

            if (empty($domain) && empty($domainId)) {
                continue;
            }

            $status = $mapped['status'] ?? 'unknown';
            $expires = $mapped['expires_at'] ?? '';

            $identifier = $domainId ? (string)$domainId : $domain;
            $labelParts = [$identifier];

            if (!empty($domain)) {
                $labelParts[] = $domain;
            }

            if (!empty($status)) {
                $labelParts[] = '(' . $status . ')';
            }

            if (!empty($expires)) {
                $labelParts[] = 'Exp: ' . $expires;
            }

            $options[$identifier] = [
                'id' => $identifier,
                'domain' => $domain,
                'domain_id' => $domainId,
                'status' => $status,
                'expires_at' => $expires,
                'label' => implode(' · ', array_filter($labelParts)),
            ];
        }

        return $options;
    }

    /**
     * Retrieves detailed information for a domain directly from DonDominio.
     *
     * @param ClienteDonDominio $domain
     * @param array $types
     * @param array|null $errors
     * @return array<string,mixed>
     */
    public static function getDomainDetails(ClienteDonDominio $domain, array $types = ['status', 'contact', 'nameservers', 'service'], ?array &$errors = null): array
    {
        $errors = [];
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $errors[] = 'missing-domain';
            return [];
        }

        $client = DonDominioApiClient::get();
        if (null === $client) {
            $errors[] = 'client-unavailable';
            return [];
        }

        $details = [];
        foreach ($types as $type) {
            try {
                $response = $client->domain_getinfo($identifier, ['infoType' => $type]);
                $data = $response instanceof \Dondominio\API\Response\Response ? $response->getResponseData() : $response;
                if (!empty($data)) {
                    $details[$type] = $data;
                }
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        return $details;
    }

    /**
     * Toggles the auto renew status of a domain.
     */
    public static function setAutoRenew(ClienteDonDominio $domain, bool $enable, ?string &$error = null): bool
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return false;
        }

        $client = DonDominioApiClient::get();
        if (null === $client) {
            $error = 'client-unavailable';
            return false;
        }

        try {
            $response = $client->domain_update($identifier, ['renewalMode' => $enable ? 'autorenew' : 'manual']);
            if ($response instanceof \Dondominio\API\Response\Response && !$response->getSuccess()) {
                $error = $response->getErrorCodeMsg();
                return false;
            }
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
            return false;
        }

        $domain->autorenew = $enable;
        $domain->synced_at = Tools::dateTime();
        $domain->save();

        return true;
    }

    /**
     * Retrieves the current auth code for a domain.
     */
    public static function getAuthCode(ClienteDonDominio $domain, ?string &$error = null): ?string
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return null;
        }

        $client = DonDominioApiClient::get();
        if (null === $client) {
            $error = 'client-unavailable';
            return null;
        }

        try {
            $response = $client->domain_getauthcode($identifier);
            $data = $response instanceof \Dondominio\API\Response\Response ? $response->getResponseData() : $response;
            if (is_array($data)) {
                if (isset($data['authcode'])) {
                    return (string)$data['authcode'];
                }
                if (isset($data['code'])) {
                    return (string)$data['code'];
                }
            }
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
            return null;
        }

        $error = 'authcode-not-found';
        return null;
    }

    /**
     * Retrieves the current nameservers for a domain.
     *
     * @param ClienteDonDominio $domain
     * @param string|null $error
     * @return string[]
     */
    public static function getNameservers(ClienteDonDominio $domain, ?string &$error = null): array
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return [];
        }

        $client = DonDominioApiClient::get();
        if (null === $client) {
            $error = 'client-unavailable';
            return [];
        }

        try {
            $response = $client->domain_getnameservers($identifier);
            $data = $response instanceof \Dondominio\API\Response\Response ? $response->getResponseData() : $response;
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
            return [];
        }

        if (!is_array($data)) {
            return [];
        }

        $candidates = [
            $data['nameservers'] ?? null,
            $data['list'] ?? null,
            $data['items'] ?? null,
            $data,
        ];

        $nameservers = [];
        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            foreach ($candidate as $item) {
                if (is_string($item)) {
                    $item = trim($item);
                    if ('' !== $item) {
                        $nameservers[] = $item;
                    }
                    continue;
                }

                if (is_array($item)) {
                    $host = $item['host']
                        ?? ($item['hostname']
                        ?? ($item['name']
                        ?? ($item['nameserver'] ?? null)));
                    if (is_string($host)) {
                        $host = trim($host);
                        if ('' !== $host) {
                            $nameservers[] = $host;
                        }
                    }
                }
            }

            if (!empty($nameservers)) {
                break;
            }
        }

        return array_values(array_unique($nameservers));
    }

    /**
     * Updates the nameservers for a domain.
     *
     * @param ClienteDonDominio $domain
     * @param array $nameservers
     * @param string|null $error
     */
    public static function updateNameservers(ClienteDonDominio $domain, array $nameservers, ?string &$error = null): bool
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return false;
        }

        $sanitized = self::sanitizeNameservers($nameservers);
        if (count($sanitized) < 2) {
            $error = 'nameservers-too-few';
            return false;
        }

        $client = DonDominioApiClient::get();
        if (null === $client) {
            $error = 'client-unavailable';
            return false;
        }

        try {
            $response = $client->domain_updatenameservers($identifier, $sanitized);
            if ($response instanceof \Dondominio\API\Response\Response && false === $response->getSuccess()) {
                $error = $response->getErrorCodeMsg();
                return false;
            }
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
            return false;
        }

        $domain->synced_at = Tools::dateTime();
        $domain->save();

        return true;
    }

    /**
     * Returns whether the transfer lock is enabled for the domain.
     */
    public static function getTransferLock(ClienteDonDominio $domain, ?string &$error = null): ?bool
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return null;
        }

        $client = DonDominioApiClient::get();
        if (null === $client) {
            $error = 'client-unavailable';
            return null;
        }

        try {
            $response = $client->domain_getinfo($identifier, ['infoType' => 'status']);
            $data = $response instanceof \Dondominio\API\Response\Response ? $response->getResponseData() : $response;
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        $status = $data['status'] ?? $data;
        if (is_array($status)) {
            if (array_key_exists('transferBlock', $status)) {
                return self::normalizeBoolean($status['transferBlock']);
            }
            if (array_key_exists('lockTransfer', $status)) {
                return self::normalizeBoolean($status['lockTransfer']);
            }
        }

        if (array_key_exists('transferBlock', $data)) {
            return self::normalizeBoolean($data['transferBlock']);
        }

        return null;
    }

    /**
     * Enables or disables the transfer lock for a domain.
     */
    public static function setTransferLock(ClienteDonDominio $domain, bool $enable, ?string &$error = null): bool
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return false;
        }

        $client = DonDominioApiClient::get();
        if (null === $client) {
            $error = 'client-unavailable';
            return false;
        }

        try {
            $response = $client->domain_update($identifier, [
                'updateType' => 'transferBlock',
                'transferBlock' => $enable,
            ]);
            if ($response instanceof \Dondominio\API\Response\Response && false === $response->getSuccess()) {
                $error = $response->getErrorCodeMsg();
                return false;
            }
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
            return false;
        }

        $domain->synced_at = Tools::dateTime();
        $domain->save();

        return true;
    }

    private static function extractDomains(mixed $response): array
    {
        if ($response instanceof \Dondominio\API\Response\Response) {
            $data = $response->getResponseData();
        } elseif (is_object($response) && method_exists($response, 'getResponseData')) {
            $data = $response->getResponseData();
        } else {
            $data = $response;
        }

        if (!is_array($data)) {
            return [];
        }

        $candidates = [
            $data['domains'] ?? null,
            $data['list'] ?? null,
            $data['items'] ?? null,
            $data['results'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && self::isSequentialList($candidate)) {
                return $candidate;
            }
        }

        return self::isSequentialList($data) ? $data : [];
    }

    public static function mapDomainData(array $data): array
    {
        $domain = $data['domain'] ?? ($data['name'] ?? ($data['domainName'] ?? ''));
        $domainId = $data['domainID'] ?? ($data['id'] ?? null);

        $status = $data['status'] ?? null;
        if (is_array($status)) {
            $status = $status['status'] ?? ($status['code'] ?? null);
        }

        $expires = $data['expirationDate']
            ?? ($data['expireDate']
            ?? ($data['expires_at']
            ?? ($data['tsExpir']
            ?? ($data['tsExpire'] ?? null))));
        $expires = self::normalizeDate($expires);

        $autorenew = $data['autoRenew'] ?? ($data['autorenew'] ?? ($data['renewAuto'] ?? false));

        return [
            'domain' => $domain,
            'domain_id' => $domainId,
            'status' => is_string($status) ? $status : null,
            'expires_at' => $expires,
            'autorenew' => (bool)$autorenew,
        ];
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return date('Y-m-d', (int)$value);
        }

        $timestamp = strtotime(is_scalar($value) ? (string)$value : '');
        return false === $timestamp ? null : date('Y-m-d', $timestamp);
    }

    /**
     * @param BaseCliente|Cliente|ClienteDonDominio $cliente
     */
    private static function resolveContactId(BaseCliente $cliente, ?string $fallback): ?string
    {
        if (!empty($fallback)) {
            return $fallback;
        }

        // Si es ClienteDonDominio, usar directamente su dondominio_id
        if ($cliente instanceof ClienteDonDominio) {
            return $cliente->getDonDominioId();
        }

        // Intentar acceder a dondominio_id si existe (puede estar en propiedades dinámicas)
        if (property_exists($cliente, 'dondominio_id') && !empty($cliente->dondominio_id)) {
            return $cliente->dondominio_id;
        }

        // Cargar el modelo extendido para obtener el dondominio_id
        if (empty($cliente->codcliente)) {
            return null;
        }

        $extended = new ClienteDonDominio();
        if (false === $extended->loadFromCode($cliente->codcliente)) {
            return null;
        }

        return $extended->getDonDominioId();
    }

    private static function isSequentialList(array $value): bool
    {
        $expected = 0;
        foreach ($value as $key => $_) {
            if ($key !== $expected) {
                return false;
            }
            ++$expected;
        }

        return true;
    }

    private static function domainIdentifier(ClienteDonDominio $domain): string
    {
        if (!empty($domain->domain)) {
            return $domain->domain;
        }

        if (!empty($domain->domain_id)) {
            return (string)$domain->domain_id;
        }

        return '';
    }

    /**
     * @param array $nameservers
     * @return array<int,string>
     */
    private static function sanitizeNameservers(array $nameservers): array
    {
        $result = [];
        foreach ($nameservers as $nameserver) {
            if (!is_scalar($nameserver)) {
                continue;
            }

            $value = strtolower(trim((string)$nameserver));
            if ('' === $value) {
                continue;
            }

            $result[] = $value;
        }

        $result = array_values(array_unique($result));
        if (count($result) > 7) {
            $result = array_slice($result, 0, 7);
        }

        return $result;
    }

    private static function resolveWebLinkFromDomain(Domain $domain): ?string
    {
        $nameservers = self::extractNameserversFromRaw($domain->raw_data);
        if (empty($nameservers)) {
            return null;
        }

        $candidate = $nameservers[1] ?? $nameservers[0] ?? null;
        if (empty($candidate)) {
            return null;
        }

        $candidate = trim($candidate);
        if ('' === $candidate) {
            return null;
        }

        if (!preg_match('/^https?:\/\//i', $candidate)) {
            $candidate = 'https://' . $candidate;
        }

        return $candidate;
    }

    private static function extractNameserversFromRaw(?string $rawData): array
    {
        if (empty($rawData)) {
            return [];
        }

        $data = json_decode($rawData, true);
        if (!is_array($data)) {
            return [];
        }

        $candidates = [
            $data['nameservers'] ?? null,
            $data['list'] ?? null,
            $data['items'] ?? null,
            $data,
        ];

        $result = [];
        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            foreach ($candidate as $entry) {
                if (is_string($entry)) {
                    $entry = trim($entry);
                    if ('' !== $entry) {
                        $result[] = $entry;
                    }
                    continue;
                }

                if (is_array($entry)) {
                    $host = $entry['host'] ?? $entry['hostname'] ?? $entry['name'] ?? $entry['nameserver'] ?? null;
                    if (is_string($host)) {
                        $host = trim($host);
                        if ('' !== $host) {
                            $result[] = $host;
                        }
                    }
                }
            }

            if (!empty($result)) {
                break;
            }
        }

        return array_values(array_unique($result));
    }

    public static function getNameservers(string ): array
    {
         = trim();
        if ( === '') {
            return [];
        }

         = DonDominioApiClient::get();
        if (null === ) {
            return [];
        }

        try {
             = (, ['infoType' => 'nameservers']);
            if ( instanceof \Dondominio\API\Response\Response) {
                 = ();
            } elseif (is_object() && method_exists(, 'getResponseData')) {
                 = ();
            } else {
                 = ;
            }

            if (!is_array()) {
                return [];
            }

             = json_encode();
            return self::extractNameserversFromRaw();
        } catch (\Throwable ) {
            Tools::log()->warning('dondominio-domain-nameservers-error', [
                '%domain%' => ,
                '%message%' => (),
            ]);
        }

        return [];
    }
    private static function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
