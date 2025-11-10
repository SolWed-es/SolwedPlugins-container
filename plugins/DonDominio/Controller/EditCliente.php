<?php

namespace FacturaScripts\Plugins\DonDominio\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioApiClient;
use FacturaScripts\Core\Controller\EditCliente as BaseEditCliente;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\Widget\WidgetSelect;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioContactService;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioDomainService;
// ClienteDonDominio ha sido reemplazado por ClienteDonDominio
use FacturaScripts\Plugins\DonDominio\Model\ClienteDonDominio;

class EditCliente extends BaseEditCliente
{
    /**
     * Muestra mensajes de depuración en la consola del navegador
     * @param string $message
     * @param mixed $data
     * @param string $type log|warn|error
     */
    protected function consoleLog($message, $data = null, $type = 'log')
    {
        $jsonData = $data !== null ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'null';
        echo "<script>"
           . "if (typeof console !== 'undefined' && typeof console.{$type} === 'function') {"
           . "console.{$type}('DonDominio: ' + " . json_encode($message) . ", " . $jsonData . ");"
           . "}"
           . "</script>\n";
    }

    private const DOMAINS_VIEW = 'EditClienteDonDominio';
    private const ERP_VIEW = 'EditClienteErp';
    private const ACTION_DOMAIN_DETAILS = 'dondominio-domain-details';
    private const ACTION_DOMAIN_TOGGLE_AUTORENEW = 'dondominio-domain-toggle-autorenew';
    private const ACTION_DOMAIN_AUTHCODE = 'get-domain-authcode';
    private const ACTION_DOMAIN_TRANSFER_LOCK_STATUS = 'get-domain-transfer-lock';
    private const ACTION_DOMAIN_TRANSFER_LOCK_UPDATE = 'update-domain-transfer-lock';
    private const ACTION_DOMAIN_GET_NAMESERVERS = 'dondominio-domain-get-nameservers';
    private const ACTION_DOMAIN_UPDATE_NAMESERVERS = 'dondominio-domain-update-nameservers';
    private const ACTION_ADD_DOMAIN = 'dondominio-add-domain';
    private const ACTION_SET_CONTACT_FILTER = 'dondominio-set-domain-contact';
    private const ACTION_DOWNLOAD_CONTRACT = 'dondominio-download-autorenew-contract';
    private const AUTORENEW_CONTRACT_URL = 'https://filedn.eu/litOB0SUT8q5aLOM933djFm/Contrato%20domiciliaci%C3%B3n-Solwed.pdf';

    /** @var array<string,array<string,string>> */
    private $contactOptionsCache = [];

    protected function createViews()
    {
        parent::createViews();
        $this->createDomainsView();
        $this->createErpView();
    }

    protected function loadData($viewName, $view)
    {
        parent::loadData($viewName, $view);

        if (self::DOMAINS_VIEW === $viewName) {
            $this->loadDomainsView($view);
            return;
        }

        if (self::ERP_VIEW === $viewName) {
            $this->loadErpView($view);
        }
    }

