<?php

namespace FacturaScripts\Plugins\DescuentoGlobal;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Plugins\DescuentoGlobal\Extension\Controller\EditProducto as EditProductoExtension;
use FacturaScripts\Plugins\DescuentoGlobal\Extension\Model\Variante as VarianteExtension;

class Init extends InitClass
{
    public function init(): void
    {
        $this->registerExtensions();
    }

    public function uninstall(): void
    {
    }

    public function update(): void
    {
        $this->registerExtensions();
    }

    private function registerExtensions(): void
    {
        $this->loadExtension(new EditProductoExtension());
        $this->loadExtension(new VarianteExtension());
    }
}
