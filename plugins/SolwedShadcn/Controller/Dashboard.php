<?php
/**
 * Dashboard controller override — desactiva update checks
 */

namespace FacturaScripts\Plugins\SolwedShadcn\Controller;

use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\User;

class Dashboard extends \FacturaScripts\Core\Controller\Dashboard
{
    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);
        $this->updated = true;
    }
}
