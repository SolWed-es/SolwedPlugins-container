<?php
namespace FacturaScripts\Plugins\AcercaFacturaScripts\Controller;

use FacturaScripts\Core\Base\Controller;

class AboutFacturaScripts extends Controller
{
    public function getPageData(): array
    {
        // Esto añade la opción al menú
        $page = parent::getPageData();
        $page['title'] = 'Acerca de FacturaScripts';
        $page['icon'] = 'fas fa-info-circle';
        $page['menu'] = 'admin';       // puedes moverlo a otro menú si prefieres
        $page['showonmenu'] = true;
        $page['ordernum'] = 99;
        return $page;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        // La plantilla debe llamarse igual que la clase
        $this->setTemplate('AboutFacturaScripts');
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
    }
}
