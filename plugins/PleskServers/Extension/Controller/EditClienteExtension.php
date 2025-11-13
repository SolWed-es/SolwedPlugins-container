<?php

namespace FacturaScripts\Plugins\PleskServers\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;

class EditClienteExtension
{
    public function createViews(): Closure
    {
        return function (): void {
            // Agregar pestaña Plesk al cliente
            $this->addHtmlView('PleskClient', 'PleskClient.html.twig', 'Cliente', 'Plesk', 'fa-solid fa-server');
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view): void {
            if ($viewName === 'PleskClient') {
                // Cargar datos específicos para la pestaña Plesk
                $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
                // Aquí podríamos cargar servicios Plesk asociados al cliente
            }
        };
    }
}
