<?php

/**
 * Rdgarantia Sync Map Model
 * Maps Rdgarantia IDs to FacturaScripts codes
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;

class RdgarantiaSyncMap extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $id;

    /** @var int */
    public $rdg_id;

    /** @var string */
    public $fs_code;

    /** @var string */
    public $entity_type;

    /** @var string */
    public $last_sync;

    /** @var string */
    public $created_at;

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'rdgarantia_sync_map';
    }

    /**
     * Get FS code by RDG ID and entity type
     */
    public static function getFsCode(int $rdgId, string $entityType): ?string
    {
        $map = new self();
        $where = [
            new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('rdg_id', $rdgId),
            new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('entity_type', $entityType)
        ];

        if ($map->loadFromCode('', $where)) {
            return $map->fs_code;
        }

        return null;
    }

    /**
     * Check if RDG ID already exists
     */
    public static function rdgExists(int $rdgId, string $entityType): bool
    {
        return self::getFsCode($rdgId, $entityType) !== null;
    }
}
