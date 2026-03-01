<?php
/**
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\CronJob;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\CronJobClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\CrmCampaign;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class SendCampaign extends CronJobClass
{
    const JOB_NAME = 'crm-send-campaign';
    const LIMIT = 25;

    public static function run(): void
    {
        echo "\n\n* JOB: " . self::JOB_NAME . ' ...';

        // recorremos las campañas abiertas
        foreach (self::getCampaigns() as $campaign) {

            // si la campaña está fuera de horario, saltamos a la siguiente
            if (false === $campaign->canSendNow()) {
                continue;
            }

            self::echo("\n- Enviando emails de la campaña " . $campaign->name);

            // obtenemos los emails no enviados de la campaña
            $emails = $campaign->getPendingEmails(self::LIMIT);

            // si no hay emails, saltamos a la siguiente campaña
            if (empty($emails)) {
                // marcamos la campaña como completa
                $campaign->save();
                self::echo("\n-- No hay emails pendientes de enviar en la campaña " . $campaign->name);
                continue;
            }

            self::sendMails($campaign, $emails);

            // actualizamos la campaña
            $campaign->save();
        }

        self::saveEcho();
    }

    public static function sendMail(CrmCampaign $campaign, string $outgoing_email, string $email, string $name): bool
    {
        // creamos el email
        $newEmail = NewMail::create()
            ->setUser(Session::user())
            ->setMailbox($outgoing_email)
            ->to($email, $name);

        // preparamos el texto
        $params = [
            'email' => $email,
            'name' => $name,
            'verificode' => $newEmail->verificode
        ];
        $subject = self::getText($campaign->subject, $params);
        $body = self::getText(Tools::fixHtml($campaign->content), $params);

        // añadimos el texto al email
        $newEmail->subject($subject)
            ->body($body);

        // comprobamos si el email puede enviar emails
        if (false === $newEmail->canSendMail()) {
            self::echo(" - Email no configurado.");
            return false;
        }

        // añadimos los adjuntos
        foreach ($campaign->getAttachments() as $attachedFile) {
            $newEmail->addAttachment($attachedFile->getFullPath(), $attachedFile->filename);
        }

        return $newEmail->send();
    }

    protected static function getCampaigns(): array
    {
        $where = [new DataBaseWhere('status', CrmCampaign::STATUS_SENDING)];
        return CrmCampaign::all($where, [], 0, 0);
    }

    public static function getText(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    protected static function sendMails(CrmCampaign $campaign, array $emails): void
    {
        // recorremos los grupos de emails
        foreach ($emails as $email) {
            self::echo("\n-- Enviando email desde " . $email->outgoing_email . " a " . $email->email);

            if (false === self::sendMail($campaign, $email->outgoing_email, $email->email, $email->getContact()->fullName())) {
                self::echo(" - Error al enviar el email");
                continue;
            }

            // marcamos el email como enviado
            $email->sent = Tools::dateTime();
            $email->save();

            // esperamos 1 segundo entre envíos
            sleep(1);
        }
    }
}
