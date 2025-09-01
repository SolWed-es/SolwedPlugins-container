<?php


namespace FacturaScripts\Plugins\IeWhatsapp\Mod;

use FacturaScripts\Core\Base\Contract\SalesModInterface;

use FacturaScripts\Core\Base\Translator;

use FacturaScripts\Core\Model\Base\SalesDocument;

use FacturaScripts\Core\Model\User;

use FacturaScripts\Core\Tools;


class SalesMod implements SalesModInterface
{


    public function apply(SalesDocument &$model, array $formData, User $user)
    {

    }


    public function applyBefore(SalesDocument &$model, array $formData, User $user)
    {

    }


    public function assets(): void
    {

    }


    public function newFields(): array
    {

        return [];

    }


    public function newModalFields(): array
    {

        return [];

    }


    public function newBtnFields(): array
    {

        return ['_whatsappBtn'];

    }


    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        if ($field === '_whatsappBtn') {
            return $this->renderWhatsappButton($i18n, $model);
        }
        return null;
    }

    private function renderWhatsappButton(Translator $i18n, SalesDocument $model): string
    {
        $subject = $model->getSubject();
        $telefono1 = $subject->telefono1 ?? '';
        $telefono2 = $subject->telefono2 ?? '';
        $prefijo = Tools::settings('whatsapp', 'prefijo', '34');

        // Detect document type
        $tipo = '';
        $codigo = '';
        if (property_exists($model, 'idpresupuesto')) {
            $tipo = 'presupuesto';
            $codigo = $model->codigo ?? '';
        } elseif (property_exists($model, 'idpedido')) {
            $tipo = 'pedido';
            $codigo = $model->codigo ?? '';
        } elseif (property_exists($model, 'idalbaran')) {
            $tipo = 'albaran';
            $codigo = $model->codigo ?? '';
        } elseif (property_exists($model, 'idfactura')) {
            $tipo = 'factura';
            $codigo = $model->codigo ?? '';
        }

        // Get and process message
        $template = Tools::settings('whatsapp', 'msg' . $tipo, '');
        if (empty($template)) {
            $template = '';
        }

        $documentInfo = [
            'type' => $tipo,
            'code' => $codigo,
        ];
        $defaultMsg = $this->processMessageTemplate($template, $model, $documentInfo);


        $hasPhones = !empty($telefono1) || !empty($telefono2);
        error_log("hasPhones ------------->  " . ($hasPhones ? 'true' : 'false'));
        error_log("Telefono 1 ----> " . $telefono1);
        error_log("Telefono 2 ----> " . $telefono2);


        // Start HTML
        $html = '<button type="button" class="btn btn-success mr-2" data-toggle="modal" data-target="#whatsappModal">'
            . '<i class="fab fa-whatsapp"></i> WhatsApp</button>';

        // Modal
        $html .= '<div class="modal fade" id="whatsappModal" tabindex="-1" aria-labelledby="whatsappModalLabel" aria-hidden="true">'
            . '<div class="modal-dialog" role="document">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title" id="whatsappModalLabel">Editar mensaje de WhatsApp</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'

            // Prefix + phone row
            . '<div class="form-row">'
            . '<div class="col-2">'
            . '<label for="whatsappPrefix">Prefijo</label>'
            . '<input type="text" id="whatsappPrefix" class="form-control mb-2" maxlength="3" value="' . htmlspecialchars($prefijo) . '">'
            . '</div>'
            . '<div class="col">'
            . '<label for="whatsappPhone">Teléfono</label>';
$html .= '<select id="whatsappPhone" class="form-control mb-2">';
if (!empty($telefono2)) {
    $html .= '<option value="' . htmlspecialchars($telefono2) . '" selected>' . htmlspecialchars($telefono2) . ' (Tel 2)</option>';
}
if (!empty($telefono1)) {
    $html .= '<option value="' . htmlspecialchars($telefono1) . '">' . htmlspecialchars($telefono1) . ' (Tel 1)</option>';
}
$html .= '<option value="custom">Personalizado</option>'
. '</select>'
        . '</select>'
            . '</div>'
            . '</div>'

            // Custom phone input (initially hidden)
            . '<div class="form-group d-none" id="customPhoneGroup">'
            . '<label for="customPhoneInput">Teléfono personalizado</label>'
            . '<input type="text" id="customPhoneInput" class="form-control mb-2" placeholder="Introduce un número">'
            . '</div>'

            // Message box with copy button
            . '<div class="position-relative">'
            . '<label for="whatsappMessage">Mensaje</label>'
            . '<textarea id="whatsappMessage" class="form-control" rows="5" style="padding-right: 45px;">' . htmlspecialchars($defaultMsg) . '</textarea>'
            . '<button type="button" onclick="copyWhatsAppMessage()" class="btn btn-sm btn-outline-secondary position-absolute" style="top: 35px; right: 20px; border: 1px solid #dee2e6; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">'
            . '<i class="fas fa-copy"></i> Copiar</button>'
            . '</div>'

            // Disabled hint box
            . '<textarea id="infobox" class="form-control mt-2" disabled rows="2" style="resize: none;">'
            . 'Copia el mensaje si es la primera vez que contactas con este número.'
            . '</textarea>'

            . '</div>' // modal-body

            // Footer
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>'
            . '<button type="button" class="btn btn-success" id="sendWhatsappBtn">Enviar</button>'
            . '</div>'
            . '</div></div></div>';


        return $html;
    }


    private function processMessageTemplate(string $template, SalesDocument $model, array $documentInfo): string
    {
        $subject = $model->getSubject();
        // message header
        $inicio = Tools::settings('whatsapp', 'inicio', '');
        // Message footer
        $fin = Tools::settings('whatsapp', 'fin', '');

        // Build the complete message
        $message = $inicio . "\n\n" . $template . "\n\n" . $fin;

        // Replace placeholders
        $replacements = [
            '{CLIENTE}' => $subject->nombre ?? '',
            '{DOCUMENTO}' => strtoupper($documentInfo['type']),
            '{CODIGO}' => $documentInfo['code'],
            '{TOTAL}' => number_format($model->total, 2) . ' €',
            '{FECHA}' => $model->fecha ?? ''
        ];

        foreach ($replacements as $placeholder => $value) {
            $message = str_replace($placeholder, $value, $message);
        }

        return trim($message);
    }


}