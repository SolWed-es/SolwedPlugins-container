<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Plugins\Facturae;

require_once __DIR__ . '/vendor/autoload.php';

class Init extends \FacturaScripts\Core\Base\InitClass
{

    public function init()
    {
        // se ejecuta cada vez que carga FacturaScripts (si este plugin está activado).
        $this->loadExtension(new Extension\Controller\EditFacturaCliente());
    }

    public function update()
    {
        // se ejecuta cada vez que se instala o actualiza el plugin
    }
}
