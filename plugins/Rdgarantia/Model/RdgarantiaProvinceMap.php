<?php
/**
 * Rdgarantia Province Map Model
 * Maps Rdgarantia province IDs to FacturaScripts province names
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;

class RdgarantiaProvinceMap extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $rdg_province_id;

    /** @var string */
    public $fs_province_name;

    public static function primaryColumn(): string
    {
        return 'rdg_province_id';
    }

    public static function tableName(): string
    {
        return 'rdgarantia_province_map';
    }

    /**
     * Get FS province name by RDG province ID
     */
    public static function getProvinceName(int $rdgProvinceId): string
    {
        $map = new self();
        if ($map->loadFromCode($rdgProvinceId)) {
            return $map->fs_province_name;
        }

        return '';
    }

    /**
     * Load province mappings from JSON file
     */
    public static function loadMappingsFromFile(): bool
    {
        $jsonFile = dirname(__DIR__) . '/Data/province_mappings.json';

        if (!file_exists($jsonFile)) {
            return false;
        }

        $mappings = json_decode(file_get_contents($jsonFile), true);
        if (!$mappings) {
            return false;
        }

        foreach ($mappings as $rdgId => $provinceName) {
            // Solwed addition - Check if exists without clearing current object
            $existing = new self();
            if (!$existing->loadFromCode((int)$rdgId)) {
                // Doesn't exist, create new mapping
                $map = new self();
                $map->rdg_province_id = (int)$rdgId;
                $map->fs_province_name = $provinceName;
                $map->save();
            }
        }

        return true;
    }
}
