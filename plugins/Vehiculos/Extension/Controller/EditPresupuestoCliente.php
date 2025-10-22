<?php
/**
 * Extension for EditPresupuestoCliente controller 
 * Adds smart vehicle creation link that saves data and pre-selects customer
 */

namespace FacturaScripts\Plugins\Vehiculos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;

class EditPresupuestoCliente
{
    private static array $vehicleCache = [];

    public function execPreviousAction(): Closure
    {
        return function(string $action): bool {
            if ($action === 'create-vehicle-redirect') {
                return $this->handleCreateVehicleRedirect();
            }
            return true;
        };
    }

    public function execAfterAction(): Closure
    {
        return function(string $action): bool {
            $main = $this->getMainViewName();
            if ($main !== 'EditPresupuestoCliente') {
                return true;
            }
            $this->addSmartVehicleLinkScript();
            return true;
        };
    }

    private function handleCreateVehicleRedirect(): bool
    {
        $main = $this->getMainViewName();
        $model = $this->views[$main]->model;
        
        if (!empty($model->codcliente)) {
            try {
                if ($model->save()) {
                    $this->toolBox()->i18nLog()->notice('vehicle-saved');
                    $url = 'EditVehiculo?codcliente=' . urlencode($model->codcliente) . '&return=' . urlencode($this->request->getUri());
                    $this->redirect($url);
                    return false;
                } else {
                    $this->toolBox()->i18nLog()->error('vehicle-save-error');
                }
            } catch (\Throwable $e) {
                $this->toolBox()->i18nLog()->error('Error: ' . $e->getMessage());
            }
        } else {
            $this->toolBox()->i18nLog()->warning('select-customer-first');
        }
        return true;
    }

    public function loadData(): Closure
    {
        return function(string $viewName, $view): void {
            if ($viewName !== 'EditPresupuestoCliente') {
                return;
            }

            $model = $view->model;
            $codcliente = $model->codcliente ?? '';
            
            if ($codcliente === '') {
                return;
            }

            $this->loadVehicleOptions($view, $codcliente);
        };
    }

    private function loadVehicleOptions($view, string $codcliente): void
    {
        $cacheKey = $codcliente;
        
        if (!isset(self::$vehicleCache[$cacheKey])) {
            $i18n = $this->toolBox()->i18n();
            $vehicleOptions = ['' => '-- ' . $i18n->trans('select') . ' --'];
            
            try {
                $vehicleModel = new Vehiculo();
                $where = [new DataBaseWhere('codcliente', $codcliente)];
                // Usar sólo campos que existen en la tabla
                $vehicles = $vehicleModel->all($where, ['matricula' => 'ASC', 'marca' => 'ASC', 'modelo' => 'ASC'], 0, 0);
                
                foreach ($vehicles as $veh) {
                    $display = method_exists($veh, 'getDisplayInfo') ? $veh->getDisplayInfo() : ($veh->modelo ?? '');
                    $vehicleOptions[$veh->idmaquina] = $display;
                }
            } catch (\Throwable $th) {
                \FacturaScripts\Core\Tools::log()->warning('Error cargando vehículos: ' . $th->getMessage());
            }
            
            self::$vehicleCache[$cacheKey] = $vehicleOptions;
        }

        $options = self::$vehicleCache[$cacheKey];

        foreach ($view->getColumns() as $group) {
            foreach ($group->columns as $col) {
                if (isset($col->widget) && $col->widget->fieldname === 'idmaquina') {
                    $col->widget->setValuesFromArray($options, false);
                    break 2;
                }
            }
        }
    }

    private function addSmartVehicleLinkScript(): void
    {
        $script = "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const vehicleLabels = document.querySelectorAll('label[for*=\"idmaquina\"], .form-label');
            
            vehicleLabels.forEach(function(label) {
                if (label.textContent.toLowerCase().includes('vehículo') || 
                    label.textContent.toLowerCase().includes('vehiculo')) {
                    
                    label.style.cursor = 'pointer';
                    label.style.color = '#0d6efd';
                    label.style.textDecoration = 'underline';
                    label.title = 'Haz clic para crear un nuevo vehículo';
                    
                    label.addEventListener('click', function(e) {
                        e.preventDefault();
                        handleVehicleCreation();
                    });
                }
            });
            
            function handleVehicleCreation() {
                const codcliente = document.querySelector('input[name=\"codcliente\"]')?.value;
                
                if (!codcliente) {
                    alert('Debe seleccionar un cliente primero');
                    return;
                }
                
                const form = document.querySelector('form');
                if (form) {
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'create-vehicle-redirect';
                    form.appendChild(actionInput);
                    
                    form.submit();
                }
            }
        });
        </script>";

        $this->assets()->addHtml('smart-vehicle-link', $script);
    }
}