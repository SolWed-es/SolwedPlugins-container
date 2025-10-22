<?php
namespace FacturaScripts\Plugins\Vehiculos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controlador de edición de vehículos optimizado.
 * Proporciona una interfaz limpia y funcional para gestionar vehículos.
 */
class EditVehiculo extends EditController
{
    /**
     * Retorna el nombre del modelo que gestiona este controlador.
     */
    public function getModelClassName(): string
    {
        return 'Vehiculo';
    }

    /**
     * Configuración de la página para el menú y navegación.
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'vehiculo';
        $data['icon'] = 'fa-solid fa-car';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Crea las vistas del controlador.
     */
    protected function createViews(): void
    {
        parent::createViews();
        $this->setTabsPosition('top');

        // VISTA 1: Matrícula con botón API (PRIMERA - Para crear desde matrícula)
        $this->addEditView('EditVehiculoMatricula', 'Vehiculo', 'crear-matricula', 'fa-solid fa-id-card');
        $this->views['EditVehiculoMatricula']->title = 'Crear desde Matrícula';

        // Deshabilitar botones de guardar, deshacer y eliminar en la vista de Matrícula
        $this->setSettings('EditVehiculoMatricula', 'btnSave', false);
        $this->setSettings('EditVehiculoMatricula', 'btnUndo', false);
        $this->setSettings('EditVehiculoMatricula', 'btnDelete', false);

        // Agregar botón de API a la vista de Matrícula
        $this->addButton('EditVehiculoMatricula', [
            'action' => 'get-vehicle-data',
            'icon' => 'fa-solid fa-download',
            'label' => 'Obtener datos API',
            'type' => 'action',
            'color' => 'success'
        ]);

        // VISTA 2: Todos los datos del vehículo (SEGUNDA - Después de crear)
        $this->addEditView('EditVehiculo', 'Vehiculo', 'datos-vehiculo', 'fa-solid fa-car');
        $this->views['EditVehiculo']->title = 'Datos del Vehículo';

        // Deshabilitar botón de eliminar en la vista principal
        $this->setSettings('EditVehiculo', 'btnDelete', false);

        // Determinar qué pestaña activar
        $activetab = $this->request->query->get('activetab', '');
        $code = $this->request->query->get('code', '');

        // Si viene activetab en la URL, usarlo
        if (!empty($activetab)) {
            $this->active = $activetab;
        }
        // Si es un vehículo nuevo (sin code), establecer la pestaña de matrícula como activa
        elseif (empty($code)) {
            $this->active = 'EditVehiculoMatricula';
        }
        // Si hay code pero no hay activetab, dejar que el padre decida (EditVehiculo por defecto)
    }