    protected function execPreviousAction($action)
    {
        if ('sync-dondominio-domains' === $action) {
            if (false === $this->validateFormToken()) {
                return true;
            }

            $count = $this->syncDomainsAction();
            if ($count >= 0) {
                Tools::log()->notice('dondominio-sync-ok', ['%count%' => $count]);
            }

            return true;
        }

        if ('get-expiring-domains-count' === $action) {
            return $this->getExpiringDomainsCountAction();
        }

        if ('validate-autologin-token' === $action) {
            return $this->validateAutologinTokenAction();
        }

        if ('erp-autologin' === $action) {
            return $this->erpAutoLoginAction();
        }

        if (self::ACTION_ADD_DOMAIN === $action) {
            return $this->addDomainAction();
        }

        if (self::ACTION_DOMAIN_TOGGLE_AUTORENEW === $action) {
            return $this->toggleAutoRenewAction();
        }

        if (self::ACTION_DOWNLOAD_CONTRACT === $action) {
            return $this->downloadAutoRenewContractAction();
        }

        if (self::ACTION_DOMAIN_AUTHCODE === $action) {
            return $this->getDomainAuthcodeAction();
        }

        if (self::ACTION_DOMAIN_TRANSFER_LOCK_STATUS === $action) {
            return $this->getDomainTransferLockAction();
        }

        if (self::ACTION_DOMAIN_TRANSFER_LOCK_UPDATE === $action) {
            return $this->updateDomainTransferLockAction();
        }

        if (self::ACTION_SET_CONTACT_FILTER === $action) {
            return $this->setDomainsAction();
        }

        if (self::ACTION_DOMAIN_GET_NAMESERVERS === $action) {
            return $this->getDomainNameserversAction();
        }

        if (self::ACTION_DOMAIN_UPDATE_NAMESERVERS === $action) {
            return $this->updateDomainNameserversAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function insertAction(): bool
    {
        $this->ensureDomainBinding();
        return parent::insertAction();
    }

    protected function editAction(): bool
    {
        $this->ensureDomainBinding();
        
        // Si estamos editando desde la vista ERP, asegurar que se guarde el dondominio_id
        if ($this->active === self::ERP_VIEW) {
            $mainViewName = $this->getMainViewName();
            if (isset($this->views[$mainViewName])) {
                $model = $this->views[$mainViewName]->model;
                if ($model instanceof Cliente && $model->exists()) {
                    $dondominioId = $this->request->request->get('dondominio_id');
                    if (!empty($dondominioId)) {
                        $erpModel = new ClienteDonDominio();
                        $erpModel->loadFromCode($model->codcliente);
                        $erpModel->dondominio_id = $dondominioId;
                        $erpModel->save();
                    }
                }
            }
        }
        
        return parent::editAction();
    }

    private function ensureDomainBinding(): void
    {
        if ($this->active !== self::DOMAINS_VIEW) {
            return;
        }

        $mainViewName = $this->getMainViewName();
        if (!isset($this->views[$mainViewName])) {
            return;
        }

        $model = $this->views[$mainViewName]->model;
        if ($model instanceof Cliente && $model->exists()) {
            $this->request->request->set('codcliente', $model->codcliente);
        }
    }

    private function createDomainsView(): void
    {
        $this->addHtmlView(
            self::DOMAINS_VIEW,
            'Block/Domains',
            'Cliente',
            'Dominios',
            'fas fa-globe'
        );
    }
    
    /**
     * Carga la vista de dominios y obtiene los contactos de DonDominio por DNI.
     * 
     * @param BaseView $view
     * @return void
     */
    /**
     * Carga la vista de dominios con los contactos y dominios del cliente
     *
     * @param BaseView $view
     * @return void
     */
    private function loadDomainsView(BaseView $view): void
    {
        // Obtener el cliente actual
        $cliente = $this->getActiveCliente();
        if (!$cliente instanceof Cliente) {
            Tools::log()->error('No se pudo cargar el cliente actual');
            return;
        }

        // Configuración de la vista
        $view->setSettings('showBtnNew', false);
        $view->setSettings('showBtnDelete', false);

        // Obtener los contactos y dominios usando el método existente
        $contacts = $this->getDomainContactsOverview();

        // Configurar la vista con los datos
        $view->cursor = $contacts;
        $view->count = count($contacts);

        // Añadir información de dominios próximos a expirar
        $expiringDomains = \FacturaScripts\Plugins\DonDominio\Lib\DomainAlertService::getExpiringDomainsForClient($cliente->codcliente);
        $view->expiring_domains = $expiringDomains;
        $view->expiring_count = count($expiringDomains);

        // Log para debugging
        if (empty($contacts)) {
            $this->consoleLog('No se encontraron contactos para el cliente', [
                'cliente_cod' => $cliente->codcliente,
                'cliente_cifnif' => $cliente->cifnif
            ], 'warn');
        } else {
            $this->consoleLog('Contactos encontrados', [
                'count' => count($contacts),
                'cliente_cod' => $cliente->codcliente
            ], 'info');
        }
    }
    
    /**
     * Establece datos en la sesión temporal.
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    private function setSessionData(string $key, $value): void
    {
        // Usar el método estático de la clase Session
        Session::set('dondominio_' . $key, $value);
    }

    private function createErpView(): void
    {
        $view = $this->addEditView(self::ERP_VIEW, 'ClienteDonDominio', 'dondominio-erp-tab', 'fa-solid fa-plug');
        $this->setSettings(self::ERP_VIEW, 'icon', 'fa-solid fa-plug');
        $this->setSettings(self::ERP_VIEW, 'btnNew', false);
        $this->setSettings(self::ERP_VIEW, 'btnDelete', false);
    }

    

    private function loadErpView(BaseView $view): void
    {
        $mainViewName = $this->getMainViewName();
        if (!isset($this->views[$mainViewName])) {
            return;
        }

        $model = $this->views[$mainViewName]->model;
        if (!$model instanceof Cliente || false === $model->exists()) {
            return;
        }

        // Cargar el modelo extendido ClienteDonDominio
        $erpModel = new ClienteDonDominio();
        $erpModel->loadFromCode($model->primaryColumnValue());

        // Sincronizar el selector de dondominio_id
        $this->syncDonDominioIdSelector($view, $erpModel);

        $view->model = $erpModel;
        $view->cursor = [$erpModel];
        $view->count = 1;
    }

    /**
     * Sincroniza el selector de dondominio_id en la vista ERP
     */
    private function syncDonDominioIdSelector(BaseView $view, ClienteDonDominio $model): void
    {
        $column = $view->columnForField('dondominio_id');
        if (!$column || false === ($column->widget instanceof WidgetSelect)) {
            return;
        }

        if (empty($model->cifnif)) {
            return;
        }

        $contacts = DonDominioContactService::findContactsByTaxNumber($model->cifnif);
        $options = [];
        foreach ($contacts as $contact) {
            $identifier = DonDominioContactService::extractContactIdentifier($contact);
            if (empty($identifier)) {
                continue;
            }
            $options[$identifier] = DonDominioContactService::buildContactLabel($contact);
        }

        if (!empty($model->dondominio_id) && !isset($options[$model->dondominio_id])) {
            $options[$model->dondominio_id] = $model->dondominio_id;
        }

        // Configurar los valores del widget
        $widget = $column->widget;
        $reflection = new \ReflectionClass($widget);
        
        // Intentar con setValuesFromArrayKeys si existe
        if ($reflection->hasMethod('setValuesFromArrayKeys')) {
            $method = $reflection->getMethod('setValuesFromArrayKeys');
            $method->setAccessible(true);
            $method->invoke($widget, $options, false, true);
        } 
        // Si no, intentar con setValuesFromCode
        elseif ($reflection->hasMethod('setValuesFromCode')) {
            $widgetValues = [];
            foreach ($options as $key => $value) {
                $widgetValues[] = ['value' => $key, 'title' => $value];
            }
            $method = $reflection->getMethod('setValuesFromCode');
            $method->setAccessible(true);
            $method->invoke($widget, $widgetValues);
        }
        // Si nada más funciona, intentar asignación directa
        else {
            $widget->values = $options;
        }

        // Auto-seleccionar si hay un solo contacto
        if (empty($model->dondominio_id) && count($options) === 1) {
            $model->dondominio_id = array_key_first($options);
            $model->save();
        }
    }

    private function syncDomainsAction(): int
    {
        $mainViewName = $this->getMainViewName();
        if (!isset($this->views[$mainViewName])) {
            return -1;
        }

        $model = $this->views[$mainViewName]->model;
        if (!$model instanceof Cliente) {
            return -1;
        }

        $primaryKey = $model->primaryColumn();
        if (false === $model->exists()) {
            $code = $this->request->query($primaryKey, $this->request->input($primaryKey));
            if (!empty($code)) {
                $model->load($code);
            }
        }

        if (false === $model->exists()) {
            Tools::log()->warning('record-not-found');
            return -1;
        }

        // Cargar el modelo extendido para acceder a propiedades de DonDominio
        $clienteDonDominio = new ClienteDonDominio();
        $clienteDonDominio->loadFromCode($model->codcliente);

        // Sincronizar el ID de DonDominio antes de sincronizar dominios
        $this->syncDonDominioIdForDomains($clienteDonDominio);

        $contactId = $this->resolveDonDominioId($clienteDonDominio);
        if (empty($contactId)) {
            Tools::log()->warning('dondominio-sync-missing-id', ['%codcliente%' => $clienteDonDominio->codcliente]);
            return 0;
        }

        return DonDominioDomainService::syncDomains($clienteDonDominio, $contactId);
    }

    /**
     * Sincroniza el ID de DonDominio cuando se necesita (sin depender de un widget en la vista principal)
     */
    private function syncDonDominioIdForDomains(ClienteDonDominio $model): void
    {
        if (empty($model->cifnif)) {
            return;
        }

        // Si ya tiene un ID asignado, no hacer nada
        if (!empty($model->dondominio_id)) {
            return;
        }

        // Buscar contactos por número fiscal
        $contacts = DonDominioContactService::findContactsByTaxNumber($model->cifnif);
        
        // Si hay exactamente un contacto, asignarlo automáticamente
        if (count($contacts) === 1) {
            $identifier = DonDominioContactService::extractContactIdentifier($contacts[0]);
            if (!empty($identifier)) {
                $model->dondominio_id = $identifier;
                $model->save();
            }
        }
    }

    private function resolveDonDominioId(ClienteDonDominio $cliente): ?string
    {
        if (!empty($cliente->dondominio_id)) {
            return $cliente->dondominio_id;
        }

        $contacts = DonDominioContactService::findContactsByTaxNumber($cliente->cifnif ?? '');
        if (count($contacts) === 1) {
            return DonDominioContactService::extractContactIdentifier($contacts[0]);
        }

        return null;
    }

    private function toggleAutoRenewAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return true;
        }

        $mainViewName = $this->getMainViewName();
        if (!isset($this->views[$mainViewName])) {
            Tools::log()->warning('record-not-found');
            return true;
        }

        $cliente = $this->views[$mainViewName]->model;
        if (!$cliente instanceof Cliente || false === $cliente->exists()) {
            Tools::log()->warning('dondominio-invalid-customer');
            return true;
        }

        $codes = $this->request->request->getArray('codes');
        if (!empty($codes)) {
            foreach ($codes as $code) {
                // TODO: Implementar la lógica para buscar el dominio
                // Esto es un placeholder - necesitarás adaptarlo a tu estructura de datos actual
                $domain = null;
                
                // Si no se pudo cargar el dominio, continuar con el siguiente
                if (null === $domain) {
                    continue;
                }

                // Verificar que el dominio pertenece al cliente y es de DonDominio
                if ($domain->codcliente !== $cliente->codcliente || $domain->provider !== 'dondominio') {
                    continue;
                }

                $wasEnabled = (bool)$domain->autorenew;
                $enable = !$wasEnabled;
                $error = null;

                if (DonDominioDomainService::setAutoRenew($domain, $enable, $error)) {
                    Tools::log()->notice(
                        $enable ? 'dondominio-domain-autorenew-enabled' : 'dondominio-domain-autorenew-disabled',
                        ['%domain%' => $domain->domain]
                    );
                } else {
                    Tools::log()->error(
                        'dondominio-domain-autorenew-error',
                        [
                            '%domain%' => $domain->domain ?: (string)$code,
                            '%message%' => $error ?? 'unknown',
                        ]
                    );
                }
            }
        } else {
            // Fallback single domain by name
            $domainName = trim((string)$this->request->request->get('domain_name'));
            if ('' === $domainName) {
                Tools::log()->warning('no-selected-item');
                return true;
            }

            // TODO: Implementar la lógica para crear un nuevo dominio
            // Esto es un placeholder - necesitarás adaptarlo a tu estructura de datos actual
            $domain = new ClienteDonDominio();
            $domain->codcliente = $cliente->codcliente;

            $wasEnabled = (bool)$domain->autorenew;
            $enable = !$wasEnabled;
            $error = null;

            if (DonDominioDomainService::setAutoRenew($domain, $enable, $error)) {
                Tools::log()->notice(
                    $enable ? 'dondominio-domain-autorenew-enabled' : 'dondominio-domain-autorenew-disabled',
                    ['%domain%' => $domain->domain]
                );
            } else {
                Tools::log()->error(
                    'dondominio-domain-autorenew-error',
                    [
                        '%domain%' => $domain->domain,
                        '%message%' => $error ?? 'unknown',
                    ]
                );
            }
        }

        return true;
    }

