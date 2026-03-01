<?php
/**
 * Informe de horas por proyecto y empleado.
 */

namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\HumanResourcesSolwed\Lib\ProjectHoursCalculator;

class ListProjectHours extends ListController
{
    /** @var array|null */
    private ?array $cachedHours = null;

    /** @var array|null */
    private ?array $cachedIncidences = null;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'project-hours';
        $data['icon'] = 'fa-solid fa-clock';
        $data['menu'] = 'rrhh';
        return $data;
    }

    /**
     * @throws Exception
     */
    protected function createViews()
    {
        $this->createProjectHoursView();
        $this->createIncidencesView();
    }

    /**
     * @param string $viewName
     * @param ListView $view
     * @throws Exception
     */
    protected function loadData($viewName, $view)
    {
        // Solo procesar las vistas de este controlador
        if ($viewName !== 'ListProjectHours' && $viewName !== 'ListProjectIncidences') {
            return;
        }

        [$hours, $incidences] = $this->computeOnce();

        if ($viewName === 'ListProjectHours') {
            $cursor = [];
            foreach ($hours as $row) {
                $cursor[] = (object)$row;
            }

            if (empty($cursor)) {
                Tools::log()->warning('hrproyectos-hours-empty', $this->currentFilters());
            }

            $view->cursor = $cursor;
            $view->count = count($cursor);
            $view->offset = 0;
            return;
        }

        if ($viewName === 'ListProjectIncidences') {
            if (empty($incidences)) {
                Tools::log()->warning('hrproyectos-hours-empty', $this->currentFilters());
            }

            // $incidences ya contiene objetos IncidenceRow con método url()
            $view->cursor = $incidences;
            $view->count = count($incidences);
            $view->offset = 0;
            return;
        }
    }

    /**
     * Crea la vista principal usando ListView directamente (sin tabla física).
     *
     * @throws Exception
     */
    private function createProjectHoursView(): void
    {
        // Modelo ficticio para evitar errores; el cursor se rellenará manualmente.
        $view = new ListView('ListProjectHours', 'project-hours', \stdClass::class, 'fa-solid fa-clock');
        $view->setSettings('btnNew', false)
            ->setSettings('btnDelete', false)
            ->setSettings('btnPrint', false)
            ->setSettings('saveFilters', true)
            ->setSettings('megasearch', false)
            ->setSettings('card', true);

        $this->addCustomView('ListProjectHours', $view);

        // Filtros: fecha desde/hasta
        $this->addFilterDatePicker('ListProjectHours', 'startdate', 'start-date', 'checkdate', '>=');
        $this->addFilterDatePicker('ListProjectHours', 'enddate', 'end-date', 'checkdate', '<=');

        // Filtro proyecto
        $projectValues = $this->codeModel->all('proyectos', 'idproyecto', 'nombre');
        $this->addFilterSelect('ListProjectHours', 'idproyecto', 'project', 'idproyecto', $projectValues);

        // Filtro empleado
        $employeeValues = $this->codeModel->all('rrhh_employees', 'id', 'nombre');
        $this->addFilterSelect('ListProjectHours', 'idemployee', 'employee', 'idemployee', $employeeValues);

        // Orden por proyecto y empleado
        $this->addOrderBy('ListProjectHours', ['project_code', 'employee_name'], 'project', 1);
    }

    /**
     * Vista de incidencias con los mismos filtros.
     */
    private function createIncidencesView(): void
    {
        $view = new ListView('ListProjectIncidences', 'project-incidences', \stdClass::class, 'fa-solid fa-triangle-exclamation');
        $view->setSettings('btnNew', false)
            ->setSettings('btnDelete', false)
            ->setSettings('btnPrint', false)
            ->setSettings('saveFilters', true)
            ->setSettings('megasearch', false)
            ->setSettings('checkBoxes', false)
            ->setSettings('clickable', true)
            ->setSettings('card', true);

        $this->addCustomView('ListProjectIncidences', $view);

        // Filtros idénticos (comparten request)
        $this->addFilterDatePicker('ListProjectIncidences', 'startdate', 'start-date', 'checkdate', '>=');
        $this->addFilterDatePicker('ListProjectIncidences', 'enddate', 'end-date', 'checkdate', '<=');

        $projectValues = $this->codeModel->all('proyectos', 'idproyecto', 'nombre');
        $this->addFilterSelect('ListProjectIncidences', 'idproyecto', 'project', 'idproyecto', $projectValues);

        $employeeValues = $this->codeModel->all('rrhh_employees', 'id', 'nombre');
        $this->addFilterSelect('ListProjectIncidences', 'idemployee', 'employee', 'idemployee', $employeeValues);

        $this->addOrderBy('ListProjectIncidences', ['type', 'employee_name'], 'type', 1);
    }

    /**
     * Calcula horas + incidencias una sola vez por request.
     *
     * @return array{array,array}
     */
    private function computeOnce(): array
    {
        if ($this->cachedHours !== null && $this->cachedIncidences !== null) {
            return [$this->cachedHours, $this->cachedIncidences];
        }

        $filters = $this->currentFilters();
        [$hours, $incidences] = ProjectHoursCalculator::computeWithIncidences(
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            $filters['idproyecto'] ?? null,
            $filters['idemployee'] ?? null
        );

        $this->cachedHours = $hours;
        $this->cachedIncidences = $incidences;

        return [$hours, $incidences];
    }

    /**
     * Devuelve filtros normalizados desde el request.
     *
     * @return array
     */
    private function currentFilters(): array
    {
        $fromDate = $this->request->queryOrInput('filterstartdate', '') ?: null;
        $toDate = $this->request->queryOrInput('filterenddate', '') ?: null;
        $idProyecto = (int)$this->request->queryOrInput('filteridproyecto', 0);
        $idEmployee = (int)$this->request->queryOrInput('filteridemployee', 0);

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'idproyecto' => $idProyecto > 0 ? $idProyecto : null,
            'idemployee' => $idEmployee > 0 ? $idEmployee : null,
        ];
    }
}
