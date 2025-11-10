<?php
/**
 * Servicio para obtener información de dominios para el portal del cliente
 */

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Servicio para manejar la lógica de dominios en el portal
 */
class PortalDomainService
{
    /**
     * Obtiene los contactos de DonDominio con sus dominios para un cliente específico
     *
     * @param Cliente $cliente
     * @return array
     */
    public static function getDomainContactsForClient(Cliente $cliente): array
    {
        if (!$cliente instanceof Cliente || empty($cliente->cifnif)) {
            return [];
        }

        try {
            $client = DonDominioApiClient::get();
            if (null === $client) {
                return [];
            }

            $targetTaxNumber = self::normalizeTaxNumber($cliente->cifnif);

            // 1. Obtener contactos por DNI/CIF
            $contacts = [];

            try {
                $params = [
                    'identNumber' => $targetTaxNumber,
                    'pageLength' => 100
                ];

                $response = $client->contact_list($params);
                $responseData = $response instanceof \Dondominio\API\Response\Response ? $response->getResponseData() : $response;

                // Si no hay resultados con el DNI/CIF normalizado, intentar con el original
                if (empty($responseData['contacts'])) {
                    if ($targetTaxNumber !== $cliente->cifnif) {
                        $params['identNumber'] = $cliente->cifnif;
                        $response = $client->contact_list($params);
                        $responseData = $response instanceof \Dondominio\API\Response\Response ? $response->getResponseData() : $response;
                    }

                    if (empty($responseData['contacts'])) {
                        return [];
                    }
                }

                $contacts = $responseData['contacts'];

            } catch (\Exception $e) {
                Tools::log()->error('Error obteniendo contactos: ' . $e->getMessage());
                return [];
            }

            // 2. Para cada contacto, obtener sus dominios
            $result = [];
            foreach ($contacts as $contact) {
                $contactId = $contact['contactID'] ?? null;
                if (empty($contactId)) {
                    continue;
                }

                $domains = self::getDomainsForContact($client, $contactId);

                // Añadir el contacto con sus dominios al resultado
                $result[] = [
                    'id' => $contactId,
                    'name' => $contact['contactName'] ?? 'Sin nombre',
                    'email' => $contact['email'] ?? 'Sin email',
                    'phone' => $contact['phone'] ?? 'Sin teléfono',
                    'type' => $contact['contactType'] ?? 'individual',
                    'company' => $contact['company'] ?? 'Sin empresa',
                    'tax_number' => $contact['identNumber'] ?? 'Sin NIF/CIF',
                    'country' => $contact['country'] ?? 'ES',
                    'verification_status' => $contact['verificationstatus'] ?? 'unknown',
                    'daaccepted' => $contact['daaccepted'] ?? false,
                    'domains' => $domains,
                    'domains_count' => count($domains)
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Tools::log()->error('Error al obtener contactos de DonDominio: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los dominios para un contacto específico
     *
     * @param mixed $client
     * @param string $contactId
     * @return array
     */
    private static function getDomainsForContact($client, string $contactId): array
    {
        $domains = [];

        try {
            $domainsResponse = $client->domain_list([
                'owner' => $contactId,
                'pageLength' => 100,
                'infoType' => 'status'
            ]);

            $responseData = $domainsResponse instanceof \Dondominio\API\Response\Response ? $domainsResponse->getResponseData() : $domainsResponse;

            $domainsList = [];
            if (is_array($responseData)) {
                $domainsList = $responseData['domains'] ?? ($responseData['list'] ?? ($responseData['items'] ?? []));
            }

            if (!empty($domainsList) && is_array($domainsList)) {
                foreach ($domainsList as $domain) {
                    try {
                        $domainName = $domain['name'] ?? ($domain['domain'] ?? '');
                        if (empty($domainName)) {
                            continue;
                        }

                        // Obtener información detallada del dominio
                        $detailedInfo = [];
                        $nameservers = [];
                        try {
                            $statusResponse = $client->domain_getinfo($domainName, ['infoType' => 'status']);
                            if ($statusResponse instanceof \Dondominio\API\Response\Response) {
                                $detailedInfo = $statusResponse->getResponseData() ?? [];
                            }

                            $nsResponse = $client->domain_getnameservers($domainName);
                            if ($nsResponse instanceof \Dondominio\API\Response\Response) {
                                $nsData = $nsResponse->getResponseData();
                                $nameservers = self::normalizeNameservers($nsData);
                            }
                        } catch (\Exception $e) {
                            // Continuar sin información detallada
                        }

                        // Normalizar fechas
                        $expiration = null;
                        if (!empty($domain['tsExpir'])) {
                            $expiration = is_numeric($domain['tsExpir'])
                                ? date('Y-m-d H:i:s', $domain['tsExpir'])
                                : $domain['tsExpir'];
                        }

                        $created = null;
                        if (!empty($domain['tsCreate'])) {
                            $created = is_numeric($domain['tsCreate'])
                                ? date('Y-m-d H:i:s', $domain['tsCreate'])
                                : $domain['tsCreate'];
                        }

                        $domains[] = [
                            'name' => $domainName,
                            'domain_id' => $domain['domainID'] ?? null,
                            'status' => $domain['status'] ?? 'unknown',
                            'expiration' => $expiration,
                            'created' => $created,
                            'registrant' => $domain['registrant'] ?? '',
                            'admin' => $domain['admin'] ?? '',
                            'tech' => $domain['tech'] ?? '',
                            'billing' => $domain['billing'] ?? '',
                            'registrar' => $domain['registrar'] ?? '',
                            'nameservers' => $nameservers,
                            'autorenew' => ($detailedInfo['renewalMode'] ?? '') === 'autorenew',
                            'transfer_lock' => $detailedInfo['transferBlock'] ?? false,
                            'tld' => $domain['tld'] ?? '',
                            'ownerContactID' => $detailedInfo['ownerContactID'] ?? null,
                            'adminContactID' => $detailedInfo['adminContactID'] ?? null,
                            'techContactID' => $detailedInfo['techContactID'] ?? null,
                            'billingContactID' => $detailedInfo['billingContactID'] ?? null,
                        ];

                    } catch (\Exception $e) {
                        // Continuar con el siguiente dominio
                    }
                }
            }
        } catch (\Exception $e) {
            Tools::log()->error('Error al listar dominios para el contacto ' . $contactId . ': ' . $e->getMessage());
        }

        return $domains;
    }

    /**
     * Normaliza un número fiscal eliminando caracteres no alfanuméricos
     *
     * @param string $taxNumber
     * @return string
     */
    private static function normalizeTaxNumber(string $taxNumber): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $taxNumber));
    }

    /**
     * Normaliza nameservers de la API a un array de strings
     *
     * @param mixed $data
     * @return array
     */
    private static function normalizeNameservers($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $candidates = [
            $data['nameservers'] ?? null,
            $data['list'] ?? null,
            $data['items'] ?? null,
            $data
        ];

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $result = [];
            foreach ($candidate as $item) {
                if (is_string($item)) {
                    $item = trim($item);
                    if ('' !== $item) {
                        $result[] = $item;
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
}
