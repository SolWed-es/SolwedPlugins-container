<?php
/**
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\CronJob;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Users;
use FacturaScripts\Core\Template\CronJobClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\MailNotifier;
use FacturaScripts\Dinamic\Model\CrmNota;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class NotifyNote extends CronJobClass
{
    const JOB_NAME = 'crm-notify-note';

    public static function run(): void
    {
        self::echo("\n\n* JOB: " . self::JOB_NAME . ' ...');

        $where = [
            new DataBaseWhere('fecha_notificado', null),
            new DataBaseWhere('fechaaviso', Tools::date())
        ];
        $order_by = ['fechaaviso' => 'ASC'];
        foreach (CrmNota::all($where, $order_by, 0, 0) as $note) {
            // si la nota tiene hora de aviso
            // y la hora de aviso no es igual a la hora actual
            // no se notifica
            if (false === empty($note->hora)
                && date('H') !== substr($note->notice_hour, 0, 2)) {
                continue;
            }

            if ($note->paratodos) {
                // si la nota es para todos los usuarios, se notifica a todos los usuarios
                $users = Users::all();
            } else {
                // si la nota es para un usuario en concreto, se notifica solo a ese usuario
                $users = [Users::get($note->nick)];
            }

            if (self::notify($users, $note->id)) {
                $note->fecha_notificado = Tools::dateTime();
                $note->save();
            }
        }

        self::saveEcho();
    }

    protected static function notify(array $users, int $id): bool
    {
        $result = false;

        foreach ($users as $user) {
            // si el usuario no tiene email, no se le puede notificar
            if (empty($user->email)) {
                continue;
            }

            // enviamos la notificación por email
            $sending = MailNotifier::send('notify-note', $user->email, $user->nick, [
                'number' => $id,
                'url' => Tools::siteUrl() . '/EditCrmNota?code=' . $id
            ]);

            // con enviar a un solo usuario ya damos por notificado el aviso
            if ($sending) {
                $result = true;
                self::echo("\n-" . Tools::trans('notify-crm-success', ['%nick%' => $user->nick]));
                continue;
            }

            self::echo("\n-" . Tools::trans('notify-crm-error', ['%nick%' => $user->nick]));
        }

        return $result;
    }
}
