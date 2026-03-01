<?php

namespace FacturaScripts\Plugins\BackupSetting;

use FacturaScripts\Plugins\Backup\Cron as BackupCron;
use FacturaScripts\Core\Tools;

class Cron extends BackupCron
{
    public function run(): void
    {
        $frequency = Config::getFrequency();
        $interval = $this->mapInterval($frequency);

        // Registramos el mismo job name que el plugin Backup para sustituir su planificación
        $this->job(self::JOB_NAME)
            ->every($interval)
            ->run(function () {
                // Reutilizamos la lógica del plugin Backup sin duplicar código
                $this->createBackup();
            });

        // Log opcional para facilitar diagnóstico
        Tools::log(self::JOB_NAME)->debug('backupsetting-frequency', [
            '%frequency%' => $frequency,
            '%interval%' => $interval,
        ]);
    }

    private function mapInterval(string $freq): string
    {
        switch ($freq) {
            case 'daily':
                return '1 day';
            case 'monthly':
                // Si tu scheduler no soporta "1 month", puedes usar "4 weeks"
                return '1 month';
            case 'weekly':
            default:
                return '1 week';
        }
    }
}