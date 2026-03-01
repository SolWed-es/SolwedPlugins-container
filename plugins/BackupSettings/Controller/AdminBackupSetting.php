<?php

namespace FacturaScripts\Plugins\BackupSetting\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Plugins\BackupSetting\Config;

class AdminBackupSetting extends Controller
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData["title"] = "Backup Setting";
        $pageData["menu"] = "admin";
        $pageData["icon"] = "fas fa-cogs";
        $pageData['showonmenu'] = true;
        $pageData['ordernum'] = 90;
        return $pageData;
    }

    /**
     * Frecuencia actual (daily | weekly | monthly).
     */
    public function getFrequency(): string
    {
        return Config::getFrequency();
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $freq = (string) ($_POST['frequency'] ?? '');
            if (Config::saveFrequency($freq)) {
                // Mensajería opcional
                // $this->miniLog('success', 'Frecuencia de backup guardada.');
            } else {
                // $this->miniLog('danger', 'No fue posible guardar la frecuencia.');
            }
        }

        $this->setTemplate('AdminBackupSetting');
    }
}
