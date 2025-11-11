<?php
namespace FacturaScripts\Plugins\DescargarFacturasZIP;

use FacturaScripts\Core\Base\AjaxForms\PurchasesLineHTML;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Core\Tools;

class Init extends \FacturaScripts\Core\Base\InitClass
{
    public function init() {
        $this->loadExtension(new Extension\Controller\ListFacturaCliente()); 
    }

    public function update() {
    }
}