    /**
     * Acción para añadir un dominio específico desde DonDominio
     */
    private function addDomainAction(): bool
    {
        $mainViewName = $this->getMainViewName();
        if (!isset($this->views[$mainViewName])) {
            Tools::log()->warning('record-not-found');
            return true;
        }

        $model = $this->views[$mainViewName]->model;
        if (!$model instanceof Cliente || false === $model->exists()) {
            Tools::log()->warning('dondominio-invalid-customer');
            return true;
        }

        // Si se está enviando el formulario con el dominio seleccionado
        $selectedDomain = $this->request->request->get('selected_domain');
        if (!empty($selectedDomain)) {
            if (false === $this->validateFormToken()) {
                return true;
            }

            // Cargar el modelo extendido
            $clienteDonDominio = new ClienteDonDominio();
            $clienteDonDominio->loadFromCode($model->codcliente);

            $contactId = $this->resolveDonDominioId($clienteDonDominio);
            if (empty($contactId)) {
                Tools::log()->warning('dondominio-sync-missing-id', ['%codcliente%' => $clienteDonDominio->codcliente]);
                return true;
            }

            // Obtener los dominios disponibles para validar
            $availableDomains = DonDominioDomainService::getAvailableDomainsForSelector($clienteDonDominio);
            if (!isset($availableDomains[$selectedDomain])) {
                Tools::log()->warning('dondominio-domain-not-found', ['%domain%' => $selectedDomain]);
                return true;
            }

            $domainOption = $availableDomains[$selectedDomain];
            $domainIdentifier = $domainOption['domain'] ?? '';
            if (empty($domainIdentifier)) {
                $domainIdentifier = (string)$domainOption['id'];
            }

            // Obtener información del dominio desde DonDominio
            $client = \FacturaScripts\Plugins\DonDominio\Lib\DonDominioApiClient::get();
            if (null === $client) {
                Tools::log()->error('dondominio-client-unavailable');
                return true;
            }

            try {
                $response = $client->domain_getinfo($domainIdentifier, ['infoType' => 'status']);
                $domainData = $response instanceof \Dondominio\API\Response\Response ? $response->getResponseData() : $response;

                if (!empty($domainData)) {
                    $baseData = is_array($domainData) ? $domainData : [];
                    $mapped = DonDominioDomainService::mapDomainData(['domain' => $domainIdentifier] + $baseData);
                    if (!empty($domainOption['domain_id']) && empty($mapped['domain_id'])) {
                        $mapped['domain_id'] = $domainOption['domain_id'];
                    }
                    $mapped['provider'] = 'dondominio';
                    $mapped['raw_data'] = $domainData;
                    $mapped['synced_at'] = Tools::dateTime();

                    // TODO: Implementar la lógica para actualizar/insertar el dominio
                    // Esto es un placeholder - necesitarás adaptarlo a tu estructura de datos actual
                    Tools::log()->notice('dondominio-domain-added', ['%domain%' => $domainIdentifier]);
                }
            } catch (\Throwable $exception) {
                Tools::log()->error('dondominio-domain-add-error', [
                    '%domain%' => $domainIdentifier ?: $selectedDomain,
                    '%message%' => $exception->getMessage()
                ]);
            }

            return true;
        }

        // Si no hay dominio seleccionado, mostrar el modal con el selector
        // Esto se renderizará en la vista
        return false;
    }

