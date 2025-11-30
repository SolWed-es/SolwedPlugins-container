<?php

namespace FacturaScripts\Plugins\Dominios\Lib;

use DateTime;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Model\Cliente as BaseCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Plugins\Dominios\Model\ClienteDominio;

/**
 * Service for managing domain operations directly from API (no local cache).
 */
final class DomainService
{
    /**
     * Syncs domains (legacy method - now returns count from API without storing).
     * In the new architecture, this returns available domains count for backward compatibility.
     */
    public static function syncDomains(BaseCliente $cliente, ?string $contactId = null): int
    {
        try {
            $domains = self::getAvailableDomainsForSelector($cliente, $contactId);
            return count($domains);
        } catch (\Throwable $exception) {
            Tools::log()->warning('domain-sync-error', [
                '%message%' => $exception->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Returns domains directly from API (no longer from local cache).
     */
    public static function getStoredDomains($cliente): array
    {
        if (!($cliente instanceof \FacturaScripts\Core\Model\Cliente)) {
            throw new \InvalidArgumentException('Expected instance of Cliente or ClienteDominio');
        }

        if (empty($cliente->codcliente)) {
            return [];
        }

        // Get domains directly from API, not from cache
        try {
            $apiService = new DomainApiService();
            $contacts = $apiService->getClientContacts($cliente->codcliente);
            return $contacts;
        } catch (\Throwable $exception) {
            Tools::log()->warning('domain-api-error', [
                '%message%' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Gets available domains for a selector directly from API.
     */
    public static function getAvailableDomainsForSelector(BaseCliente $cliente, ?string $forcedContactId = null): array
    {
        $contactId = $forcedContactId ?? self::resolveContactId($cliente, null);
        if (empty($contactId)) {
            return [];
        }

        $client = DomainApiClient::get();
        if (null === $client) {
            return [];
        }

        try {
            $response = $client->domain_list([
                'owner' => $contactId,
                'infoType' => 'status',
            ]);
        } catch (\Throwable $exception) {
            Tools::log()->warning('domain-domains-list-error', ['%message%' => $exception->getMessage()]);
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
     * Retrieves detailed information for a domain directly from the provider API.
     */
    public static function getDomainDetails(ClienteDominio $domain, array $types = ['status', 'contact', 'nameservers', 'service'], ?array &$errors = null): array
    {
        $errors = [];
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $errors[] = 'missing-domain';
            return [];
        }

        $client = DomainApiClient::get();
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
    public static function setAutoRenew(ClienteDominio $domain, bool $enable, ?string &$error = null): bool
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return false;
        }

        $client = DomainApiClient::get();
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

        return true;
    }

    /**
     * Retrieves the current auth code for a domain.
     */
    public static function getAuthCode(ClienteDominio $domain, ?string &$error = null): ?string
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return null;
        }

        $client = DomainApiClient::get();
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
     */
    public static function getNameservers(ClienteDominio $domain, ?string &$error = null): array
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return [];
        }

        $client = DomainApiClient::get();
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
     */
    public static function updateNameservers(ClienteDominio $domain, array $nameservers, ?string &$error = null): bool
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

        $client = DomainApiClient::get();
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

        return true;
    }

    /**
     * Returns whether the transfer lock is enabled for the domain.
     */
    public static function getTransferLock(ClienteDominio $domain, ?string &$error = null): ?bool
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return null;
        }

        $client = DomainApiClient::get();
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
    public static function setTransferLock(ClienteDominio $domain, bool $enable, ?string &$error = null): bool
    {
        $identifier = self::domainIdentifier($domain);
        if ('' === $identifier) {
            $error = 'missing-domain';
            return false;
        }

        $client = DomainApiClient::get();
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

        return true;
    }

    /**
     * Extracts domains from API response.
     */
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

    /**
     * Maps raw domain data to standardized format.
     */
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

    /**
     * Normalizes a date value to Y-m-d format.
     */
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
     * Resolves the contact ID for a customer.
     */
    private static function resolveContactId(BaseCliente $cliente, ?string $fallback): ?string
    {
        if (!empty($fallback)) {
            return $fallback;
        }

        // Si es ClienteDominio, usar directamente su domain_id
        if ($cliente instanceof ClienteDominio) {
            return $cliente->getDomainId();
        }

        // Intentar acceder a domain_id si existe
        if (property_exists($cliente, 'domain_id') && !empty($cliente->domain_id)) {
            return $cliente->domain_id;
        }

        // Cargar el modelo extendido para obtener el domain_id
        if (empty($cliente->codcliente)) {
            return null;
        }

        $extended = new ClienteDominio();
        if (false === $extended->loadFromCode($cliente->codcliente)) {
            return null;
        }

        return $extended->getDomainId();
    }

    /**
     * Checks if array is a sequential list.
     */
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

    /**
     * Gets domain identifier (name or ID).
     */
    private static function domainIdentifier(ClienteDominio $domain): string
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
     * Sanitizes nameserver list.
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

    /**
     * Normalizes boolean values.
     */
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