    /**
     * Procesamiento de acciones.
     */
    protected function execPreviousAction($action)
    {
        try {
            // Interceptar INSERT desde pestaña Matricula y ejecutar API
            if ($action === 'insert') {
                $activetab = $this->request->request->get('activetab', '');
                if ($activetab === 'EditVehiculoMatricula') {
                    return $this->getVehicleDataFromAPI();
                }
            }

            // Verificar si se ha pulsado el botón de API
            $postData = $this->request->request->all();
            foreach ($postData as $key => $value) {
                if (strpos($key, 'get-vehicle-data') !== false) {
                    $action = 'get-vehicle-data';
                    break;
                }
            }

            // Acción personalizada para obtener datos de API
            if ($action === 'get-vehicle-data') {
                return $this->getVehicleDataFromAPI();
            }

            $result = parent::execPreviousAction($action);

            // Mensajes de éxito/error
            if ($action === 'save') {
                if ($result) {
                    $this->toolBox()->i18nLog()->notice('vehicle-saved');
                } else {
                    $this->toolBox()->i18nLog()->error('vehicle-save-error');
                }
            } elseif ($action === 'delete') {
                if ($result) {
                    $this->toolBox()->i18nLog()->notice('vehicle-deleted');
                } else {
                    $this->toolBox()->i18nLog()->error('vehicle-delete-error');
                }
            }

            return $result;
        } catch (\Throwable $e) {
            $this->toolBox()->i18nLog()->error('Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Carga datos adicionales del cliente para mejorar la UX.
     * Muestra información del cliente en campos de solo lectura.
     * También carga datos de la API desde la sesión si existen.
     */
    protected function loadData($viewName, $view)
    {
        parent::loadData($viewName, $view);

        $model = $view->model;

        // Cargar datos de la API desde la sesión si existen (solo para vehículos nuevos)
        if ($viewName === 'EditVehiculo' && empty($model->primaryColumnValue())) {
            if (!isset($_SESSION)) {
                session_start();
            }

            if (isset($_SESSION['vehiculo_api_data']) && is_array($_SESSION['vehiculo_api_data'])) {
                $apiData = $_SESSION['vehiculo_api_data'];

                // Rellenar el modelo con los datos de la API
                foreach ($apiData as $field => $value) {
                    if (property_exists($model, $field)) {
                        $model->$field = $value;
                    }
                }

                // Limpiar la sesión después de cargar los datos
                unset($_SESSION['vehiculo_api_data']);

                $this->toolBox()->i18nLog()->info('api-data-loaded');
            }
        }

        if ($viewName !== 'EditVehiculo') {
            return;
        }

        // Si no hay cliente asignado, no hay nada que cargar
        if (empty($model->codcliente)) {
            $this->clearCustomerFields($model);
            return;
        }

        // Cargar información del cliente
        $this->loadCustomerInfo($model);
    }

    /**
     * Limpia los campos de información del cliente.
     */
    private function clearCustomerFields($model): void
    {
        $model->cliente_nombre = '';
        $model->cliente_cifnif = '';
        $model->cliente_email = '';
        $model->cliente_telefono = '';
        $model->cliente_direccion = '';
        $model->cliente_poblacion = '';
        $model->cliente_provincia = '';
    }

    /**
     * Carga la información del cliente en el modelo.
     */
    private function loadCustomerInfo($model): void
    {
        $cliente = new \FacturaScripts\Dinamic\Model\Cliente();
        
        if (!$cliente->loadFromCode($model->codcliente)) {
            $this->clearCustomerFields($model);
            return;
        }

        // Datos básicos del cliente
        $model->cliente_nombre = $cliente->nombre ?? '';
        $model->cliente_cifnif = $cliente->cifnif ?? '';
        $model->cliente_email = $cliente->email ?? '';
        $model->cliente_telefono = $cliente->telefono1 ?: ($cliente->telefono2 ?? '');

        // Dirección del cliente
        $this->loadCustomerAddress($model, $cliente);
    }

    /**
     * Carga la dirección del cliente.
     */
    private function loadCustomerAddress($model, $cliente): void
    {
        try {
            $direccion = $cliente->getDefaultAddress();
            
            if ($direccion) {
                $model->cliente_direccion = trim(($direccion->direccion ?? '') . ' ' . ($direccion->codpostal ?? ''));
                $model->cliente_poblacion = $direccion->ciudad ?? '';
                $model->cliente_provincia = $direccion->provincia ?? '';
            } else {
                $model->cliente_direccion = '';
                $model->cliente_poblacion = '';
                $model->cliente_provincia = '';
            }
        } catch (\Throwable $e) {
            // En caso de error, dejar campos vacíos
            $model->cliente_direccion = '';
            $model->cliente_poblacion = '';
            $model->cliente_provincia = '';
        }
    }

    /**
     * Obtiene datos del vehículo desde la API y los guarda en sesión.
     * Luego cambia a la pestaña "Datos del Vehículo" para que el usuario revise y guarde.
     */
    private function getVehicleDataFromAPI(): bool
    {
        $matricula = $this->request->request->get('matricula', '');

        if (empty($matricula)) {
            $this->toolBox()->i18nLog()->warning('enter-license-plate');
            return false;
        }

        // Limpiar matrícula
        $matricula = strtoupper(trim(str_replace([' ', '-'], '', $matricula)));

        try {
            // Configurar la llamada a la API
            $apiUrl = 'https://api-license-plate-spain.p.rapidapi.com/es?plate=' . urlencode($matricula);

            $headers = [
                'x-rapidapi-host: api-license-plate-spain.p.rapidapi.com',
                'x-rapidapi-key: 34ce5bd069mshfdf966fb464a1cdp1f09dbjsn496339bf2821'
            ];

            // Realizar la llamada
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $this->toolBox()->i18nLog()->error('api-connection-error: ' . $curlError);
                return false;
            }

            if ($httpCode === 404) {
                $this->toolBox()->i18nLog()->warning('license-plate-not-found: ' . $matricula);
                return false;
            }

            if ($httpCode !== 200) {
                $this->toolBox()->i18nLog()->warning('api-error (HTTP ' . $httpCode . ')');
                return false;
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->toolBox()->i18nLog()->error('api-response-error');
                return false;
            }

            // Crear un nuevo vehículo temporal con los datos de la API
            $vehiculo = new \FacturaScripts\Plugins\Vehiculos\Model\Vehiculo();
            $vehiculo->matricula = $matricula;

            // Preservar codcliente si ya existe en el POST
            $codcliente = $this->request->request->get('codcliente', '');
            if (!empty($codcliente)) {
                $vehiculo->codcliente = $codcliente;
            }

            // Rellenar con datos de la API
            $this->fillVehicleDataFromAPI($vehiculo, $data, $matricula);

            // Guardar el vehículo temporal en la sesión
            if (!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['vehiculo_api_data'] = [
                'matricula' => $vehiculo->matricula,
                'marca' => $vehiculo->marca,
                'modelo' => $vehiculo->modelo,
                'bastidor' => $vehiculo->bastidor,
                'numserie' => $vehiculo->numserie,
                'idmarca_api' => $vehiculo->idmarca_api,
                'kilometros' => $vehiculo->kilometros,
                'fecha_primera_matriculacion' => $vehiculo->fecha_primera_matriculacion,
                'procedencia_matricula' => $vehiculo->procedencia_matricula,
                'carroceria' => $vehiculo->carroceria,
                'motor' => $vehiculo->motor,
                'potencia' => $vehiculo->potencia,
                'id_marca_tecdoc' => $vehiculo->id_marca_tecdoc,
                'id_modelo_tecdoc' => $vehiculo->id_modelo_tecdoc,
                'id_ktype' => $vehiculo->id_ktype,
                'descripcion' => $vehiculo->descripcion,
                'codcliente' => $vehiculo->codcliente
            ];

            $this->toolBox()->i18nLog()->notice('api-data-success');

            // Redirigir a la pestaña de datos del vehículo
            $this->redirect($this->url() . '?activetab=EditVehiculo');

            return false;

        } catch (\Throwable $e) {
            $this->toolBox()->i18nLog()->error('Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Rellena los datos del vehículo desde la respuesta de la API.
     */
    private function fillVehicleDataFromAPI($vehiculo, array $data, string $matricula): void
    {
        // Asignar matrícula
        $vehiculo->matricula = $matricula;

        // La API devuelve un array con un objeto dentro
        if (isset($data[0])) {
            $data = $data[0];
        }

        // ===== CAMPOS BÁSICOS =====
        if (isset($data['MARCA'])) {
            $vehiculo->marca = $this->cleanString($data['MARCA']);
        }

        if (isset($data['MODELO'])) {
            $vehiculo->modelo = $this->cleanString($data['MODELO']);
        }

        if (isset($data['VIN'])) {
            $vehiculo->bastidor = $this->cleanString($data['VIN']);
        }

        // ===== CARROCERIA (Opción A: "Coupé - Tracción delantera") =====
        $carroceriaParts = [];
        if (isset($data['CARROCERIA'])) {
            $carroceriaParts[] = $data['CARROCERIA'];
        }
        if (isset($data['TRACCION'])) {
            $carroceriaParts[] = $data['TRACCION'];
        }
        if (!empty($carroceriaParts)) {
            $vehiculo->carroceria = $this->cleanString(implode(' - ', $carroceriaParts));
        }

        // ===== MOTOR (Opción C: "CAWB Motor Otto Inyección directa" - sin cilindrada) =====
        $motorParts = [];
        if (isset($data['MOTOR'])) {
            $motorParts[] = $data['MOTOR'];
        }
        if (isset($data['TYMOTOR'])) {
            $motorParts[] = $data['TYMOTOR'];
        }
        if (isset($data['INYECCION'])) {
            $motorParts[] = $data['INYECCION'];
        }
        if (!empty($motorParts)) {
            $vehiculo->motor = $this->cleanString(implode(' ', $motorParts));
        }

        // ===== IDMODELO va a numserie (código del modelo) =====
        if (isset($data['IDMODELO'])) {
            $vehiculo->numserie = $this->cleanString($data['IDMODELO']);
        }

        // ===== KILOMETROS (si viene en la API) =====
        if (isset($data['KILOMETROS']) || isset($data['kilometros'])) {
            $vehiculo->kilometros = (int) ($data['KILOMETROS'] ?? $data['kilometros'] ?? 0);
        }

        // ===== POTENCIA (Formato: "2.0 TFSI 200CV") =====
        if (isset($data['KWs']) && isset($data['TPMOTOR'])) {
            $kw = (float) $data['KWs'];
            $cv = (int) round($kw * 1.35962);

            // Potencia completa: "2.0 TFSI 200CV"
            $vehiculo->potencia = $this->cleanString($data['TPMOTOR']) . ' ' . $cv . 'CV';
        }

        // ===== FECHAS =====
        if (isset($data['FECHA_MATRICULACION'])) {
            $vehiculo->fecha_primera_matriculacion = $this->parseDate($data['FECHA_MATRICULACION']);
        } elseif (isset($data['fecha_matriculacion'])) {
            $vehiculo->fecha_primera_matriculacion = $this->parseDate($data['fecha_matriculacion']);
        }

        // ===== PAÍS DE MATRICULACIÓN =====
        if (isset($data['PAIS'])) {
            $vehiculo->procedencia_matricula = $this->cleanString($data['PAIS']);
        }

        // ===== CAMPOS TECDOC (Opción A: Guardar todos) =====
        if (isset($data['IDMARCA'])) {
            $vehiculo->idmarca_api = $this->cleanString($data['IDMARCA']);
        }

        if (isset($data['ID_MARCA_TECDOC'])) {
            $vehiculo->id_marca_tecdoc = $this->cleanString($data['ID_MARCA_TECDOC']);
        }

        if (isset($data['ID_MODELO_TECDOC'])) {
            $vehiculo->id_modelo_tecdoc = $this->cleanString($data['ID_MODELO_TECDOC']);
        }

        if (isset($data['ID_KTYPE'])) {
            $vehiculo->id_ktype = $this->cleanString($data['ID_KTYPE']);
        }
    }

    /**
     * Limpia y formatea cadenas de texto.
     */
    private function cleanString(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        return trim(strip_tags($text));
    }

    /**
     * Parsea una fecha al formato de FacturaScripts.
     */
    private function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Si es un timestamp numérico
            if (is_numeric($date)) {
                $timestamp = (int) $date;
                if ($timestamp > 0 && $timestamp < 2147483647) {
                    return date('Y-m-d', $timestamp);
                }
            }

            // Si es una cadena de fecha
            if (is_string($date)) {
                $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d', 'd.m.Y'];

                foreach ($formats as $format) {
                    $dateObj = \DateTime::createFromFormat($format, $date);
                    if ($dateObj !== false) {
                        return $dateObj->format('Y-m-d');
                    }
                }

                // Último intento: usar strtotime
                $timestamp = strtotime($date);
                if ($timestamp !== false && $timestamp > 0) {
                    return date('Y-m-d', $timestamp);
                }
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