    /**
     * Obtiene los dominios disponibles para el selector basado en el DNI del cliente
     */
    public function getAvailableDomains(): array
    {
        $mainViewName = $this->getMainViewName();
        if (!isset($this->views[$mainViewName])) {
            return [];
        }

        $model = $this->views[$mainViewName]->model;
        if (!$model instanceof Cliente || false === $model->exists()) {
            return [];
        }

        // Cargar el modelo extendido
        $clienteDonDominio = new ClienteDonDominio();
        $clienteDonDominio->loadFromCode($model->codcliente);

        return DonDominioDomainService::getAvailableDomainsForSelector($clienteDonDominio, $this->getSelectedDomainContactId());
    }

    public function getDomainContactOptions(): array
    {
        $cliente = $this->getActiveCliente();
        if (!$cliente instanceof Cliente) {
            return [];
        }

        $clienteDonDominio = new ClienteDonDominio();
        $clienteDonDominio->loadFromCode($cliente->codcliente);

        return $this->resolveContactOptions($clienteDonDominio);
    }

    public function getSelectedDomainContactId(): ?string
    {
        $cliente = $this->getActiveCliente();
        if (!$cliente instanceof Cliente) {
            return null;
        }

        $selected = $this->getStoredDomains($cliente->codcliente);
        if (!empty($selected)) {
            return $selected;
        }

        $clienteDonDominio = new ClienteDonDominio();
        $clienteDonDominio->loadFromCode($cliente->codcliente);

        return $clienteDonDominio->dondominio_id ?: null;
    }

