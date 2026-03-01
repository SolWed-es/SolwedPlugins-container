<?php
namespace FacturaScripts\Plugins\AcercaFacturaScripts\Controller;

use FacturaScripts\Core\Base\Controller;

class ServerData extends Controller
{
    /** Debe devolver array, igual que la firma del padre */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'Datos de servidor';
        $data['menu']  = 'admin';           // Menú Administración
        $data['icon']  = 'fas fa-server';   // Icono opcional
        return $data;
    }

    /** Renderiza la vista View/ServerData.html.twig por convención */
    public function render()
    {
        parent::render();
    }
}
