<?php
namespace FacturaScripts\Plugins\Vehiculos\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Modelo de Vehículo optimizado para FacturaScripts.
 * Gestiona la información básica de vehículos para talleres y concesionarios.
 */
class Vehiculo extends ModelClass {
    use ModelTrait;

    // === Campos principales del vehículo ===
    public $idmaquina;           // ID único del vehículo
    public $marca;               // Marca del vehículo
    public $modelo;              // Modelo del vehículo (obligatorio)
    public $matricula;           // Matrícula (obligatorio)
    public $bastidor;            // Número de bastidor (VIN)
    public $numserie;            // Código del modelo
    public $kilometros;          // Kilometraje actual
    public $nombre;              // Nombre del vehículo

    // === Campos de propietario ===
    public $codcliente;          // Código del cliente propietario
    public $codagente;           // Agente comercial asignado
    public $codfabricante;       // Código del fabricante

    // === Campos de vehículo ===
    public $descripcion;                 // Descripción adicional
    public $fecha;                       // Fecha de alta del vehículo
    public $fecha_primera_matriculacion; // Fecha de matriculación
    public $carroceria;                  // Carrocería y tracción del vehículo
    public $motor;                       // Información completa del motor
    public $potencia;                    // Potencia completa (ej: "2.0 TFSI 200CV")
    public $procedencia_matricula;       // Procedencia de la matrícula
    public $color;                       // Color del vehículo
    public $combustible;                 // Tipo de combustible
    public $codmotor;                    // Código del motor

    // === Campos TecDoc (IDs de catálogo de piezas) ===
    public $idmarca_api;                 // ID de marca / Código Marca
    public $id_marca_tecdoc;             // ID TecDoc marca
    public $id_modelo_tecdoc;            // ID TecDoc modelo
    public $id_ktype;                    // Tipo K de TecDoc

    // === Campos de información del cliente (sólo memoria, no DB) ===
    public $cliente_nombre = '';
    public $cliente_cifnif = '';
    public $cliente_email = '';
    public $cliente_telefono = '';
    public $cliente_direccion = '';
    public $cliente_poblacion = '';
    public $cliente_provincia = '';

    /**
     * Inicializa un nuevo vehículo con valores por defecto.
     */
    public function clear(): void
    {
        parent::clear();

        // Inicializar campos principales del vehículo
        $this->idmaquina = null;
        $this->marca = '';
        $this->modelo = '';
        $this->matricula = '';
        $this->bastidor = '';
        $this->numserie = '';
        $this->kilometros = 0;
        $this->nombre = '';

        // Campos de propietario
        $this->codcliente = '';
        $this->codagente = '';
        $this->codfabricante = '';

        // Campos de vehículo
        $this->descripcion = '';
        $this->fecha = \FacturaScripts\Core\Tools::date();
        $this->fecha_primera_matriculacion = null;
        $this->carroceria = '';
        $this->motor = '';
        $this->potencia = '';
        $this->procedencia_matricula = '';
        $this->color = '';
        $this->combustible = '';
        $this->codmotor = '';

        // Campos TecDoc
        $this->idmarca_api = null;
        $this->id_marca_tecdoc = null;
        $this->id_modelo_tecdoc = null;
        $this->id_ktype = null;
    }

    /**
     * Obtiene la referencia principal del vehículo para identificación.
     * Prioriza bastidor > matrícula > texto genérico.
     */
    public function referencia(): string
    {
        if (!empty($this->bastidor)) {
            return $this->bastidor;
        }
        if (!empty($this->matricula)) {
            return $this->matricula;
        }
        return 'Sin identificar';
    }

    /**
     * Obtiene el cliente propietario del vehículo.
     */
    public function getCliente(): ?\FacturaScripts\Dinamic\Model\Cliente
    {
        if (empty($this->codcliente)) {
            return null;
        }
        $cliente = new \FacturaScripts\Dinamic\Model\Cliente();
        return $cliente->loadFromCode($this->codcliente) ? $cliente : null;
    }

    /**
     * Obtiene el fabricante del vehículo (con caché para optimización).
     */
    public function getFabricante(): ?\FacturaScripts\Dinamic\Model\Fabricante
    {
        if (empty($this->codfabricante)) {
            return null;
        }
        $fabricante = new \FacturaScripts\Dinamic\Model\Fabricante();
        return $fabricante->loadFromCode($this->codfabricante) ? $fabricante : null;
    }

    /**
     * Genera una descripción completa del vehículo para mostrar al usuario.
     * Incluye fecha de matriculación para diferenciar duplicados.
     */
    public function getDisplayInfo(): string
    {
        $titulo = trim(trim((string)$this->marca) . ' ' . trim((string)$this->modelo));
        $ident = '';

        if (!empty($this->matricula)) {
            $ident = '(' . strtoupper($this->matricula);

            // Agregar fecha de matriculación si existe para diferenciar duplicados
            if (!empty($this->fecha_primera_matriculacion)) {
                try {
                    $fecha = new \DateTime($this->fecha_primera_matriculacion);
                    $ident .= ' - ' . $fecha->format('d/m/Y');
                } catch (\Exception $e) {
                    // Si hay error parseando la fecha, continuar sin ella
                }
            }

            $ident .= ')';
        } elseif (!empty($this->bastidor)) {
            $ident = '(Bastidor: ' . strtoupper($this->bastidor) . ')';
        }

        $res = trim($titulo . ' ' . $ident);
        return $res !== '' ? $res : 'Vehículo sin identificar';
    }

