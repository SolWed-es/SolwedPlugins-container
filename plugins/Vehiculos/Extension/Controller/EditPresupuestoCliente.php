<?php
/**
 * Extension for EditPresupuestoCliente controller
 * Adds vehicle list link that saves data and redirects to client vehicles
 */

namespace FacturaScripts\Plugins\Vehiculos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;

class EditPresupuestoCliente
{
    public function execPreviousAction(): Closure
    {
        return function (string $action): bool {
            if ($action === 'view-client-vehicles') {
                $main = $this->getMainViewName();
                $model = $this->views[$main]->model;

                if (!empty($model->codcliente)) {
                    try {
                        // Guardar el documento actual
                        if ($model->save()) {
                            Tools::log()->notice('vehicle-saved');
                        }

                        // Redireccionar a lista de vehículos del cliente
                        $url = 'ListVehiculo?activetab=List&codcliente=' . urlencode($model->codcliente);
                        $this->redirect($url);
                        return false;
                    } catch (\Throwable $e) {
                        Tools::log()->error('Error: ' . $e->getMessage());
                    }
                } else {
                    Tools::log()->warning('select-customer-first');
                }
            }
            return true;
        };
    }
}
