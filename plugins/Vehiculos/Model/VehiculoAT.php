<?php
/**
 * This file is part of Vehiculos plugin for FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Vehiculos\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Fabricante;

/**
 * Modelo principal de vehículo optimizado para PHP 8.1+
 * Clase principal del sistema de vehículos
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class VehiculoAT extends Base\ModelClass
{
    use Base\ModelTrait;

    public ?string $codagente = null;
    public ?string $codcliente = null;
    public ?string $codfabricante = null;
    public ?string $descripcion = null;
    public ?string $fecha = null;
    public ?int $idmaquina = null;
    public ?string $nombre = null; // Campo legacy - mantener compatibilidad
    public ?string $numserie = null;
    public ?string $matricula = null; // Puede ser null inicialmente
    public ?string $bastidor = null; // Puede ser null inicialmente
    public ?int $kilometros = null;
    public ?string $fecha_matriculacion = null;
    public ?string $color = null;
    public ?string $combustible = null;
    public ?string $codmotor = null; // Código de motor del vehículo
    
    // Campos principales del vehículo (nuevos)
    public ?string $marca = null; // Puede ser null inicialmente
    public ?string $modelo = null; // Puede ser null inicialmente

    /** @var Fabricante|null Cache del fabricante para optimización */
    private ?Fabricante $fabricante = null;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
        $this->fabricante = null;
        
        // Limpiar todos los campos a null inicialmente
        $this->codagente = null;
        $this->codcliente = null;
        $this->codfabricante = null;
        $this->descripcion = null;
        $this->idmaquina = null;
        $this->numserie = null;
        $this->kilometros = null;
        $this->color = null;
        $this->combustible = null;
        $this->fecha_matriculacion = null;
        $this->codmotor = null;
        $this->nombre = null;
        $this->marca = null;
        $this->modelo = null;
        $this->matricula = null;
        $this->bastidor = null;
    }

    /**
     * Obtiene la referencia del vehículo (bastidor/VIN si existe, sino matrícula)
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
     * Obtiene el fabricante del vehículo (con caché para optimización)
     */
    public function getFabricante(): Fabricante
    {
        if ($this->fabricante === null) {
            $this->fabricante = new Fabricante();
            if (!empty($this->codfabricante)) {
                $this->fabricante->loadFromCode($this->codfabricante);
            }
        }
        return $this->fabricante;
    }

    /**
     * Obtiene el cliente propietario del vehículo
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
     * Obtiene información formateada del vehículo para mostrar
     */
    public function getDisplayInfo(): string
    {
        $parts = [];

        // Usar marca + modelo como título principal
        $titulo = trim(trim((string)$this->marca) . ' ' . trim((string)$this->modelo));
        
        // Fallback a nombre legacy si marca/modelo están vacíos
        if (empty($titulo) && !empty($this->nombre)) {
            $titulo = $this->nombre;
        }
        
        if (!empty($titulo)) {
            $parts[] = $titulo;
        }

        // Mostrar matrícula o bastidor como identificador
        if (!empty($this->matricula)) {
            $parts[] = '(' . $this->matricula . ')';
        } elseif (!empty($this->bastidor)) {
            $parts[] = '(VIN: ' . $this->bastidor . ')';
        }

        return trim(implode(' ', $parts)) ?: 'Vehículo sin identificar';
    }
    
    /**
     * Obtiene el nombre del fabricante (optimizado con caché)
     */
    public function getNombreFabricante(): string
    {
        $fabricante = $this->getFabricante();
        return $fabricante->nombre ?? '';
    }
    
    /**
     * Genera nombre automático basado en marca, modelo y matrícula
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

    public function test(): bool
    {
        // Limpiar y normalizar campos de texto para seguridad
        $textFields = ['nombre', 'marca', 'modelo', 'matricula', 'bastidor'];
        foreach ($textFields as $field) {
            $this->{$field} = Tools::noHtml((string)$this->{$field});
        }
        
        // Limpiar campos opcionales de texto
        $optionalTextFields = ['descripcion', 'numserie', 'color', 'combustible', 'codmotor'];
        foreach ($optionalTextFields as $field) {
            if (!empty($this->{$field})) {
                $this->{$field} = Tools::noHtml((string)$this->{$field});
            }
        }

        // Normalizar campos - convertir strings vacíos a null para campos opcionales
        $optionalFields = ['codagente', 'codfabricante', 'descripcion', 
                          'numserie', 'color', 'combustible', 'fecha_matriculacion', 'codmotor'];
        foreach ($optionalFields as $field) {
            if (empty($this->{$field})) {
                $this->{$field} = null;
            }
        }
        
        // Normalizar campos que pueden ser vacíos pero no null en BD
        $stringFields = ['nombre', 'marca', 'modelo', 'matricula', 'bastidor'];
        foreach ($stringFields as $field) {
            if ($this->{$field} === null) {
                $this->{$field} = '';
            }
        }
        
        // Normalizar kilómetros
        if (empty($this->kilometros) || $this->kilometros < 0) {
            $this->kilometros = null;
        }
        
        // Asegurar que fecha siempre tenga un valor
        if (empty($this->fecha)) {
            $this->fecha = Tools::date();
        }

        // === VALIDACIONES OBLIGATORIAS ===
        
        // 1. Validar que tenga cliente asignado (obligatorio)
        if (empty($this->codcliente)) {
            Tools::log()->warning('vehicle-customer-required');
            return false;
        }

        // 2. Validar identificadores del vehículo (al menos uno debe existir)
        if (empty($this->marca) && empty($this->modelo) && empty($this->nombre)) {
            Tools::log()->warning('vehicle-identification-required', [
                'msg' => 'Debe especificar al menos marca y modelo, o nombre del vehículo'
            ]);
            return false;
        }

        // 3. Validar que tenga matrícula O bastidor (al menos uno)
        if (empty($this->matricula) && empty($this->bastidor)) {
            Tools::log()->warning('vehicle-license-or-vin-required', [
                'msg' => 'Debe especificar matrícula o número de bastidor (VIN)'
            ]);
            return false;
        }

        // === VALIDACIONES DE FORMATO ===
        
        // Validar formato VIN/bastidor si está presente
        if (!empty($this->bastidor)) {
            $this->bastidor = strtoupper(trim($this->bastidor));
            // VIN debe tener 17 caracteres alfanuméricos (sin I, O, Q)
            if (strlen($this->bastidor) !== 17 || !preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $this->bastidor)) {
                Tools::log()->warning('bastidor-invalid-format', [
                    'msg' => 'El número de bastidor (VIN) debe tener 17 caracteres alfanuméricos válidos'
                ]);
                return false;
            }
        }

        // Validar matrícula si está presente
        if (!empty($this->matricula)) {
            $this->matricula = strtoupper(trim($this->matricula));
            // Validación básica de matrícula (6-10 caracteres)
            if (strlen($this->matricula) < 4 || strlen($this->matricula) > 15) {
                Tools::log()->warning('matricula-invalid-format', [
                    'msg' => 'La matrícula debe tener entre 4 y 15 caracteres'
                ]);
                return false;
            }
        }

        // Generar nombre automático si está vacío
        if (empty($this->nombre) && (!empty($this->marca) || !empty($this->modelo))) {
            $this->nombre = $this->generarNombreAutomatico();
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListVehiculoAT?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
