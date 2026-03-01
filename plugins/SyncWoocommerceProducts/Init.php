<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos;

use FacturaScripts\Core\Html;
use Twig\TwigFunction;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Los plugins pueden contener un archivo Init.php en el que se definen procesos a ejecutar
 * cada vez que carga FacturaScripts o cuando se instala o actualiza el plugin.
 *
 * https://facturascripts.com/publicaciones/el-archivo-init-php-307
 */
class Init extends InitClass
{
    public function init(): void
    {
        // se ejecuta cada vez que carga FacturaScripts (si este plugin está activado).
        $this->loadExtension(new Extension\Controller\EditProducto());
        $this->loadExtension(new Extension\Model\Producto());

        // Add json_decode function to twig settings
        Html::addFunction(
            new TwigFunction('json_decode', function (string $json) {
                if (empty($json)) {
                    return [];
                }
                $decoded = json_decode($json, true);
                return $decoded !== null ? $decoded : [];
            })
        );
    }

    public function uninstall(): void
    {
        // se ejecuta cada vez que se desinstale el plugin. Primero desinstala y luego ejecuta el uninstall.
    }

    public function update(): void
    {
        // se ejecuta cada vez que se instala o actualiza el plugin
        $this->setDefaultSettings();
    }

    // set default settings to empty (as a workaround to the error message “Field 'name' doesn't have a default value")
    private function setDefaultSettings(): void
    {
        if (empty(Tools::settings('woocommerce', 'enlace'))) {
            Tools::settingsSet('woocommerce', 'enlace', '');
        }
        if (empty(Tools::settings('woocommerce', 'ck'))) {
            Tools::settingsSet('woocommerce', 'ck', '');
        }
        if (empty(Tools::settings('woocommerce', 'cs'))) {
            Tools::settingsSet('woocommerce', 'cs', '');
        }

        Tools::settingsSave();
    }
}
