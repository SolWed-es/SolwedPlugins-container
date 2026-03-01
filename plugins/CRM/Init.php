<?php
/**
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Plugins\CRM\Model\CrmFuente;
use FacturaScripts\Plugins\CRM\Model\CrmInteres;
use FacturaScripts\Plugins\CRM\Model\CrmNota;
use FacturaScripts\Plugins\CRM\Model\CrmOportunidadEstado;
use FacturaScripts\Plugins\CRM\Model\CrmPosition;

/**
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class Init extends InitClass
{
    public function init(): void
    {
        $this->loadExtension(new Extension\Controller\EditAgente());
        $this->loadExtension(new Extension\Controller\EditCliente());
        $this->loadExtension(new Extension\Controller\EditCrmNota());
        $this->loadExtension(new Extension\Controller\EditPresupuestoCliente());
        $this->loadExtension(new Extension\Controller\EditProveedor());
        $this->loadExtension(new Extension\Controller\EditServicioAT());
        $this->loadExtension(new Extension\Model\CrmNota());
        $this->loadExtension(new Extension\Model\LineaPresupuestoCliente());
        $this->loadExtension(new Extension\Model\PresupuestoCliente());
        $this->loadExtension(new Extension\Model\ServicioAT());
    }

    public function uninstall(): void
    {
    }

    public function update(): void
    {
        // forzamos la creación de las tablas
        new CrmFuente();
        new CrmInteres();
        new CrmOportunidadEstado();
        new CrmPosition();

        $this->updateEmailNotifications();
        $this->updateServiceNotes();
    }

    private function updateEmailNotifications(): void
    {
        $i18n = Tools::lang();
        $notificationModel = new EmailNotification();
        $keys = [
            'new-contact-agent', 'notify-note', 'new-opportunity-agent', 'new-opportunity-assignee',
            'new-opportunity-file', 'new-opportunity-note'
        ];
        foreach ($keys as $key) {
            if ($notificationModel->loadFromCode($key)) {
                continue;
            }

            $notificationModel->name = $key;
            $notificationModel->body = $i18n->trans($key . '-body');
            $notificationModel->subject = $i18n->trans($key);
            $notificationModel->enabled = false;
            $notificationModel->save();
        }
    }

    private function updateServiceNotes(): void
    {
        // si la tabla serviciosat no existe, saltamos
        $db = new DataBase();
        if (false === $db->tableExists('serviciosat')) {
            return;
        }

        // recorremos todas las notas sin contacto vinculadas a servicios
        $where = [
            new DataBaseWhere('idcontacto', null, 'IS'),
            new DataBaseWhere('tipodocumento', 'servicio de cliente')
        ];
        foreach (CrmNota::all($where, [], 0, 0) as $note) {
            // obtenemos el codcliente del servicio
            $sql = 'SELECT codcliente FROM serviciosat WHERE idservicio = ' . $db->var2str($note->iddocumento);
            foreach ($db->select($sql) as $row) {
                // cargamos el cliente
                $cliente = new Cliente();
                if (false === $cliente->loadFromCode($row['codcliente'])) {
                    continue;
                }

                // vinculamos la nota al cliente
                $note->idcontacto = $cliente->idcontactofact;
                $note->save();
            }
        }
    }
}
