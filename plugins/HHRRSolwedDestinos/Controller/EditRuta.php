<?php
/**
 * This file is part of HHRRSolwedDestinos plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedDestinos Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedDestinos\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\HHRRSolwedDestinos\Model\EmployeeRoute;
use FacturaScripts\Plugins\HHRRSolwedDestinos\Model\Ruta;
use FacturaScripts\Plugins\HumanResources\Model\Employee;

/**
 * Controller to edit Ruta.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditRuta extends EditController
{
    private const VIEW_RUTA_DESTINOS = 'ListRutaDestino';
    private const VIEW_EMPLEADOS = 'ListEmpleadosRuta';

    /**
    * Returns the model name
    */
    public function getModelClassName(): string
    {
        return 'Ruta';
    }

    /**
    * Returns basic page attributes
    *
    * @return array
    */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Rutas';
        $pagedata['icon'] = 'fas fa-route';
        $pagedata['menu'] = 'rrhh';
        $pagedata['ordernum'] = 110;
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
    * Create views to display.
    */
    protected function createViews()
    {
        // Vista principal para editar la ruta
        parent::createViews();
        
        // Pestaña para gestionar destinos asignados a la ruta
        $this->createViewRutaDestinos();
        
        // Pestaña para gestionar empleados asignados a la ruta
        $this->createViewEmpleados();
        
        // Configurar posición de las pestañas
        $this->setTabsPosition('bottom');
    }

    /**
    * Create view to manage route destinations
    */
    protected function createViewRutaDestinos()
    {
        $view = $this->addEditListView(self::VIEW_RUTA_DESTINOS, 'RutaDestino', 'destinations', 'fas fa-map-marker-alt');
        $view->setInLine(true);

        // Permitir reordenar arrastrando
        $this->setSettings(self::VIEW_RUTA_DESTINOS, 'sortable', true);
    }

    /**
     * Create view for employees with route assignments
     */
    protected function createViewEmpleados()
    {
        // Crear la vista como una vista HTML simple en lugar de ListView
        $this->addHtmlView(self::VIEW_EMPLEADOS, 'Tab/EmpleadosRuta', 'EmployeeRoute', 'employees', 'fas fa-users');
    }

    /**
     * Execute actions before loading data
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->request->get('action', $this->request->query->get('action'));

        switch ($action) {
            case 'assign-ruta':
                $this->assignRutaAction();
                break;

            case 'unassign-ruta':
                $this->unassignRutaAction();
                break;

            case 'load-rutas-data':
                $this->loadRutasDataAction();
                break;

            case 'get-employees':
                $this->getEmployeesAction();
                break;
        }
    }

    /**
     * Load view data procedure
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();

        // CORREGIR: Obtener el ID correcto según el modelo
        $primaryKey = $this->views[$mainViewName]->model->primaryColumn();
        $idruta = $this->getViewModelValue($mainViewName, $primaryKey);

        if ($viewName === $mainViewName) {
            parent::loadData($viewName, $view);
            return;
        }

        switch ($viewName) {
            case self::VIEW_RUTA_DESTINOS:
                $where = [new DataBaseWhere('idruta', $idruta)];
                $view->loadData('', $where, ['orden' => 'ASC']);

                if (!empty($idruta)) {
                    $view->model->idruta = $idruta;
                    if (empty($view->model->orden) || $view->model->orden == 1) {
                        $view->model->orden = \FacturaScripts\Plugins\HHRRSolwedDestinos\Model\RutaDestino::getNextOrder($idruta);
                    }
                }
                break;

            case self::VIEW_EMPLEADOS:
                // Pasar el ID de ruta a la vista HTML
                $view->model->idruta = $idruta;
                break;
        }
    }

        /**
     * Assign route to employee
     */
    private function assignRutaAction()
    {
        // Limpiar cualquier salida previa
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            $employeeId = $this->request->request->get('idemployee');
            $routeId = $this->request->request->get('idruta');

            if (empty($employeeId) || empty($routeId)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Missing employee or route']);
                exit;
            }

            // Verificar empleado
            $employee = new Employee();
            if (!$employee->loadFromCode($employeeId)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Employee not found']);
                exit;
            }

            // Verificar si ya está asignado
            $existing = new EmployeeRoute();
            $where = [
                new DataBaseWhere('idemployee', $employeeId),
                new DataBaseWhere('idruta', $routeId)
            ];
            if ($existing->loadFromCode('', $where)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Employee already assigned to this route']);
                exit;
            }

            // Crear nueva asignación
            $employeeRoute = new EmployeeRoute();
            $employeeRoute->idemployee = $employee->id;
            $employeeRoute->idruta = $routeId;
            $employeeRoute->fecha_asignacion = date('Y-m-d');
            $employeeRoute->activo = true;

            if ($employeeRoute->save()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Employee assigned successfully']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Error saving assignment']);
            }
            exit;

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Unassign route from employee
     */
    private function unassignRutaAction()
    {
        // Limpiar cualquier salida previa
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            $assignmentId = $this->request->request->get('id');

            $employeeRoute = new EmployeeRoute();
            if ($employeeRoute->loadFromCode($assignmentId)) {
                if ($employeeRoute->delete()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Error deleting']);
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Assignment not found']);
            }
            exit;

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

     /**
    * Load route assignments data for AJAX
    */
    private function loadRutasDataAction()
    {
        // Limpiar cualquier salida previa
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            $routeId = $this->request->request->get('idruta');

            if (empty($routeId)) {
                header('Content-Type: application/json');
                echo json_encode([]);
                exit;
            }

            $employeeRoute = new EmployeeRoute();
            $assignments = $employeeRoute->all([
                new DataBaseWhere('idruta', $routeId)
            ], ['fecha_asignacion' => 'DESC']);

            $data = [];
            foreach ($assignments as $assignment) {
                $employee = new Employee();
                if ($employee->loadFromCode($assignment->idemployee)) {
                    $contact = $employee->getContact();
                    $employeeName = 'Unknown Employee';
                    $employeeCode = '';

                    if ($contact && property_exists($contact, 'nombre')) {
                        $employeeName = trim($contact->nombre . ' ' . ($contact->apellidos ?? ''));
                        $employeeCode = $contact->codigo ?? '';
                    }

                    $data[] = [
                        'id' => $assignment->id,
                        'idemployee' => $assignment->idemployee,
                        'nombre' => $employeeName,
                        'codigo' => $employeeCode,
                        'fecha' => $assignment->fecha_asignacion,
                        'notas' => $assignment->notas,
                        'activo' => $assignment->activo
                    ];
                }
            }

            header('Content-Type: application/json');
            echo json_encode($data);
            exit;

        } catch (\Exception $e) {
            $this->toolBox()->log()->error('HHRRSolwedDestinos: Error in load-rutas-data action: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Get employees for select dropdown
     */
    private function getEmployeesAction()
    {
        // Limpiar cualquier salida previa
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            $employee = new Employee();
            $employees = $employee->all([], [], 0, 100);

            $results = [];
            foreach ($employees as $emp) {
                $contact = $emp->getContact();
                $fullName = 'Unknown Employee';
                $employeeCode = '';

                if ($contact && property_exists($contact, 'nombre')) {
                    $fullName = trim($contact->nombre . ' ' . ($contact->apellidos ?? ''));
                    $employeeCode = $contact->codigo ?? '';
                }

                $results[] = [
                    'id' => $emp->id,
                    'label' => $fullName . ($employeeCode ? ' (' . $employeeCode . ')' : ''),
                    'value' => $fullName
                ];
            }

            // Ordenar por nombre
            usort($results, function($a, $b) {
                return strcmp($a['value'], $b['value']);
            });

            header('Content-Type: application/json');
            echo json_encode($results);
            exit;

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
    }
}