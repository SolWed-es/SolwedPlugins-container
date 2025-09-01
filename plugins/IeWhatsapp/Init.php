<?php

namespace FacturaScripts\Plugins\IeWhatsapp;

use FacturaScripts\Core\Base\AjaxForms\SalesFooterHTML;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;

class Init extends InitClass
{

    public function init(): void
    {
        SalesFooterHTML::addMod(new Mod\SalesMod());
    }

    public function uninstall(): void
    {
        // se ejecuta cada vez que se desinstale el plugin. Primero desinstala y luego ejecuta el uninstall.
    }

    public function update(): void
    {
        // se ejecuta cada vez que se instala o actualiza el plugin
        $this->setupSettings();
    }




    private function setupSettings(): void
    {

        $prefijo = 34;
        $textoInicio = "Estimado/a cliente,";
        $textoFin = "Gracias por confiar en nosotros.

Saludos cordiales.";

        $plantillaPresupuesto = "Su {DOCUMENTO} {CODIGO} está disponible para su revisión.

{TOTAL}

Tiene una validez de 30 días.";
        $plantillaPedido = "Su {DOCUMENTO} {CODIGO} ha sido procesado correctamente.

{TOTAL}

Tiempo estimado de entrega: 3-5 días laborables.";
        $plantillaAlbaran = "Su {DOCUMENTO} {CODIGO} está listo para su recogida.

{TOTAL}";
        $plantillaFactura = "Su {DOCUMENTO} {CODIGO} ha sido emitida.

{TOTAL}

Puede realizar el pago en nuestras instalaciones o por transferencia.";

        if (empty(Tools::settings('whatsapp', 'prefijo'))) {
            Tools::settingsSet('whatsapp', 'prefijo', $prefijo);
        }
        if (empty(Tools::settings('whatsapp', 'inicio'))) {
            Tools::settingsSet('whatsapp', 'inicio', $textoInicio);
        }
        if (empty(Tools::settings('whatsapp', 'fin'))) {
            Tools::settingsSet('whatsapp', 'fin', $textoFin);
        }
        if (empty(Tools::settings('whatsapp', 'msgpresupuesto'))) {
            Tools::settingsSet('whatsapp', 'msgpresupuesto', $plantillaPresupuesto);
        }
        if (empty(Tools::settings('whatsapp', 'msgpedido'))) {
            Tools::settingsSet('whatsapp', 'msgpedido', $plantillaPedido);
        }
        if (empty(Tools::settings('whatsapp', 'msgalbaran'))) {
            Tools::settingsSet('whatsapp', 'msgalbaran', $plantillaAlbaran);
        }
        if (empty(Tools::settings('whatsapp', 'msgfactura'))) {
            Tools::settingsSet('whatsapp', 'msgfactura', $plantillaFactura);
        }

        $ayudaParametros = "{CLIENTE} - Nombre del cliente
{DOCUMENTO} - Tipo de documento (Presupuesto, Pedido, Albarán, Factura)
{CODIGO} - Código del documento
{TOTAL} - Total (ej. 123,45 €)
{FECHA} - Fecha del documento";
        // Set help text at plugin install
        Tools::settingsSet('whatsapp', 'textoayuda', $ayudaParametros);

        Tools::settingsSave();
    }
}


