<?php
/**
 * This file is part of the DonDominio plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Servicio simplificado para obtener datos de dominios directamente desde la API de DonDominio.
 * No utiliza caché ni persistencia en base de datos.
 */
final class DomainApiService
{
    private ?\Dondominio\API\API $apiClient = null;

    public function __construct()
    {
        $this->apiClient = DonDominioApiClient::get();
    }

    /**
     * Obtiene los contactos y sus dominios para un cliente específico.
     * Los datos se obtienen directamente de la API sin caché.
     *
     * @param string $codcliente Código del cliente
     * @param bool $retry Si es true, reintenta en caso de error
     * @return array Array de contactos con sus dominios
     */
    public function getClientContacts(string $codcliente, bool $retry = false): array
    {
        if (null === $this->apiClient) {
            return [];
        }

        $cliente = new Cliente();
        if (!$cliente->loadFromCode($codcliente) || !$cliente->exists()) {
            return [];
        }

        $taxNumber = trim((string)$cliente->cifnif);
        if (empty($taxNumber)) {
            return [];
        }

        try {
            return $this->fetchContactsFromApi($taxNumber, $codcliente);
        } catch (\Dondominio\API\Exceptions\Authentication\Login_Invalid $e) {
            Tools::log()->error('dondominio-auth-error', [
                '%code%' => $codcliente,
                '%message%' => 'Credenciales de API inválidas',
            ]);
        } catch (\Dondominio\API\Exceptions\Account\InsufficientBalance $e) {
            Tools::log()->error('dondominio-balance-error', [
                '%code%' => $codcliente,
                '%message%' => 'Saldo insuficiente en cuenta DonDominio',
            ]);
        } catch (\Dondominio\API\Exceptions\HttpError $e) {
            Tools::log()->error('dondominio-http-error', [
                '%code%' => $codcliente,
                '%message%' => 'Error de conexión con la API de DonDominio',
            ]);
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-fetch-contacts-error', [
                '%code%' => $codcliente,
                '%message%' => $e->getMessage(),
            ]);