    /**
     * Genera un nombre automático para el vehículo basado en sus datos.
     */
    public function generarNombreAutomatico(): string
    {
        $partes = array_filter([
            trim((string)$this->marca),
            trim((string)$this->modelo),
            !empty($this->matricula) ? "({$this->matricula})" : null
        ]);
        return implode(' ', $partes) ?: 'Vehículo';
    }

    // === Métodos requeridos por FacturaScripts ===
    
    public static function primaryColumn(): string 
    { 
        return 'idmaquina'; 
    }
    
    public function primaryDescriptionColumn(): string 
    { 
        return 'matricula'; 
    }

    public static function tableName(): string
    {
        return 'vehiculos';
    }
    
    public function install(): string
    {
        return '';  // No requiere instalación especial
    }

    /**
     * Validación y preparación de datos antes del guardado.
     */
    public function test(): bool
    {
        // Cargar datos desde POST en contexto web
        if (PHP_SAPI !== 'cli') {
            $this->loadFromPost();
        }

        // Sanitizar y normalizar datos
        $this->sanitizeAndNormalize();

        // Validar datos mínimos requeridos
        if (!$this->hasMinimumData()) {
            Tools::log()->warning('Datos insuficientes: se requiere modelo y matrícula o bastidor');
            return false;
        }

        // Validar que no haya duplicados para el mismo cliente
        if (!$this->validateNoDuplicates()) {
            return false;
        }

        // Advertir si la matrícula existe en otros clientes
        $this->warnIfDuplicate();

        return parent::test();
    }