    /**
     * Obtiene una visión general de los contactos de DonDominio con sus dominios.
     * 
     * @return array
     */
    /**
     * Normaliza un identificador fiscal eliminando caracteres no alfanuméricos
     */
    private function normalizeTaxNumber(string $taxNumber): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $taxNumber));
    }
    
    /**
     * Normaliza nameservers de la API a un array de strings
     */
    private function normalizeNameservers($data): array
    {
        if (!is_array($data)) {
            return [];
        }
        
        // Intentar extraer nameservers de diferentes estructuras posibles
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
                // Si es un string directamente, añadirlo
                if (is_string($item)) {
                    $item = trim($item);
                    if ('' !== $item) {
                        $result[] = $item;
                    }
                    continue;
                }
                
                // Si es un array con información del nameserver
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

    /**
     * Obtiene los contactos de DonDominio y sus dominios asociados para el cliente actual
     *
     * @return array Array con la estructura [['id' => string, 'label' => string, 'domains' => array]]
     */
    public function getDomainContactsOverview(): array
    {
        $result = [];

        try {
            $cliente = $this->getActiveCliente();

            if (!$cliente instanceof Cliente || empty($cliente->cifnif)) {
                return $result;
            }

            $client = DonDominioApiClient::get();
            if (null === $client) {
                return $result;
            }

            $targetTaxNumber = $this->normalizeTaxNumber($cliente->cifnif);

            // 1. Obtener contactos por DNI/CIF
            $contacts = [];

            try {
                // Primero intentar con el DNI/CIF normalizado
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
                        return $result;
                    }
                }

                $contacts = $responseData['contacts'];

            } catch (\Exception $e) {
                $this->consoleLog('Error obteniendo contactos', ['error' => $e->getMessage()], 'error');
                return $result;
            }

            // 2. Para cada contacto, obtener sus dominios usando el contactID como owner
            foreach ($contacts as $contact) {
                $contactId = $contact['contactID'] ?? null;
                if (empty($contactId)) {
                    continue;
                }

                $domains = [];

                // Obtener dominios asociados a este contacto usando 'owner' como parámetro
                try {
                    $domainsResponse = $client->domain_list([
                        'owner' => $contactId,
                        'pageLength' => 100,
                        'infoType' => 'status'
                    ]);

                    // Extraer dominios de la respuesta usando el método correcto del SDK v2.x
                    $responseData = $domainsResponse instanceof \Dondominio\API\Response\Response ? $domainsResponse->getResponseData() : $domainsResponse;

                    $this->consoleLog('Respuesta de dominios para contacto ' . $contactId, [
                        'contact_id' => $contactId,
                        'response_type' => gettype($domainsResponse),
                        'data' => $responseData
                    ]);

                    // Extraer lista de dominios usando el método correcto
                    $domainsList = [];
                    if (is_array($responseData)) {
                        $domainsList = $responseData['domains'] ?? ($responseData['list'] ?? ($responseData['items'] ?? []));
                    }

                    if (!empty($domainsList) && is_array($domainsList)) {
                        $this->consoleLog('Procesando ' . count($domainsList) . ' dominios para contacto ' . $contactId);

                        foreach ($domainsList as $domain) {
                            try {
                                $domainName = $domain['name'] ?? ($domain['domain'] ?? '');
                                if (empty($domainName)) {
                                    $this->consoleLog('Dominio sin nombre', ['domain_data' => $domain], 'warn');
                                    continue;
                                }

                                // Obtener información detallada del dominio (status + nameservers)
                                $detailedInfo = [];
                                $nameservers = [];
                                try {
                                    // Obtener info de estado
                                    $statusResponse = $client->domain_getinfo($domainName, ['infoType' => 'status']);
                                    if ($statusResponse instanceof \Dondominio\API\Response\Response) {
                                        $detailedInfo = $statusResponse->getResponseData() ?? [];
                                    }

                                    // Obtener nameservers
                                    $nsResponse = $client->domain_getnameservers($domainName);
                                    if ($nsResponse instanceof \Dondominio\API\Response\Response) {
                                        $nsData = $nsResponse->getResponseData();
                                        // Normalizar nameservers a array de strings
                                        $nameservers = $this->normalizeNameservers($nsData);
                                    }
                                } catch (\Exception $e) {
                                    $this->consoleLog('Error obteniendo detalles de ' . $domainName, ['error' => $e->getMessage()], 'warn');
                                }

                                // Normalizar fecha de expiración
                                $expiration = null;
                                if (!empty($domain['tsExpir'])) {
                                    $expiration = is_numeric($domain['tsExpir'])
                                        ? date('Y-m-d H:i:s', $domain['tsExpir'])
                                        : $domain['tsExpir'];
                                }

                                // Normalizar fecha de creación
                                $created = null;
                                if (!empty($domain['tsCreate'])) {
                                    $created = is_numeric($domain['tsCreate'])
                                        ? date('Y-m-d H:i:s', $domain['tsCreate'])
                                        : $domain['tsCreate'];
                                }

                                // Añadir dominio con información completa
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

                                $this->consoleLog('Dominio añadido: ' . $domainName, ['nameservers' => count($nameservers)]);

                            } catch (\Exception $e) {
                                $this->consoleLog('Error procesando dominio', [
                                    'error' => $e->getMessage(),
                                    'domain' => $domain
                                ], 'error');
                            }
                        }
                    } else {
                        $this->consoleLog('No se encontraron dominios para el contacto', [
                            'contact_id' => $contactId,
                            'domains_list_type' => gettype($domainsList),
                            'domains_list' => $domainsList
                        ], 'warn');
                    }
                } catch (\Exception $e) {
                    $this->consoleLog('Error al listar dominios para el contacto ' . $contactId, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], 'error');
                }

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
        } catch (\Exception $e) {
            Tools::log()->error('Error al obtener contactos de DonDominio: ' . $e->getMessage());
        }

        return $result;
    }

    private function downloadAutoRenewContractAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return true;
        }

        $domain = $this->findSelectedDomain();
        if (!$domain instanceof ClienteDonDominio) {
            Tools::log()->warning('record-not-found');
            return true;
        }

        if ($domain->autorenew) {
            Tools::log()->notice('dondominio-domain-autorenew-enabled', ['%domain%' => $domain->domain]);
            return true;
        }

        $this->redirect(self::AUTORENEW_CONTRACT_URL);
        $this->setTemplate(false);

        return false;
    }

    private function getDomainAuthcodeAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        $domain = $this->findSelectedDomain();
        if (!$domain instanceof ClienteDonDominio) {
            Tools::log()->warning('record-not-found');
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('record-not-found')]);
        }

        $lockedError = null;
        $locked = DonDominioDomainService::getTransferLock($domain, $lockedError);
        if (true === $locked) {
            Tools::log()->warning('dondominio-authcode-locked', ['%domain%' => $domain->domain]);
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('dondominio-authcode-locked')]);
        }

        $error = null;
        $code = DonDominioDomainService::getAuthCode($domain, $error);
        if (null === $code) {
            $message = Tools::lang()->trans('dondominio-domain-authcode-error', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? 'unknown',
            ]);
            Tools::log()->error('dondominio-domain-authcode-error', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? 'unknown',
            ]);
            return $this->jsonResponse(['success' => false, 'message' => $message]);
        }

        Tools::log()->notice('dondominio-domain-authcode', [
            '%domain%' => $domain->domain,
            '%authcode%' => $code,
        ]);

        return $this->jsonResponse([
            'success' => true,
            'code' => $code,
            'domain' => $domain->domain,
        ]);
    }

    private function getDomainTransferLockAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        $domain = $this->findSelectedDomain();
        if (!$domain instanceof ClienteDonDominio) {
            Tools::log()->warning('record-not-found');
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('record-not-found')]);
        }

        $error = null;
        $enabled = DonDominioDomainService::getTransferLock($domain, $error);
        if (null === $enabled) {
            $message = Tools::lang()->trans('dondominio-domain-transfer-lock-unknown', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
            ]);
            if (!empty($error)) {
                $message .= ' (' . $error . ')';
            }

            Tools::log()->warning('dondominio-domain-transfer-lock-unknown', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? '',
            ]);

            return $this->jsonResponse(['success' => false, 'message' => $message]);
        }

        return $this->jsonResponse([
            'success' => true,
            'enabled' => $enabled,
            'domain' => $domain->domain,
            'enabledMessage' => Tools::lang()->trans('dondominio-transfer-lock-enabled-label'),
            'disabledMessage' => Tools::lang()->trans('dondominio-transfer-lock-disabled-label'),
        ]);
    }

    private function updateDomainTransferLockAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        $domain = $this->findSelectedDomain();
        if (!$domain instanceof ClienteDonDominio) {
            Tools::log()->warning('record-not-found');
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('record-not-found')]);
        }

        $lockValue = $this->request->request->get('lock', $this->request->request->get('enabled'));
        if (null === $lockValue) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        $enable = filter_var($lockValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (null === $enable) {
            $enable = in_array((string)$lockValue, ['1', 'on'], true);
        }

        $error = null;
        if (false === DonDominioDomainService::setTransferLock($domain, (bool)$enable, $error)) {
            $message = Tools::lang()->trans('dondominio-domain-transfer-lock-error', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? 'unknown',
            ]);
            Tools::log()->error('dondominio-domain-transfer-lock-error', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? 'unknown',
            ]);
            return $this->jsonResponse(['success' => false, 'message' => $message]);
        }

        $messageKey = $enable
            ? 'dondominio-domain-transfer-lock-enabled'
            : 'dondominio-domain-transfer-lock-disabled';
        $message = Tools::lang()->trans($messageKey, ['%domain%' => $domain->domain]);

        Tools::log()->notice($messageKey, ['%domain%' => $domain->domain]);

        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'domain' => $domain->domain,
            'enabled' => (bool)$enable,
            'enabledLabel' => Tools::lang()->trans('dondominio-transfer-lock-enabled-label'),
            'disabledLabel' => Tools::lang()->trans('dondominio-transfer-lock-disabled-label'),
        ]);
    }

    private function getDomainNameserversAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        $domain = $this->resolveDomainForDnsRequest();
        if (!$domain instanceof ClienteDonDominio) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('record-not-found')]);
        }

        $error = null;
        $nameservers = DonDominioDomainService::getNameservers($domain, $error);
        if (null !== $error && '' !== $error) {
            return $this->jsonResponse([
                'success' => false,
                'message' => Tools::lang()->trans('dondominio-domain-dns-error', [
                    '%domain%' => $domain->domain ?: (string)$domain->id,
                    '%message%' => $error,
                ]),
            ]);
        }

        return $this->jsonResponse([
            'success' => true,
            'nameservers' => $nameservers,
            'domain' => $domain->domain ?: $domain->domain_id,
        ]);
    }

    private function updateDomainNameserversAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        $domain = $this->resolveDomainForDnsRequest();
        if (!$domain instanceof ClienteDonDominio) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('record-not-found')]);
        }

        $nameservers = $this->request->request->getArray('nameservers');

        $nameservers = array_values(array_filter(array_map(static function ($value) {
            return is_string($value) ? trim($value) : '';
        }, $nameservers)));

        if (count($nameservers) < 2) {
            return $this->jsonResponse([
                'success' => false,
                'message' => Tools::lang()->trans('dondominio-dns-error-too-few'),
            ]);
        }

        $error = null;
        if (false === DonDominioDomainService::updateNameservers($domain, $nameservers, $error)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => Tools::lang()->trans('dondominio-domain-dns-error', [
                    '%domain%' => $domain->domain ?: (string)$domain->id,
                    '%message%' => $error ?? 'unknown',
                ]),
            ]);
        }

        $message = Tools::lang()->trans('dondominio-domain-dns-updated', [
            '%domain%' => $domain->domain ?: (string)$domain->id,
        ]);

        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'nameservers' => $nameservers,
        ]);
    }

    private function jsonResponse(array $payload): bool
    {
        $this->setTemplate(false);
        $this->response->json($payload);
        return false;
    }

    private function findSelectedDomain(): ?ClienteDonDominio
    {
        // Prefer local identifier when present
        $domainId = $this->resolveDomainIdFromRequest();
        if ($domainId > 0) {
            $domain = ClienteDonDominio::findWhere(['id' => $domainId]);
            if ($domain instanceof ClienteDonDominio) {
                $cliente = $this->getActiveCliente();
                if ($cliente instanceof Cliente && $domain->codcliente === $cliente->codcliente && $domain->provider === 'dondominio') {
                    return $domain;
                }
                return null;
            }
        }

        // Fallback: build a transient domain from posted domain_name
        $domainName = trim((string)$this->request->request->get('domain_name'));
        if ('' === $domainName) {
            return null;
        }

        $cliente = $this->getActiveCliente();
        if (!$cliente instanceof Cliente) {
            return null;
        }

        $domain = new ClienteDonDominio();
        $domain->codcliente = $cliente->codcliente;
        $domain->provider = 'dondominio';
        $domain->domain = $domainName;
        return $domain;
    }

    private function resolveDomainIdFromRequest(): int
    {
        $domainId = (int)$this->request->request->get('code');
        if ($domainId > 0) {
            return $domainId;
        }

        $domainId = (int)$this->request->request->get('domainId');
        if ($domainId > 0) {
            return $domainId;
        }

        return (int)$this->request->query->get('code', 0);
    }

    private function getActiveCliente(): ?Cliente
    {
        $mainViewName = $this->getMainViewName();
        if (!isset($this->views[$mainViewName])) {
            return null;
        }

        $model = $this->views[$mainViewName]->model;
        if ($model instanceof Cliente && $model->exists()) {
            return $model;
        }

        return null;
    }

    private function setDomainsAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return true;
        }

        $cliente = $this->getActiveCliente();
        if (!$cliente instanceof Cliente) {
            Tools::log()->warning('dondominio-invalid-customer');
            return true;
        }

        $clienteDonDominio = new ClienteDonDominio();
        $clienteDonDominio->loadFromCode($cliente->codcliente);

        $contactId = trim((string)$this->request->request->get('domain_contact_id', ''));
        $options = $this->resolveContactOptions($clienteDonDominio);
        if (!empty($contactId) && !isset($options[$contactId])) {
            Tools::log()->warning('dondominio-contact-not-found', ['%contact%' => $contactId]);
            return true;
        }

        $this->storeDomains($clienteDonDominio->codcliente, $contactId);

        $clienteDonDominio->dondominio_id = $contactId ?: null;
        $clienteDonDominio->save();

        if (!empty($contactId)) {
            DonDominioDomainService::syncDomains($clienteDonDominio, $contactId);
        } else {
            $fallback = $this->resolveDonDominioId($clienteDonDominio);
            if (!empty($fallback)) {
                DonDominioDomainService::syncDomains($clienteDonDominio, $fallback);
            }
        }

        return true;
    }

    private function resolveDomainForDnsRequest(): ?ClienteDonDominio
    {
        $domainId = (int)$this->request->request->get('domain_id', 0);
        if ($domainId <= 0) {
            $domainId = (int)$this->request->request->get('code', 0);
        }

        if ($domainId > 0) {
            $domain = ClienteDonDominio::findWhere(['id' => $domainId]);
            if ($domain instanceof ClienteDonDominio) {
                return $domain;
            }
        }

        $domainName = trim((string)$this->request->request->get('domain_name'));
        if ('' === $domainName) {
            return null;
        }

        $cliente = $this->getActiveCliente();
        if (!$cliente instanceof Cliente) {
            return null;
        }

        $domain = new ClienteDonDominio();
        $domain->codcliente = $cliente->codcliente;
        $domain->provider = 'dondominio';
        $domain->domain = $domainName;

        return $domain;
    }

    private function resolveContactOptions(ClienteDonDominio $cliente): array
    {
        $codcliente = $cliente->codcliente ?? '';
        if (isset($this->contactOptionsCache[$codcliente])) {
            return $this->contactOptionsCache[$codcliente];
        }

        if (empty($cliente->cifnif)) {
            return [];
        }

        $contacts = DonDominioContactService::findContactsByTaxNumber($cliente->cifnif);
        $options = [];
        foreach ($contacts as $contact) {
            $identifier = DonDominioContactService::extractContactIdentifier($contact);
            if (empty($identifier)) {
                continue;
            }
            $options[$identifier] = DonDominioContactService::buildContactLabel($contact);
        }

        $this->contactOptionsCache[$codcliente] = $options;
        return $options;
    }

    private function storeDomains(string $codcliente, ?string $contactId): void
    {
        $key = $this->getContactFilterSessionKey($codcliente);
        if (empty($contactId)) {
            Session::set($key, null);
            return;
        }

        Session::set($key, $contactId);
    }

    private function getStoredDomains(string $codcliente): ?string
    {
        $key = $this->getContactFilterSessionKey($codcliente);
        $value = Session::get($key);

        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return $value;
    }

    private function getContactFilterSessionKey(string $codcliente): string
    {
        return 'dondominio_domain_contact_' . $codcliente;
    }

    /**
     * Acción para obtener el conteo de dominios próximos a expirar
     */
    private function getExpiringDomainsCountAction(): bool
    {
        $count = \FacturaScripts\Plugins\DonDominio\Lib\DomainAlertService::getExpiringDomainsCount();

        $this->setTemplate(false);
        $this->response->json([
            'count' => $count,
            'message' => $count > 0 ? $count . ' dominio(s) expira(n) en menos de 30 días' : 'No hay dominios próximos a expirar'
        ]);

        return false;
    }

    /**
     * Acción para validar tokens de autologin
     */
    private function validateAutologinTokenAction(): bool
    {
        $token = $this->request->request->get('token');
        $clientCode = $this->request->request->get('client_code');
        $service = $this->request->request->get('service');

        if (empty($token) || empty($clientCode) || empty($service)) {
            return $this->jsonResponse(['valid' => false, 'message' => 'Parámetros incompletos']);
        }

        $valid = \FacturaScripts\Plugins\DonDominio\Lib\AutoLoginService::validateToken($token, $clientCode, $service);

        if ($valid) {
            // Obtener información adicional del cliente para el servicio
            $cliente = new \FacturaScripts\Plugins\DonDominio\Model\ClienteDonDominio();
            $cliente->loadFromCode($clientCode);

            $userInfo = [];
            switch ($service) {
                case 'mail':
                    $userInfo = ['username' => $cliente->mail_username];
                    break;
                case 'web':
                    $userInfo = ['username' => $cliente->web_username];
                    break;
                case 'erp':
                    $userInfo = ['username' => $cliente->erp_username];
                    break;
            }

            return $this->jsonResponse([
                'valid' => true,
                'client_code' => $clientCode,
                'service' => $service,
                'user_info' => $userInfo
            ]);
        }

        return $this->jsonResponse(['valid' => false, 'message' => 'Token inválido o expirado']);
    }

    /**
     * Acción para autologin al ERP
     */
    private function erpAutoLoginAction(): bool
    {
        $clientCode = $this->request->request->get('client_code');
        $token = $this->request->request->get('token');

        if (empty($clientCode) || empty($token)) {
            Tools::log()->error('Parámetros incompletos para autologin ERP');
            return $this->jsonResponse(['success' => false, 'message' => 'Parámetros incompletos']);
        }

        // Validar el token
        $valid = \FacturaScripts\Plugins\DonDominio\Lib\AutoLoginService::validateToken($token, $clientCode, 'erp');
        if (!$valid) {
            Tools::log()->error('Token inválido para autologin ERP', ['client_code' => $clientCode]);
            return $this->jsonResponse(['success' => false, 'message' => 'Token inválido']);
        }

        // Obtener el cliente
        $cliente = new \FacturaScripts\Plugins\DonDominio\Model\ClienteDonDominio();
        $cliente->loadFromCode($clientCode);

        if (!$cliente->exists() || empty($cliente->erp_access_url) || empty($cliente->erp_username)) {
            Tools::log()->error('Cliente no encontrado o configuración ERP incompleta', ['client_code' => $clientCode]);
            return $this->jsonResponse(['success' => false, 'message' => 'Configuración incompleta']);
        }

        // Generar URL de autologin para FacturaScript
        $erpUrl = \FacturaScripts\Plugins\DonDominio\Lib\AutoLoginService::generateFacturaScriptAutoLoginUrl($cliente);

        if ($erpUrl) {
            // Redirigir al ERP
            $this->redirect($erpUrl);
            $this->setTemplate(false);
            return false;
        }

        Tools::log()->error('No se pudo generar URL de autologin para ERP', ['client_code' => $clientCode]);
        return $this->jsonResponse(['success' => false, 'message' => 'Error generando URL de acceso']);
    }

    /**
     * @param array<string,ClienteDonDominio> $localMap
     */
    private function buildContactDomainEntries(ClienteDonDominio $cliente, string $contactId, array $localMap): array
    {
        $domains = DonDominioDomainService::getAvailableDomainsForSelector($cliente, $contactId);
        if (empty($domains)) {
            return [];
        }

        $result = [];
        foreach ($domains as $identifier => $option) {
            $domainName = $option['domain'] ?? ($option['id'] ?? $identifier);
            $key = strtolower((string)$domainName);
            $local = $localMap[$key] ?? null;
            $result[] = [
                'identifier' => $identifier,
                'domain' => $domainName,
                'label' => $option['label'] ?? $domainName,
                'status' => $option['status'] ?? '',
                'expires_at' => $option['expires_at'] ?? '',
                'local_id' => $local ? $local->id : null,
                'local_autorenew' => $local ? (bool)$local->autorenew : null,
            ];
        }

        return $result;
    }

    /**
     * @return array<string,ClienteDonDominio>
     */
    private function mapLocalDomains(string $codcliente): array
    {
        $where = [
            new DataBaseWhere('codcliente', $codcliente),
            new DataBaseWhere('provider', 'dondominio'),
        ];

        $localDomains = ClienteDonDominio::all($where);
        $map = [];
        foreach ($localDomains as $domain) {
            if (!$domain instanceof ClienteDonDominio) {
                continue;
            }
            $map[strtolower((string)$domain->domain)] = $domain;
        }

        return $map;
    }

    public function getModelClassName(): string
    {
        // Devolver 'Cliente' para la vista principal para que use el XML estándar
        // El modelo extendido se carga manualmente cuando es necesario
        return 'Cliente';
    }
}
