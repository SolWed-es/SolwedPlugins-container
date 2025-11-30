<?php

namespace FacturaScripts\Plugins\Dominios\Lib;

use FacturaScripts\Core\Tools;

final class ContactService
{
    /**
     * Attempts to locate the domain provider contact identifier associated with the given tax number.
     */
    public static function findContactIdByTaxNumber(?string $taxNumber): ?string
    {
        $candidates = self::findContactsByTaxNumber($taxNumber);
        if (1 === count($candidates)) {
            return self::extractContactIdentifier($candidates[0]);
        }

        return null;
    }

    public static function findContactsByTaxNumber(?string $taxNumber): array
    {
        $normalized = self::normalizeTaxNumber($taxNumber);
        if ('' === $normalized) {
            return [];
        }

        $client = DomainApiClient::get();
        if (null === $client) {
            return [];
        }

        try {
            $response = $client->contact_list([
                'identNumber' => $normalized,
            ]);

            $data = self::normalizeResponse($response);
            if (!empty($data)) {
                $debugPath = Tools::folder('MyFiles', 'Tmp', 'domain_last_contact.json');
                file_put_contents($debugPath, json_encode([
                    'taxNumber' => $normalized,
                    'contacts' => $data,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return $data;
        } catch (\Throwable $exception) {
            Tools::log()->warning('domain-contact-lookup-error', ['%message%' => $exception->getMessage()]);
        }

        return [];
    }

    /**
     * Extracts the list of contacts from the API response in a resilient way.
     *
     * @param mixed $response
     *
     * @return array<int,array<string,mixed>>
     */
    public static function extractContactIdentifier(array $contact): ?string
    {
        foreach (['idFormatted', 'contactID', 'id'] as $key) {
            if (!empty($contact[$key])) {
                return (string)$contact[$key];
            }
        }

        return null;
    }

    public static function buildContactLabel(array $contact): string
    {
        $parts = [];

        $identifier = self::extractContactIdentifier($contact);
        if ($identifier) {
            $parts[] = $identifier;
        }

        if (!empty($contact['contactName'])) {
            $parts[] = $contact['contactName'];
        }

        return trim(implode(' · ', array_filter($parts)));
    }

    private static function normalizeResponse(mixed $response): array
    {
        if ($response instanceof \Dondominio\API\Response\Response) {
            $data = $response->getResponseData();
        } elseif (is_object($response) && method_exists($response, 'getResponseData')) {
            $data = $response->getResponseData();
        } else {
            $data = $response;
        }

        if (!is_array($data) || empty($data)) {
            return [];
        }

        $candidates = [
            $data['contacts'] ?? null,
            $data['list'] ?? null,
            $data['items'] ?? null,
            $data['results'] ?? null,
            $data
        ];

        foreach ($candidates as $candidate) {
            if (!is_array($candidate) || empty($candidate) || false === self::isSequentialList($candidate)) {
                continue;
            }

            $contacts = [];
            foreach ($candidate as $item) {
                if (is_array($item) && !empty($item)) {
                    $contacts[] = $item;
                }
            }

            if (!empty($contacts)) {
                return $contacts;
            }
        }

        return [];
    }

    private static function normalizeTaxNumber(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        return preg_replace('/[^A-Z0-9]/i', '', strtoupper($value)) ?? '';
    }

    private static function isSequentialList(array $value): bool
    {
        $expectedKey = 0;
        foreach ($value as $key => $_) {
            if ($key !== $expectedKey) {
                return false;
            }
            ++$expectedKey;
        }

        return true;
    }
}
