<?php
/**
 * Extension for EditAlbaranCliente controller
 * Adds vehicle list link that saves data and redirects to client vehicles
 */

namespace FacturaScripts\Plugins\Vehiculos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;

class EditAlbaranCliente
{
    private static array $vehicleCache = [];

    public function execPreviousAction(): Closure
    {
        return function(string $action): bool {
            if ($action === 'view-client-vehicles') {
                return $this->handleViewClientVehicles();
            }
            return true;
        };
    }

    public function execAfterAction(): Closure
    {
        return function(string $action): bool {
            $main = $this->getMainViewName();
            if ($main !== 'EditAlbaranCliente') {
                return true;
            }
            $this->addVehicleListLinkScript();
            return true;
        };
    }

    private function handleViewClientVehicles(): bool
    {
        $main = $this->getMainViewName();
        $model = $this->views[$main]->model;

        if (!empty($model->codcliente)) {
            try {
                // Guardar el documento actual
                if ($model->save()) {
                    $this->toolBox()->i18nLog()->notice('vehicle-saved');
                }

                // Redireccionar a lista de vehículos del cliente
                $url = 'ListVehiculo?activetab=List&codcliente=' . urlencode($model->codcliente);
                $this->redirect($url);
                return false;
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
            if ($viewName !== 'EditAlbaranCliente') {
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

    private function addVehicleListLinkScript(): void
    {
        $script = "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const allLabels = document.querySelectorAll('label');
            let foundLabels = [];

            allLabels.forEach(label => {
                const text = label.textContent.toLowerCase();
                if (text.includes('vehículo') || text.includes('vehiculo') || text.includes('máquina') || text.includes('maquina')) {
                    foundLabels.push(label);
                }
            });

            foundLabels.forEach(function(label) {
                label.style.cursor = 'pointer';
                label.style.color = '#0d6efd';
                label.style.textDecoration = 'underline';
                label.style.fontWeight = 'bold';
                label.title = 'Haz clic para ver los vehículos del cliente';

                label.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    handleViewVehicles();
                });
            });

            function handleViewVehicles() {
                let codcliente = '';
                const selectors = [
                    'input[name=\"codcliente\"]',
                    'select[name=\"codcliente\"]',
                    'input[data-field=\"codcliente\"]',
                    'select[data-field=\"codcliente\"]'
                ];

                for (let selector of selectors) {
                    const element = document.querySelector(selector);
                    if (element && element.value) {
                        codcliente = element.value;
                        break;
                    }
                }

                if (!codcliente) {
                    alert('Debe seleccionar un cliente primero');
                    return;
                }

                const form = document.querySelector('form');
                if (form) {
                    const existingAction = form.querySelector('input[name=\"action\"]');
                    if (existingAction) {
                        existingAction.remove();
                    }

                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'view-client-vehicles';
                    form.appendChild(actionInput);

                    form.submit();
                }
            }
        });
        </script>";

        $this->assets()->addHtml('vehicle-list-link', $script);
    }
}
