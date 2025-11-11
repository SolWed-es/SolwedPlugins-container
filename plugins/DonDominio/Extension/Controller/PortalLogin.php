<?php

namespace FacturaScripts\Plugins\DonDominio\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;

class PortalLogin
{
    public function execAfterAction(): Closure
    {
        return function ($action) {
            if ('login' !== $action) {
                return;
            }

            try {
                if (!isset($this->contact) || !$this->contact instanceof \FacturaScripts\Dinamic\Model\Contacto || !$this->contact->exists()) {
                    return;
                }

                $cliente = $this->contact->getCustomer(false);
                if (!$cliente instanceof \FacturaScripts\Dinamic\Model\Cliente || !$cliente->exists()) {
                    return;
                }

                $service = new \FacturaScripts\Plugins\DonDominio\Lib\DomainSyncService();
                $service->syncClientIfNeeded($cliente->codcliente, true);
            } catch (\Throwable $exception) {
                Tools::log()->warning('dondominio-portal-login-sync-error', [
                    '%contact%' => isset($this->contact) ? (string)$this->contact->idcontacto : '',
                    '%message%' => $exception->getMessage(),
                ]);
            }
        };
    }
}
