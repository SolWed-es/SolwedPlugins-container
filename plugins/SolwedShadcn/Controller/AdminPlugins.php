<?php
/**
 * AdminPlugins controller override — desactiva update checks
 */

namespace FacturaScripts\Plugins\SolwedShadcn\Controller;

use FacturaScripts\Core\Response;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Dinamic\Model\User;

class AdminPlugins extends \FacturaScripts\Core\Controller\AdminPlugins
{
    public function getMaxFileUpload(): float
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->updated = true;
    }
}
