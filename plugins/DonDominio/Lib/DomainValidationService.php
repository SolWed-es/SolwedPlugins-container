<?php
/**
 * This file is part of the DonDominio plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FacturaScripts\Plugins\DonDominio\Lib;

use FacturaScripts\Core\Tools;

/**
 * Servicio para validaciones de dominios sin efectuar compras/transferencias.
 * Solo verifica disponibilidad, transferibilidad y precios.
 */
final class DomainValidationService
{
    private ?\Dondominio\API\API $apiClient = null;

    public function __construct()
    {
        $this->apiClient = DonDominioApiClient::get();
    }

    /**
     * Verifica si un dominio está disponible para registro.
     *
     * @param string $domain Nombre del dominio a verificar
     * @return array Array con disponibilidad y detalles
     */
    public function checkAvailability(string $domain): array
    {
        if (null === $this->apiClient || empty($domain)) {
            return [
                'available' => false,
                'domain' => $domain,
                'error' => true,
                'message' => 'API not available',
            ];
        }

        try {
            $domain = strtolower(trim($domain));

            // Asegurar que tiene el punto
            if (!str_starts_with($domain, '.') && strpos($domain, '.') === false) {
                return [
                    'available' => false,
                    'domain' => $domain,
                    'error' => true,
                    'message' => 'Invalid domain format',
                ];
            }

            $response = $this->apiClient->domain_check($domain);
            $data = $this->unwrapResponse($response);

            if (!is_array($data)) {
                return [
                    'available' => false,
                    'domain' => $domain,
                    'error' => true,
                    'message' => 'Invalid API response',
                ];
            }

            return [
                'available' => (bool)($data['available'] ?? false),
                'domain' => $domain,
                'price' => $data['price'] ?? null,
                'premium' => (bool)($data['premium'] ?? false),
                'error' => false,
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-check-availability-error', [
                '%domain%' => $domain,
                '%message%' => $e->getMessage(),
            ]);

            return [
                'available' => false,
                'domain' => $domain,
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica si un dominio puede ser transferido.
     *
     * @param string $domain Nombre del dominio a verificar
     * @return array Array con información de transferencia
     */
    public function checkTransferability(string $domain): array
    {
        if (null === $this->apiClient || empty($domain)) {
            return [
                'transferable' => false,
                'domain' => $domain,
                'error' => true,
                'message' => 'API not available',
            ];
        }

        try {
            $domain = strtolower(trim($domain));

            $response = $this->apiClient->domain_checkForTransfer($domain);
            $data = $this->unwrapResponse($response);

            if (!is_array($data)) {
                return [
                    'transferable' => false,
                    'domain' => $domain,
                    'error' => true,
                    'message' => 'Invalid API response',
                ];
            }

            return [
                'transferable' => (bool)($data['transferable'] ?? false),
                'domain' => $domain,
                'reason' => $data['reason'] ?? null,
                'message' => $data['message'] ?? null,
                'error' => false,
            ];
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-check-transfer-error', [
                '%domain%' => $domain,
                '%message%' => $e->getMessage(),
            ]);

            return [
                'transferable' => false,
                'domain' => $domain,
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtiene precios de TLDs disponibles.
     *
     * @param array $filters Filtros opcionales (tld, service, etc.)
     * @return array Array de TLDs con precios
     */
    public function getTldPrices(array $filters = []): array
    {
        if (null === $this->apiClient) {
            return [];
        }

        try {
            $args = array_merge([
                'pageLength' => 100,
            ], $filters);

            $response = $this->apiClient->account_zones($args);
            $data = $this->unwrapResponse($response);

            if (!is_array($data)) {
                return [];
            }

            $zones = $data['zones'] ?? $data['list'] ?? [];
            if (!is_array($zones)) {
                return [];
            }

            $result = [];
            foreach ($zones as $zone) {
                if (!isset($zone['name'])) {
                    continue;
                }

                $result[] = [
                    'tld' => $zone['name'],
                    'price_create' => (float)($zone['priceCreate'] ?? $zone['price_create'] ?? 0),
                    'price_renew' => (float)($zone['priceRenew'] ?? $zone['price_renew'] ?? 0),
                    'price_transfer' => (float)($zone['priceTransfer'] ?? $zone['price_transfer'] ?? 0),
                    'price_ownership' => (float)($zone['priceOwnership'] ?? 0),
                    'min_period' => (int)($zone['minPeriod'] ?? $zone['min_period'] ?? 1),
                    'max_period' => (int)($zone['maxPeriod'] ?? $zone['max_period'] ?? 10),
                    'idn' => (bool)($zone['idn'] ?? false),
                ];
            }

            usort($result, function ($a, $b) {
                return strcmp($a['tld'], $b['tld']);
            });

            return $result;
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-get-tld-prices-error', [
                '%message%' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Obtiene sugerencias de dominios basadas en una palabra clave.
     *
     * @param string $keyword Palabra clave para sugerencias
     * @param int $limit Número máximo de sugerencias
     * @return array Array de dominios sugeridos
     */
    public function suggestDomains(string $keyword, int $limit = 10): array
    {
        if (null === $this->apiClient || empty($keyword)) {
            return [];
        }

        try {
            $keyword = strtolower(trim($keyword));
            $keyword = preg_replace('/[^a-z0-9\-]/', '', $keyword);

            if (strlen($keyword) < 2) {
                return [];
            }

            $response = $this->apiClient->tool_domainSuggests([
                'query' => $keyword,
                'tlds' => ['com', 'es', 'net', 'org', 'info'],
                'max' => min($limit, 50),
            ]);

            $data = $this->unwrapResponse($response);

            if (!is_array($data)) {
                return [];
            }

            $suggestions = $data['suggestions'] ?? $data['list'] ?? [];
            if (!is_array($suggestions)) {
                return [];
            }

            $result = [];
            foreach ($suggestions as $suggest) {
                if (!isset($suggest['domain'])) {
                    continue;
                }

                $result[] = [
                    'domain' => $suggest['domain'],
                    'available' => (bool)($suggest['available'] ?? false),
                    'price' => (float)($suggest['price'] ?? 0),
                ];
            }

            return array_slice($result, 0, $limit);
        } catch (\Throwable $e) {
            Tools::log()->error('dondominio-suggest-domains-error', [
                '%keyword%' => $keyword,
                '%message%' => $e->getMessage(),
            ]);

            return [];
        }
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
}
