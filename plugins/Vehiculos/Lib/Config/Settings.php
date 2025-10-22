<?php
namespace FacturaScripts\Plugins\Vehiculos\Lib\Config;

use FacturaScripts\Core\Tools;

/**
 * Helper para acceder a configuraciones migradas de 'servicios' a 'vehiculos' con fallback.
 */
class Settings
{
    /**
     * Obtiene un valor de configuración del plugin 'vehiculos' con fallback a 'servicios'.
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        $value = Tools::settings('vehiculos', $name, null);
        if ($value !== null) {
            return $value;
        }
        $legacy = Tools::settings('servicios', $name, $default);
        if ($legacy !== $default) {
            // Loguea solo una vez por petición (cache simple estática)
            static $logged = [];
            if (!isset($logged[$name])) {
                Tools::log()->warning('Settings fallback legacy (servicios)->vehiculos: ' . $name);
                $logged[$name] = true;
            }
        }
        return $legacy;
    }

    /**
     * Atajo semántico para flags booleanos.
     */
    public static function flag(string $name, bool $default = false): bool
    {
        return (bool) static::get($name, $default);
    }
}