            if ($retry) {
                // Reintentar una sola vez
                try {
                    return $this->fetchContactsFromApi($taxNumber, $codcliente);
                } catch (\Throwable $retryError) {
                    Tools::log()->error('dondominio-fetch-contacts-retry-failed', [
                        '%code%' => $codcliente,
                        '%message%' => $retryError->getMessage(),
                    ]);
                }
            }
        }

        return [];
    }

    /**
     * Obtiene los dominios que expiran en los próximos N días para un cliente.
     *
     * @param string $codcliente Código del cliente
     * @param int $days Número de días a comprobar
     * @return array Array de dominios próximos a expirar
     */
    public function getExpiringDomains(string $codcliente, int $days = 30): array
    {
        $contacts = $this->getClientContacts($codcliente);
        $expiringDomains = [];
        $now = new \DateTime();
        $endDate = (clone $now)->add(new \DateInterval("P{$days}D"));

        foreach ($contacts as $contact) {
            if (empty($contact['domains'])) {
                continue;
            }

            foreach ($contact['domains'] as $domain) {
                $expirationDate = $this->parseDate($domain['expiration'] ?? null);
                if ($expirationDate && $expirationDate <= $endDate && $expirationDate >= $now) {
                    $expiringDomains[] = [
                        'name' => $domain['name'] ?? 'unknown',
                        'expiration' => $domain['expiration'],
                        'contact' => $contact['name'] ?? 'unknown',
                    ];
                }
            }
        }

        usort($expiringDomains, function ($a, $b) {
            return strtotime($a['expiration']) <=> strtotime($b['expiration']);
        });

        return $expiringDomains;
    }

    /**
     * Obtiene los nameservers de un dominio específico.
     *
     * @param string $domain Nombre del dominio
     * @return array Array de nameservers
     */
    public function getDomainNameservers(string $domain): array
    {
        if (null === $this->apiClient || empty($domain)) {
            return [];
        }

        try {
            $response = $this->apiClient->domain_getinfo($domain, ['infoType' => 'nameservers']);
            $data = $this->unwrapResponse($response);

            if (!is_array($data)) {
                return [];
            }

            return $this->extractNameservers($data);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene información completa de un dominio específico.
     *
     * @param string $domain Nombre del dominio
     * @return array|null Datos del dominio o null si error
     */
    public function getDomainInfo(string $domain): ?array
    {
        if (null === $this->apiClient || empty($domain)) {
            return null;
        }

        try {
            $response = $this->apiClient->domain_getinfo($domain, ['infoType' => 'status']);
            $data = $this->unwrapResponse($response);

            if (!is_array($data)) {
                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Obtiene los contactos desde la API usando el NIF del cliente.
     *
     * @param string $taxNumber NIF/CIF del cliente
     * @param string $codcliente Código del cliente (para logging)
     * @return array Array de contactos con sus dominios
     */
    private function fetchContactsFromApi(string $taxNumber, string $codcliente): array
    {
        // Buscar contactos por NIF
        $response = $this->apiClient->contact_list(['identNumber' => $taxNumber]);
        $contactsData = $this->unwrapResponse($response);

        if (!is_array($contactsData)) {
            return [];
        }

        $contactList = $contactsData['contacts'] ?? $contactsData['list'] ?? [];
        if (!is_array($contactList)) {
            $contactList = [];
        }

        $result = [];

        foreach ($contactList as $contactData) {
            $contactId = $contactData['id'] ?? $contactData['contactID'] ?? null;
            if (empty($contactId)) {
                continue;
            }

            $domains = $this->fetchDomainsForContact($contactId);

            $result[] = [
                'id' => $contactId,
                'name' => $contactData['contactName'] ?? $contactData['name'] ?? 'Sin nombre',
                'email' => $contactData['email'] ?? '',
                'phone' => $contactData['phone'] ?? '',
                'company' => $contactData['company'] ?? '',
                'country' => $contactData['country'] ?? '',
                'verification_status' => $contactData['verificationstatus'] ?? '',
                'daaccepted' => (bool)($contactData['daaccepted'] ?? false),
                'domains_count' => count($domains),
                'domains' => $domains,
                'fetched_at' => date('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    /**
     * Obtiene los dominios de un contacto específico desde la API.
     *
     * @param string $contactId ID del contacto
     * @return array Array de dominios
     */
    private function fetchDomainsForContact(string $contactId): array
    {
        if (null === $this->apiClient) {
            return [];
        }

        try {
            $response = $this->apiClient->domain_list([
                'owner' => $contactId,
                'pageLength' => 100,
                'infoType' => 'status',
            ]);

            $data = $this->unwrapResponse($response);
            $domainsList = $data['domains'] ?? $data['list'] ?? [];

            if (!is_array($domainsList)) {
                $domainsList = [];
            }

            $domains = [];
            foreach ($domainsList as $domainData) {
                $domainName = $domainData['domain'] ?? $domainData['name'] ?? null;
                if (empty($domainName)) {
                    continue;
                }

                $nameservers = $this->extractNameservers($domainData);
                if (empty($nameservers)) {
                    $nameservers = $this->getDomainNameservers($domainName);
                }

                // Determinar autorenew: usar el valor directo o fallback a renewalMode
                $autorenewValue = $domainData['autorenew'] ?? null;
                $renewalMode = $domainData['renewalMode'] ?? null;
                $autorenew = false;

                if ($autorenewValue !== null) {
                    $autorenew = (bool)$autorenewValue;
                } elseif ($renewalMode !== null) {
                    $autorenew = ($renewalMode === 'autorenew');
                }

                $domains[] = [
                    'name' => $domainName,
                    'domainID' => $domainData['domainID'] ?? null,
                    'status' => $domainData['status'] ?? 'unknown',
                    'expiration' => $this->normalizeDate($domainData['tsExpir'] ?? $domainData['expiration'] ?? null),
                    'registered_at' => $this->normalizeDate($domainData['tsCreate'] ?? $domainData['created'] ?? null),
                    'autorenew' => $autorenew,
                    'nameservers' => $nameservers,
                    'tld' => $domainData['tld'] ?? null,
                    'renewal_mode' => $renewalMode,
                    'renewable' => (bool)($domainData['renewable'] ?? false),
                    'transfer_block' => (bool)($domainData['transferBlock'] ?? false),
                    'modify_block' => (bool)($domainData['modifyBlock'] ?? false),
                    'whois_privacy' => (bool)($domainData['whoisPrivacy'] ?? false),
                    'owner_verification' => $domainData['ownerverification'] ?? null,
                    'service_associated' => (bool)($domainData['serviceAssociated'] ?? false),
                ];
            }

            return $domains;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Extrae nameservers de la respuesta API.
     *
     * @param array $data Datos de respuesta
     * @return array Array de nameservers
     */
    private function extractNameservers(array $data): array
    {
        // Buscar en ubicaciones comunes
        $candidates = [
            $data['nameservers'] ?? null,
            $data['list'] ?? null,
            $data['items'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $result = $this->parseNameserverArray($candidate);
            if (!empty($result)) {
                return $result;
            }
        }

        // Buscar ns1, ns2, ns3, etc.
        $result = [];
        foreach ($data as $key => $value) {
            if (preg_match('/^ns\d+/i', $key) && is_string($value)) {
                $value = trim($value);
                if (!empty($value)) {
                    $result[] = $value;
                }
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Parsea un array de nameservers.
     *
     * @param mixed $data Datos a parsear
     * @return array Array de nameservers
     */
    private function parseNameserverArray(mixed $data): array
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        if (is_string($data)) {
            $parts = preg_split('/[\s,;]+/', $data);
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part) && str_contains($part, '.')) {
                    $result[] = $part;
                }
            }
            return array_values(array_unique($result));
        }

        if (is_array($data)) {
            foreach ($data as $entry) {
                if (is_string($entry)) {
                    $entry = trim($entry);
                    if (!empty($entry) && str_contains($entry, '.')) {
                        $result[] = $entry;
                    }
                } elseif (is_array($entry)) {
                    $ns = $entry['host'] ?? $entry['hostname'] ?? $entry['name'] ?? $entry['value'] ?? null;
                    if (is_string($ns)) {
                        $ns = trim($ns);
                        if (!empty($ns) && str_contains($ns, '.')) {
                            $result[] = $ns;
                        }
                    }
                }
            }
            return array_values(array_unique($result));
        }

        return [];
    }

    /**
     * Convierte una respuesta API a array si es necesario.
     *
     * @param mixed $response Respuesta de la API
     * @return mixed Datos de la respuesta
     */
    private function unwrapResponse(mixed $response): mixed
    {
        if (is_object($response) && method_exists($response, 'getResponseData')) {
            return $response->getResponseData();
        }

        return $response;
    }

    /**
     * Normaliza una fecha a formato Y-m-d H:i:s.
     *
     * @param mixed $value Valor a normalizar
     * @return string|null Fecha normalizada o null
     */
    private function normalizeDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int)$value);
        }

        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Parsea una fecha para comparación.
     *
     * @param string|null $dateString Cadena de fecha
     * @return \DateTime|null Objeto DateTime o null
     */
    private function parseDate(?string $dateString): ?\DateTime
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return new \DateTime($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    // =====================================================================
    // FASE 1: FUNCIONALIDADES DE SOLO LECTURA
    // =====================================================================

    /**
     * Obtiene el código de autorización (AuthCode) de un dominio.
     * Necesario para transferencias salientes.
     *
     * @param string $domain Nombre del dominio
     * @return string|null Código de autorización o null si error
     */
    public function getDomainAuthCode(string $domain): ?string
    {
        if (null === $this->apiClient || empty($domain)) {
            return null;
        }

        try {
            $response = $this->apiClient->domain_getAuthCode($domain);
            $data = $this->unwrapResponse($response);
            return $data['authcode'] ?? null;
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-authcode-error', [
                '%domain%' => $domain,
                '%message%' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtiene el historial de cambios de un dominio.
     *
     * @param string $domain Nombre del dominio
     * @param int $pageLength Número máximo de resultados
     * @return array Array de eventos del historial
     */
    public function getDomainHistory(string $domain, int $pageLength = 20): array
    {
        if (null === $this->apiClient || empty($domain)) {
            return [];
        }

        try {
            $response = $this->apiClient->domain_getHistory($domain, [
                'pageLength' => $pageLength,
                'page' => 1,
            ]);
            $data = $this->unwrapResponse($response);
            return $data['history'] ?? $data['list'] ?? [];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-history-error', [
                '%domain%' => $domain,
                '%message%' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Realiza un test DNS (DIG) para verificar configuración.
     *
     * @param string $domain Nombre del dominio o host
     * @param string $type Tipo de registro (A, AAAA, MX, NS, TXT, etc.)
     * @return array|null Resultado del DIG o null si error
     */
    public function performDnsTest(string $domain, string $type = 'A'): ?array
    {
        if (null === $this->apiClient || empty($domain)) {
            return null;
        }

        try {
            $response = $this->apiClient->tool_dig([
                'host' => $domain,
                'type' => $type,
            ]);
            return $this->unwrapResponse($response);
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-dig-error', [
                '%domain%' => $domain,
                '%type%' => $type,
                '%message%' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtiene información de la cuenta de DonDominio (saldo, etc.).
     *
     * @return array|null Información de cuenta o null si error
     */
    public function getAccountInfo(): ?array
    {
        if (null === $this->apiClient) {
            return null;
        }

        try {
            $response = $this->apiClient->account_info();
            $data = $this->unwrapResponse($response);

            return [
                'balance' => (float)($data['balance'] ?? 0),
                'currency' => $data['currency'] ?? 'EUR',
                'account_name' => $data['accountName'] ?? '',
                'account_id' => $data['accountID'] ?? '',
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-account-info-error', [
                '%message%' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // =====================================================================
    // FASE 2: FUNCIONALIDADES CON ACCIONES
    // =====================================================================

    /**
     * Actualiza los nameservers de un dominio.
     *
     * @param string $domain Nombre del dominio
     * @param array $nameservers Array de nameservers (mínimo 2, máximo 7)
     * @return array Resultado de la operación
     */
    public function updateDomainNameservers(string $domain, array $nameservers): array
    {
        if (null === $this->apiClient || empty($domain) || empty($nameservers)) {
            return [
                'success' => false,
                'error' => 'Parámetros inválidos',
            ];
        }

        // Validar cantidad de nameservers
        $count = count($nameservers);
        if ($count < 2 || $count > 7) {
            return [
                'success' => false,
                'error' => 'Debe proporcionar entre 2 y 7 nameservers',
            ];
        }

        try {
            $response = $this->apiClient->domain_updateNameServers($domain, $nameservers);
            $data = $this->unwrapResponse($response);

            return [
                'success' => true,
                'message' => 'Nameservers actualizados correctamente',
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-update-ns-error', [
                '%domain%' => $domain,
                '%message%' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Renueva un dominio por el número de años especificado.
     * ATENCIÓN: Esta operación usa crédito de la cuenta DonDominio.
     *
     * @param string $domain Nombre del dominio
     * @param int $years Número de años de renovación (1-10)
     * @return array Resultado de la operación con coste
     */
    public function renewDomain(string $domain, int $years = 1): array
    {
        if (null === $this->apiClient || empty($domain)) {
            return [
                'success' => false,
                'error' => 'Parámetros inválidos',
            ];
        }

        if ($years < 1 || $years > 10) {
            return [
                'success' => false,
                'error' => 'El período debe ser entre 1 y 10 años',
            ];
        }

        try {
            $response = $this->apiClient->domain_renew($domain, ['period' => $years]);
            $data = $this->unwrapResponse($response);

            return [
                'success' => true,
                'order_id' => $data['orderID'] ?? null,
                'cost' => (float)($data['cost'] ?? 0),
                'currency' => $data['currency'] ?? 'EUR',
                'message' => 'Dominio renovado correctamente',
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-renew-error', [
                '%domain%' => $domain,
                '%years%' => $years,
                '%message%' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reenvía el email de verificación de un dominio.
     *
     * @param string $domain Nombre del dominio
     * @return array Resultado de la operación
     */
    public function resendVerificationEmail(string $domain): array
    {
        if (null === $this->apiClient || empty($domain)) {
            return [
                'success' => false,
                'error' => 'Parámetros inválidos',
            ];
        }

        try {
            $response = $this->apiClient->domain_resendVerificationMail($domain);
            $data = $this->unwrapResponse($response);

            return [
                'success' => true,
                'message' => 'Email de verificación reenviado correctamente',
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-resend-verification-error', [
                '%domain%' => $domain,
                '%message%' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Activa o desactiva la renovación automática de un dominio.
     *
     * @param string $domain Nombre del dominio
     * @param bool $enable True para activar, false para desactivar
     * @param string $codcliente Código del cliente (opcional, para validación)
     * @param bool $isAdmin Si es administrador (sin restricciones)
     * @return array Resultado de la operación
     */
    public function setAutoRenew(string $domain, bool $enable, string $codcliente = '', bool $isAdmin = false): array
    {
        if (null === $this->apiClient || empty($domain)) {
            return [
                'success' => false,
                'error' => 'Parámetros inválidos',
            ];
        }

        try {
            $response = $this->apiClient->domain_update($domain, [
                'updateType' => 'renewalMode',
                'renewalMode' => $enable ? 'autorenew' : 'manual',
            ]);
            $data = $this->unwrapResponse($response);

            return [
                'success' => true,
                'message' => $enable ? 'Renovación automática activada' : 'Renovación automática desactivada',
                'autorenew' => $enable,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-autorenew-error', [
                '%domain%' => $domain,
                '%enable%' => $enable ? 'true' : 'false',
                '%message%' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Activa o desactiva el bloqueo de transferencia de un dominio.
     *
     * @param string $domain Nombre del dominio
     * @param bool $enable True para bloquear, false para desbloquear
     * @return array Resultado de la operación
     */
    public function setTransferLock(string $domain, bool $enable): array
    {
        if (null === $this->apiClient || empty($domain)) {
            return [
                'success' => false,
                'error' => 'Parámetros inválidos',
            ];
        }

        try {
            $response = $this->apiClient->domain_update($domain, [
                'updateType' => 'block',
                'block' => $enable ? 'true' : 'false',
            ]);
            $data = $this->unwrapResponse($response);

            return [
                'success' => true,
                'message' => $enable ? 'Bloqueo de transferencia activado' : 'Bloqueo de transferencia desactivado',
                'blocked' => $enable,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-transfer-lock-error', [
                '%domain%' => $domain,
                '%enable%' => $enable ? 'true' : 'false',
                '%message%' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica la disponibilidad de un dominio para compra.
     *
     * @param string $domain Nombre del dominio a verificar
     * @return array Resultado con disponibilidad y precio
     */
    public function checkDomainAvailability(string $domain): array
    {
        if (null === $this->apiClient || empty($domain)) {
            return [
                'success' => false,
                'error' => 'Parámetros inválidos',
            ];
        }

        try {
            $response = $this->apiClient->domain_check($domain);
            $domains = $response->get('domains');

            if (empty($domains)) {
                return [
                    'success' => false,
                    'error' => 'No se pudo verificar el dominio',
                ];
            }

            $domainInfo = $domains[0];

            return [
                'success' => true,
                'available' => (bool)($domainInfo['available'] ?? false),
                'domain' => $domainInfo['name'] ?? $domain,
                'price' => (float)($domainInfo['price'] ?? 0),
                'currency' => $domainInfo['currency'] ?? 'EUR',
                'premium' => (bool)($domainInfo['premium'] ?? false),
                'tld' => $domainInfo['tld'] ?? '',
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-check-domain-error', [
                '%domain%' => $domain,
                '%message%' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Compra/registra un dominio nuevo.
     * ATENCIÓN: Esta operación usa crédito de la cuenta DonDominio.
     *
     * @param string $domain Nombre del dominio a comprar
     * @param string $codcliente Código del cliente
     * @param int $years Número de años de registro (1-10)
     * @param bool $isAdmin Si es administrador (bypass de validaciones)
     * @return array Resultado de la operación con detalles de compra
     */
    public function purchaseDomain(string $domain, string $codcliente, int $years = 1, bool $isAdmin = false): array
    {
        if (null === $this->apiClient || empty($domain) || empty($codcliente)) {
            return [
                'success' => false,
                'error' => 'Parámetros inválidos',
            ];
        }

        if ($years < 1 || $years > 10) {
            return [
                'success' => false,
                'error' => 'El período debe ser entre 1 y 10 años',
            ];
        }

        // 2. Cargar datos del cliente
        $cliente = new Cliente();
        if (!$cliente->loadFromCode($codcliente) || !$cliente->exists()) {
            return [
                'success' => false,
                'error' => 'Cliente no encontrado',
            ];
        }

        // 1. Verificar disponibilidad del dominio
        $availabilityCheck = $this->checkDomainAvailability($domain);
        if (!$availabilityCheck['success']) {
            return $availabilityCheck;
        }

        if (!$availabilityCheck['available']) {
            return [
                'success' => false,
                'error' => 'El dominio no está disponible para registro',
            ];
        }

        // 2. Verificar saldo de cuenta
        $accountInfo = $this->getAccountInfo();
        if (null !== $accountInfo) {
            $estimatedCost = $availabilityCheck['price'] * $years;
            if ($accountInfo['balance'] < $estimatedCost) {
                return [
                    'success' => false,
                    'error' => sprintf(
                        'Saldo insuficiente. Disponible: %.2f %s, Necesario: %.2f %s',
                        $accountInfo['balance'],
                        $accountInfo['currency'],
                        $estimatedCost,
                        $availabilityCheck['currency']
                    ),
                ];
            }
        }

        // 3. Preparar datos de contacto desde el cliente
        $contactData = $this->prepareContactDataFromCliente($cliente);
        $contactData['period'] = $years;
        $contactData['premium'] = $availabilityCheck['premium'];
        $contactData['nameservers'] = 'parking'; // Por defecto parking de DonDominio

        // 4. Ejecutar compra
        try {
            $response = $this->apiClient->domain_create($domain, $contactData);
            $data = $this->unwrapResponse($response);

            $billing = $response->get('billing');
            $domains = $response->get('domains');

            Tools::log()->info('dondominio-domain-purchased', [
                '%domain%' => $domain,
                '%codcliente%' => $codcliente,
                '%years%' => $years,
                '%cost%' => $billing['total'] ?? 0,
            ]);

            return [
                'success' => true,
                'message' => 'Dominio comprado correctamente',
                'domain' => $domains[0] ?? [],
                'cost' => (float)($billing['total'] ?? 0),
                'currency' => $billing['currency'] ?? 'EUR',
                'order_id' => $data['orderID'] ?? null,
            ];
        } catch (\Dondominio\API\Exceptions\Domain\Create\Taken $e) {
            return [
                'success' => false,
                'error' => 'El dominio ya está registrado',
            ];
        } catch (\Dondominio\API\Exceptions\Domain\Create\PremiumDomain $e) {
            return [
                'success' => false,
                'error' => 'Este es un dominio premium. Contacte con soporte para más información.',
            ];
        } catch (\Dondominio\API\Exceptions\Account\InsufficientBalance $e) {
            return [
                'success' => false,
                'error' => 'Saldo insuficiente en la cuenta DonDominio',
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-purchase-error', [
                '%domain%' => $domain,
                '%codcliente%' => $codcliente,
                '%message%' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepara los datos de contacto para registro de dominio desde datos del cliente.
     *
     * @param Cliente $cliente Modelo del cliente
     * @return array Datos de contacto formateados para la API
     */
    private function prepareContactDataFromCliente(Cliente $cliente): array
    {
        // Formatear teléfono al formato +34.600000000
        $phone = preg_replace('/[^0-9+]/', '', $cliente->telefono1 ?? '');
        if (!str_starts_with($phone, '+')) {
            $phone = '+34.' . ltrim($phone, '0');
        } elseif (!str_contains($phone, '.')) {
            $phone = str_replace('+', '+', $phone);
            $parts = explode('+', $phone);
            if (count($parts) === 2) {
                $phone = '+' . substr($parts[1], 0, 2) . '.' . substr($parts[1], 2);
            }
        }

        return [
            'ownerContactType' => 'organization',
            'ownerContactFirstName' => $cliente->nombre ?? 'Nombre',
            'ownerContactLastName' => 'Empresa',
            'ownerContactOrgName' => $cliente->nombre ?? '',
            'ownerContactIdentNumber' => $cliente->cifnif ?? '',
            'ownerContactEmail' => $cliente->email ?? 'noreply@example.com',
            'ownerContactPhone' => $phone,
            'ownerContactAddress' => $cliente->direccion ?? 'Calle Principal 1',
            'ownerContactCity' => $cliente->ciudad ?? 'Madrid',
            'ownerContactPostalCode' => $cliente->codpostal ?? '28001',
            'ownerContactState' => $cliente->provincia ?? 'Madrid',
            'ownerContactCountry' => $cliente->codpais ?? 'ES',
        ];
    }
}