    /**
     * Guarda el vehículo en la base de datos.
     */
    public function save(): bool
    {
        try {
            return parent::save();
        } catch (\Throwable $e) {
            Tools::log()->error('Error al guardar vehículo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera URL para el vehículo.
     */
    public function url(string $type = 'auto', string $list = 'ListVehiculo?activetab=List'): string
    { 
        return parent::url($type, $list); 
    }

    // === Métodos privados auxiliares ===
    
    /**
     * Carga datos desde POST.
     */
    private function loadFromPost(): void
    {
        $camposVehiculo = [
            'marca', 'modelo', 'matricula', 'bastidor', 'numserie', 'kilometros', 'nombre',
            'codcliente', 'codagente', 'codfabricante',
            'carroceria', 'motor', 'potencia', 'procedencia_matricula',
            'fecha', 'fecha_primera_matriculacion', 'descripcion',
            'color', 'combustible', 'codmotor',
            'idmarca_api', 'id_marca_tecdoc', 'id_modelo_tecdoc', 'id_ktype'
        ];

        foreach ($camposVehiculo as $campo) {
            if (isset($_POST[$campo]) && $_POST[$campo] !== '') {
                if ($campo === 'kilometros') {
                    $this->$campo = (int)$_POST[$campo];
                } else {
                    $this->$campo = is_string($_POST[$campo]) ? Tools::noHtml((string)$_POST[$campo]) : $_POST[$campo];
                }
            }
        }
    }

    /**
     * Verifica si tiene los datos mínimos requeridos.
     */
    private function hasMinimumData(): bool
    {
        $hasModelo = !empty(trim((string)$this->modelo));
        $hasIdentificacion = !empty(trim((string)$this->matricula)) || !empty(trim((string)$this->bastidor));
        
        return $hasModelo && $hasIdentificacion;
    }

    /**
     * Sanitiza y normaliza todos los campos del vehículo.
     */
    private function sanitizeAndNormalize(): void
    {
        // Sanitizar texto
        $this->sanitizeTextFields();
        
        // Normalizar formatos
        $this->normalizeFormats();
        
        // Asegurar valores obligatorios
        $this->ensureRequiredValues();
        
        // Limitar longitudes
        $this->limitFieldLengths();
        
        // Validar datos numéricos
        $this->validateNumericFields();
    }

    /**
     * Sanitiza campos de texto.
     */
    private function sanitizeTextFields(): void
    {
        $textFields = ['marca', 'modelo', 'matricula', 'bastidor', 'numserie'];
        
        foreach ($textFields as $field) {
            if ($this->$field !== '' && $this->$field !== null) {
                $this->$field = Tools::noHtml(trim((string)$this->$field));
            }
        }
    }

    /**
     * Normaliza formatos específicos.
     */
    private function normalizeFormats(): void
    {
        // Matrícula y bastidor en mayúsculas
        if (!empty($this->matricula)) {
            $this->matricula = strtoupper($this->matricula);
        }
        
        if (!empty($this->bastidor)) {
            $this->bastidor = strtoupper($this->bastidor);
        }
    }

    /**
     * Asegura valores obligatorios para la base de datos.
     */
    private function ensureRequiredValues(): void
    {
        // Modelo es NOT NULL en BD
        if (empty($this->modelo)) {
            $this->modelo = 'Sin especificar';
        }

        // Matrícula es NOT NULL en BD
        if (empty($this->matricula)) {
            if (!empty($this->bastidor)) {
                $this->matricula = $this->bastidor;
            } else {
                $this->matricula = 'SIN-MAT-' . time();
            }
        }
    }

    /**
     * Limita la longitud de los campos según la base de datos.
     */
    private function limitFieldLengths(): void
    {
        $this->marca = $this->truncateString($this->marca, 50);
        $this->modelo = $this->truncateString($this->modelo, 100);
        $this->matricula = $this->truncateString($this->matricula, 20);
        $this->bastidor = $this->truncateString($this->bastidor, 25);
        $this->numserie = $this->truncateString($this->numserie, 100);
    }

    /**
     * Valida campos numéricos.
     */
    private function validateNumericFields(): void
    {
        if ($this->kilometros !== null && $this->kilometros < 0) {
            $this->kilometros = 0;
        }
    }

    /**
     * Trunca una cadena de forma segura.
     */
    private function truncateString(string $text, int $maxLength): string
    {
        if ($text === '' || $maxLength <= 0) {
            return '';
        }
        return mb_substr($text, 0, $maxLength, 'UTF-8');
    }

    // === Métodos públicos de utilidad ===

    /**
     * Actualiza el kilometraje del vehículo con validaciones.
     */
    public function actualizarKilometros(int $nuevosKm, bool $forzar = false): bool
    {
        if ($nuevosKm < 0) {
            return false;
        }
        
        // Si no se fuerza, no permitir retroceder el kilometraje
        if (!$forzar && $this->kilometros !== null && $nuevosKm < $this->kilometros) {
            return true; // No es un error, simplemente no se actualiza
        }
        
        $this->kilometros = $nuevosKm; 
        return $this->save();
    }

    /**
     * Reasigna el vehículo a otro cliente.
     */
    public function reasignarACliente(string $nuevoCodCliente): bool
    {
        if ($nuevoCodCliente === $this->codcliente) {
            return true; // Ya está asignado a ese cliente
        }
        
        $this->codcliente = $nuevoCodCliente;
        return $this->save();
    }

    /**
     * Busca un vehículo por cliente e identificación.
     */
    public function loadByClienteIdent(string $codcliente, ?string $matricula = null, ?string $bastidor = null): bool
    {
        $matricula = $matricula ? strtoupper(trim($matricula)) : null;
        $bastidor = $bastidor ? strtoupper(trim($bastidor)) : null;

        $where = [new DataBaseWhere('codcliente', $codcliente)];

        if ($matricula) {
            $where[] = new DataBaseWhere('matricula', $matricula);
        } elseif ($bastidor) {
            $where[] = new DataBaseWhere('bastidor', $bastidor);
        } else {
            return false; // Necesita al menos matrícula o bastidor
        }

        return $this->loadFromCode('', $where);
    }

    /**
     * Valida que no exista un vehículo duplicado para el mismo cliente.
     * Previene que un cliente tenga dos vehículos con la misma matrícula.
     */
    private function validateNoDuplicates(): bool
    {
        // Solo validar si hay matrícula y cliente
        if (empty($this->matricula) || empty($this->codcliente)) {
            return true;
        }

        $existing = new Vehiculo();
        $where = [
            new DataBaseWhere('matricula', $this->matricula),
            new DataBaseWhere('codcliente', $this->codcliente)
        ];

        // Si es edición, excluir el registro actual
        if (!empty($this->idmaquina)) {
            $where[] = new DataBaseWhere('idmaquina', $this->idmaquina, '!=');
        }

        if ($existing->loadFromCode('', $where)) {
            Tools::log()->warning('duplicate-vehicle-same-customer', [
                '%matricula%' => $this->matricula
            ]);
            return false;
        }

        return true;
    }

    /**
     * Advierte si la matrícula ya existe en otros clientes.
     * No bloquea el guardado, solo informa al usuario.
     */
    private function warnIfDuplicate(): void
    {
        // Solo advertir si hay matrícula
        if (empty($this->matricula)) {
            return;
        }

        // Solo advertir en creación, no en edición
        if (!empty($this->idmaquina)) {
            return;
        }

        // Buscar otros vehículos con la misma matrícula pero diferente cliente
        $existing = new Vehiculo();
        $where = [new DataBaseWhere('matricula', $this->matricula)];

        // Excluir el cliente actual si existe
        if (!empty($this->codcliente)) {
            $where[] = new DataBaseWhere('codcliente', $this->codcliente, '!=');
        }

        $count = $existing->count($where);

        if ($count > 0) {
            $cliente = '';
            if ($existing->loadFromCode('', $where)) {
                $clienteObj = $existing->getCliente();
                $cliente = $clienteObj ? $clienteObj->nombre : 'desconocido';
            }

            Tools::log()->warning('duplicate-vehicle-other-customer', [
                '%matricula%' => $this->matricula,
                '%count%' => $count,
                '%customer%' => $cliente
            ]);
        }
    }
}