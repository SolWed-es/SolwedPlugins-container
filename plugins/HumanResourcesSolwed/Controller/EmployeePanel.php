<?php
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Attendance;
use FacturaScripts\Dinamic\Model\EmployeeHoliday;
use FacturaScripts\Plugins\HumanResources\Controller\EmployeePanel as ParentEmployeePanel;
use FacturaScripts\Plugins\HumanResources\Lib\DateTimeTools;
use FacturaScripts\Plugins\HumanResourcesSolwed\Model\Shift;
use FacturaScripts\Plugins\HumanResourcesSolwed\Model\EmployeeShift;

class EmployeePanel extends ParentEmployeePanel
{
    /** @var array Turnos auto-asignables */
    public array $shifts = [];

    /** @var EmployeeShift|null Asignación actual del empleado */
    public ?EmployeeShift $currentShift = null;

    /** @var array Proyectos disponibles (si el plugin Proyectos está instalado) */
    public array $proyectos = [];
    /** @var array<string, array{items: EmployeeHoliday[], totals: array{total:int,enjoyed:int,pending:int}}> */
    public array $holidayGroups = [];

    /** @var string */
    public string $holidayOrder = 'desc';

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        if (empty($this->employee?->id)) {
            return;
        }

        $this->hydrateHolidayGroups();
        $this->loadAutoAssignableShifts();
        $this->loadCurrentEmployeeShift();
        $this->loadProyectos();
    }

    protected function execPreviousAction(?string $action): bool
    {
        // Validar token antes de procesar cualquier acción
        $idemployee = (int)$this->request->get('idemployee') ?? 0;
        if (empty($action)
            || $this->employee->id !== $idemployee
            || false === $this->validateFormToken()
        ) {
            return true;
        }

        // Guardar turno por defecto del empleado
        if ($action === 'set-default-shift') {
            return $this->execSetDefaultShift();
        }

        if ($action === 'insert-attendance') {
            return $this->execInsertAttendanceWithLocalization();
        } elseif ($action === 'insert-holidays') {
            return $this->execInsertHolidaysWithStatus();
        } else {
            return parent::execPreviousAction($action);
        }
    }

    /**
     * Carga los proyectos disponibles si el plugin Proyectos está instalado.
     */
    private function loadProyectos(): void
    {
        if (!class_exists('\\FacturaScripts\\Plugins\\Proyectos\\Model\\Proyecto')) {
            return;
        }

        try {
            $proyecto = new \FacturaScripts\Plugins\Proyectos\Model\Proyecto();
            $this->proyectos = $proyecto->all([], ['nombre' => 'ASC'], 0, 0);
        } catch (\Exception $e) {
            Tools::log()->error('HumanResourcesSolwed: Error al cargar proyectos: ' . $e->getMessage());
            $this->proyectos = [];
        }
    }

    private function execSetDefaultShift(): bool
    {
        $idemployee = (int)($this->employee->id ?? 0);
        $idshift    = (int)$this->request->request->get('idturno', 0);

        if ($idemployee <= 0 || $idshift <= 0) {
            $this->toolBox()->i18nLog()->warning('select-shift');
            return false;
        }

        try {
            $vEmp   = $this->dataBase->var2str($idemployee);
            $vShift = $this->dataBase->var2str($idshift);
            $vNick  = $this->dataBase->var2str($this->user->nick ?? 'system');
            $vToday = $this->dataBase->var2str(date('Y-m-d'));
            $vNow   = $this->dataBase->var2str(date('Y-m-d H:i:s'));

            // Desmarcar default previo
            $this->dataBase->exec('UPDATE rrhh_employeesshifts SET isdefault = 0 WHERE idemployee = ' . $vEmp);

            // ¿Existe (empleado, turno)?
            $row = $this->dataBase->select('SELECT id FROM rrhh_employeesshifts WHERE idemployee = ' . $vEmp . ' AND idshift = ' . $vShift . ' LIMIT 1');

            if (empty($row)) {
                $this->dataBase->exec('INSERT INTO rrhh_employeesshifts
                    (idemployee, idshift, assignment_date, active, autoassignable, isdefault, creation_date, last_update, nick, last_nick)
                    VALUES (' . $vEmp . ', ' . $vShift . ', ' . $vToday . ', 1, 1, 1, ' . $vNow . ', ' . $vNow . ', ' . $vNick . ', ' . $vNick . ')');
            } else {
                $vId = $this->dataBase->var2str((int)$row[0]['id']);
                $this->dataBase->exec('UPDATE rrhh_employeesshifts SET isdefault = 1, active = 1, last_update = ' . $vNow . ', last_nick = ' . $vNick . ' WHERE id = ' . $vId);
            }

            $this->defaultShift = (object)['idshift' => $idshift];
            $this->toolBox()->i18nLog()->notice('saved-correctly');
            return true;
        } catch (\Throwable $e) {
            $this->toolBox()->log()->error('[HumanResourcesSolwed] set-default-shift: ' . $e->getMessage());
            $this->toolBox()->i18nLog()->warning('save-error');
            return false;
        }
    }

    /**
     * Carga los turnos marcados como auto-asignables
     */
    private function loadAutoAssignableShifts(): void
    {
        try {
            $shift = new Shift();
            $where = [new DataBaseWhere('autoassignable', true)];
            $this->shifts = $shift->all($where, [], 0, 0);
        } catch (\Exception $e) {
            Tools::log()->error('Error al cargar turnos auto-asignables: ' . $e->getMessage());
            $this->shifts = [];
        }
    }

    /**
     * Carga la asignación de turno actual del empleado
     */
    private function loadCurrentEmployeeShift(): void
    {
        if (!$this->employee || !$this->employee->id) {
            return;
        }

        $employeeShift = new EmployeeShift();
        $where = [
            new DataBaseWhere('idemployee', $this->employee->id),
            new DataBaseWhere('active', true),
            new DataBaseWhere('start_date', date('Y-m-d'), '<='),
            new DataBaseWhere('end_date', date('Y-m-d'), '>=')
        ];

        $assignments = $employeeShift->all($where, [], 0, 1);
        $this->currentShift = $assignments[0] ?? null;
    }

    /**
     * Devuelve el turno por defecto del empleado
     */
    public function getDefaultShift(): ?EmployeeShift
    {
        if (!$this->employee || !$this->employee->id) {
            return null;
        }

        $employeeShift = new EmployeeShift();
        $where = [
            new DataBaseWhere('idemployee', $this->employee->id),
            new DataBaseWhere('isdefault', true),
            new DataBaseWhere('active', true)
        ];

        $assignments = $employeeShift->all($where, [], 0, 1);
        return $assignments[0] ?? null;
    }

    private function execInsertAttendanceWithLocalization(): bool
    {
        Tools::log()->debug('Iniciando execInsertAttendanceWithLocalization en HumanResourcesSolwed');

        $data = $this->request->request->all();
        if (DateTimeTools::greaterCurrentDateTime($data['date'], $data['time'])) {
            Tools::log()->notice('date-must-be-less');
            return true;
        }

        $adjust = ($data['adjust'] === 'true');
        $attendance = new Attendance();
        $attendance->idemployee = (int)$data['idemployee'];
        $attendance->origin = (int)$data['origin'];
        $attendance->kind = (int)$data['kind'];
        $attendance->location = $data['location'] ?? '';
        $attendance->localizacion = $data['localizacion'] ?? '';
        $idproyecto = (int)($data['idproyecto'] ?? 0);
        if ($idproyecto > 0) {
            $attendance->idproyecto = $idproyecto;
        }
        if ($attendance->origin == Attendance::ORIGIN_MANUAL) {
            $attendance->authorized = false;
            $attendance->checkdate = $data['date'];
            $attendance->checktime = $data['time'];
            $attendance->note = $data['note'];
        }
        $attendance->setAdjustToWordPeriod($adjust);

        if ($attendance->save()) {
            Tools::log()->debug('Asistencia guardada correctamente con ID: ' . $attendance->id);
            return true;
        } else {
            Tools::log()->error('Error al guardar la asistencia');
            return false;
        }
    }

    private function execInsertHolidaysWithStatus(): bool
    {
        $data = $this->request->request->all();
        if (DateTimeTools::dateLessThan($data['startdate'])) {
            Tools::log()->notice('date-must-be-greater');
            return false;
        }

        $holidays = new EmployeeHoliday();
        $holidays->idemployee = $data['idemployee'];
        $holidays->startdate = $data['startdate'];
        $holidays->enddate = $data['enddate'];
        $holidays->note = $data['notes'];
        $applyYear = isset($data['applyto']) ? (int)$data['applyto'] : null;
        if (empty($applyYear) && !empty($holidays->startdate)) {
            $applyYear = (int)date('Y', strtotime($holidays->startdate));
        }
        if (!empty($applyYear)) {
            $holidays->applyto = $applyYear;
        }
        $holidays->holidaystatus = 'Solicitadas';
        
        // Retornar el resultado de la operación de guardado
        if ($holidays->save()) {
            Tools::log()->debug('Vacaciones guardadas correctamente con ID: ' . $holidays->id);
            return true;
        } else {
            Tools::log()->error('Error al guardar las vacaciones');
            return false;
        }
    }

    private function hydrateHolidayGroups(): void
    {
        $requestedOrder = strtolower((string)$this->request->query->get('holidayOrder', $this->request->input('holidayOrder', 'desc')));
        $this->holidayOrder = in_array($requestedOrder, ['asc', 'desc'], true) ? $requestedOrder : 'desc';

        $where = [ new DataBaseWhere('idemployee', $this->employee->id) ];
        $order = ['startdate' => strtoupper($this->holidayOrder)];
        $records = EmployeeHoliday::all($where, $order);

        $grouped = [];
        foreach ($records as $holiday) {
            $year = $this->resolveHolidayYear($holiday);
            $holiday->year_group = $year;

            if (false === isset($grouped[$year])) {
                $grouped[$year] = [
                    'items' => [],
                    'totals' => ['total' => 0, 'enjoyed' => 0, 'pending' => 0],
                ];
            }

            $grouped[$year]['items'][] = $holiday;
            $grouped[$year]['totals']['total'] += (int)$holiday->totaldays;
            if ($holiday->canDelete) {
                $grouped[$year]['totals']['pending'] += (int)$holiday->totaldays;
            } else {
                $grouped[$year]['totals']['enjoyed'] += (int)$holiday->totaldays;
            }
        }

        krsort($grouped, SORT_NUMERIC);
        $this->holidayGroups = $grouped;
    }

    private function resolveHolidayYear(EmployeeHoliday $holiday): int
    {
        if (!empty($holiday->applyto)) {
            return (int)$holiday->applyto;
        }

        if (!empty($holiday->startdate)) {
            return (int)date('Y', strtotime($holiday->startdate));
        }

        return (int)date('Y');
    }
}
