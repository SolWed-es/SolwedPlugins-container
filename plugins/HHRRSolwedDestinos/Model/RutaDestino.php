<?php
/**
 * This file is part of HHRRSolwedDestinos plugin for FacturaScripts.
 */
namespace FacturaScripts\Plugins\HHRRSolwedDestinos\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Model to link routes with destinations.
 */
class RutaDestino extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $id;

    /** @var int */
    public $idruta;

    /** @var int */
    public $iddestino;

    /** @var int */
    public $orden;

    public function clear()
    {
        parent::clear();
        $this->orden = 1;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'ruta_destino';
    }

    /**
     * Test data before save (insert or update).
     */
    public function test(): bool
    {
        // Validaciones básicas del padre
        if (!parent::test()) {
            return false;
        }

        // Validar que el orden sea positivo
        if ($this->orden < 1) {
            $this->toolBox()->i18nLog()->error('order-must-be-positive');
            return false;
        }

        // Validar que no exista otro destino con el mismo orden en la misma ruta
        if (!$this->validateUniqueOrder()) {
            return false;
        }

        return true;
    }

    /**
     * Validate that the order is unique within the route.
     * CORREGIDO: Usar métodos del modelo en lugar de SQL directo
     */
    private function validateUniqueOrder(): bool
    {
        // Crear una nueva instancia para hacer la búsqueda
        $searchModel = new static();
        
        // Definir condiciones de búsqueda
        $where = [
            new DataBaseWhere('idruta', $this->idruta),
            new DataBaseWhere('orden', $this->orden)
        ];

        // Si estamos editando un registro existente, excluirlo de la búsqueda
        if (!empty($this->id)) {
            $where[] = new DataBaseWhere('id', $this->id, '!=');
        }

        // Buscar registros que coincidan
        $existingRecords = $searchModel->all($where, [], 0, 1);

        if (!empty($existingRecords)) {
            $this->toolBox()->i18nLog()->error(
                'order-already-exists-in-route', 
                ['%order%' => $this->orden]
            );
            return false;
        }

        return true;
    }

    /**
     * Get the next available order for a route.
     */
    public static function getNextOrder(int $idruta): int
    {
        $model = new static();
        
        // Usar el método all() con WHERE para obtener el máximo orden
        $where = [new DataBaseWhere('idruta', $idruta)];
        $destinos = $model->all($where, ['orden' => 'DESC'], 0, 1);
        
        if (empty($destinos)) {
            return 1; // Primera posición si no hay destinos
        }
        
        return $destinos[0]->orden + 1; // Siguiente posición disponible
    }

    /**
     * Get the related destination.
     */
    public function getDestino(): ?Destino
    {
        if (empty($this->iddestino)) {
            return null;
        }

        $destino = new Destino();
        return $destino->loadFromCode($this->iddestino) ? $destino : null;
    }

    /**
     * Get destination address for display.
     */
    public function direccion(): string
    {
        $destino = $this->getDestino();
        return $destino ? $destino->direccion : '';
    }

    /**
     * Get destination city for display.
     */
    public function ciudad(): string
    {
        $destino = $this->getDestino();
        return $destino ? $destino->ciudad : '';
    }
}