<?php
/**
 * FacturaScripts model extension for Cliente
 * Adds Rdgarantia-specific properties to the Cliente model
 *
 * @author  Solwed
 * @package Rdgarantia Plugin
 */

namespace FacturaScripts\Plugins\Rdgarantia\Extension\Model;

use Closure;

class Cliente
{
    /** @var string Rdgarantia original ID reference */
    public $rdg_import_ref;

    /** @var string Multi-brand status (SI/NO) */
    public $rdg_multibrand;

    /** @var string Owner name */
    public $rdg_owner;

    /** @var string Sales responsible */
    public $rdg_sales_responsible;

    /** @var string Administration responsible */
    public $rdg_admin_responsible;

    /** @var string Workshop responsible */
    public $rdg_workshop_responsible;

    /** @var string Administration email */
    public $rdg_admin_email;

    /** @var string Workshop phone */
    public $rdg_workshop_phone;

    /** @var int Custom IVA percentage */
    public $rdg_iva_percent;

    /** @var string GPS latitude */
    public $rdg_latitude;

    /** @var string GPS longitude */
    public $rdg_longitude;

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
