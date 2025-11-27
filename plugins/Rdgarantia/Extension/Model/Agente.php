<?php
/**
 * FacturaScripts model extension for Agente
 * Adds Rdgarantia-specific properties to the Agente model
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Extension\Model;

use Closure;

class Agente
{
    /** @var string Rdgarantia original ID reference */
    public $rdg_import_ref;

    /** @var string Spanish DNI */
    public $rdg_dni;

    /** @var string Last synchronization timestamp */
    public $rdg_last_sync;

    public function __call(string $name, array $arguments)
    {
        return $this->pipe($name, $arguments, function ($name, $arguments) {
            return null;
        });
    }

    protected function pipe(string $name, array $arguments, Closure $next)
    {
        return $next($name, $arguments);
    }
}
