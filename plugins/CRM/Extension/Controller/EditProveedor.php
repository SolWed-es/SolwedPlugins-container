<?php
/**
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Extension\Controller;

use Closure;

class EditProveedor
{
    protected function execPreviousAction(): Closure
    {
        return function ($action) {
            if ($action === 'show-contact') {
                $idcontacto = $this->request->request->get('code');
                $this->redirect('EditContacto?code=' . $idcontacto);
            }
        };
    }
}