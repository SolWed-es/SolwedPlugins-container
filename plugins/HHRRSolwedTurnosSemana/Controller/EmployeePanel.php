<?php
/**
 * Extended EmployeePanel controller to add shift functionality
 * Replaces the need for Extension\Controller\EmployeePanel
 */
namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\HumanResources\Controller\EmployeePanel as BaseEmployeePanel;
use FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Model\Shift;
use FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Model\EmployeeShift;

class EmployeePanel extends BaseEmployeePanel
{
    /** @var array Turnos auto-asignables */
    public $shifts = [];

    /** @var EmployeeShift|null Asignación actual del empleado */
    public $currentShift = null;


    /**
     * Intercepta acciones del formulario antes de que las procese el padre.
     */
protected function execPreviousAction($action): bool
{
    // ─────────────────────────────────────────────────────────────
    // 1) Guardar turno por defecto del empleado
    // ─────────────────────────────────────────────────────────────
if ('set-default-shift' === $action) {
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
        $vToday = $this->dataBase->var2str(date('Y-m-d'));        // '2025-08-08'
        $vNow   = $this->dataBase->var2str(date('Y-m-d H:i:s'));  // '2025-08-08 10:15:00'

        // 1) Desmarcar default previo
        $sqlClear = 'UPDATE rrhh_employeesshifts SET isdefault = 0 WHERE idemployee = ' . $vEmp;
        $this->dataBase->exec($sqlClear);

        // 2) ¿Existe (empleado, turno)?
        $sqlSel = 'SELECT id FROM rrhh_employeesshifts WHERE idemployee = ' . $vEmp . ' AND idshift = ' . $vShift . ' LIMIT 1';
        $row = $this->dataBase->select($sqlSel);

        if (empty($row)) {
            // 3) Insertar relación como default
            $sqlIns = 'INSERT INTO rrhh_employeesshifts
                (idemployee, idshift, assignment_date, active, autoassignable, isdefault, creation_date, last_update, nick, last_nick)
                VALUES (' . $vEmp . ', ' . $vShift . ', ' . $vToday . ', 1, 1, 1, ' . $vNow . ', ' . $vNow . ', ' . $vNick . ', ' . $vNick . ')';
            $this->dataBase->exec($sqlIns);
        } else {
            // 4) Actualizar existente como default
            $vId = $this->dataBase->var2str((int)$row[0]['id']);
            $sqlUpd = 'UPDATE rrhh_employeesshifts
                       SET isdefault = 1, active = 1, last_update = ' . $vNow . ', last_nick = ' . $vNick . '
                       WHERE id = ' . $vId;
            $this->dataBase->exec($sqlUpd);
        }

        // Refrescar para la vista
        $this->defaultShift = (object)['idshift' => $idshift];
        $this->toolBox()->i18nLog()->notice('saved-correctly');
        return true;
    } catch (\Throwable $e) {
        $this->toolBox()->log()->error('[TurnosSemana] set-default-shift: ' . $e->getMessage());
        $this->toolBox()->i18nLog()->warning('save-error');
        return false;
    }
}



    // ─────────────────────────────────────────────────────────────
    // 2) Insertar asistencia con turno
    // ─────────────────────────────────────────────────────────────
    if ('insert-attendance' === $action) {

        // Normaliza 'kind' por si llegan múltiples valores
        $kindParam = $this->request->request->get('kind', 1);
        if (is_array($kindParam)) {
            $last = end($kindParam);
            $kind = (int) $last;
        } else {
            $kind = (int) $kindParam;
        }
        if (!in_array($kind, [1,2,3,4], true)) { $kind = 1; }

$this->request->request->set('kind', $kind);
        // Leer POST completo (por si tu método lo necesita)
        $post = $this->request->request->all();

        // idturno: usar el del request o el default del empleado
        $idturno = (int)$this->request->request->get('idturno', 0);
        if ($idturno <= 0 && !empty($this->defaultShift) && (int)$this->defaultShift->idshift > 0) {
            $idturno = (int)$this->defaultShift->idshift;
        }
        if ($idturno <= 0) {
            $this->toolBox()->log()->warning('[TurnosSemana] insert-attendance sin idturno.');
            $this->toolBox()->i18nLog()->warning('select-shift');
            return false;
        }

        // kind: respetar el que venga (manual) o el que mande el botón (1=entrada, 2=salida, etc.)
        $kind = (int)$this->request->request->get('kind', 1);
        if (!in_array($kind, [1, 2, 3, 4], true)) { // ajusta al catálogo real si usas otros valores
            $kind = 1;
        }

        // Reinyectar en el request para que tu saveAttendanceWithShift() lo vea,
        // incluso si internamente lee directamente de $this->request->request.
        $this->request->request->set('idturno', $idturno);
        $this->request->request->set('kind', $kind);

        try {
            $ok = $this->saveAttendanceWithShift($post);
            return (bool)$ok;
        } catch (\Throwable $e) {
            $this->toolBox()->log()->error('[TurnosSemana] insert-attendance excepción: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 3) Resto de acciones → controlador padre
    // ─────────────────────────────────────────────────────────────
    return (bool)parent::execPreviousAction($action);
}




    /**
     * Ejecuta la lógica privada del controlador
     */
    public function privateCore(&$response, $user, $permissions): void
    {
        Tools::log()->warning('HHRRSolwedTurnosSemana EmployeePanel: Iniciando privateCore');

        // Llamar al padre para mantener la funcionalidad original
        parent::privateCore($response, $user, $permissions);

        // Cargar turnos auto-asignables
        $this->loadAutoAssignableShifts();

        // Cargar asignación actual del empleado
        $this->loadCurrentEmployeeShift();
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

            Tools::log()->warning(
                'Turnos auto-asignables encontrados: ' . count($this->shifts)
            );
        } catch (\Exception $e) {
            Tools::log()->error(
                'Error al cargar turnos auto-asignables: ' . $e->getMessage()
            );
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
     * Guarda la asistencia con el turno seleccionado
     */
public function saveAttendanceWithShift(array $data): bool
{
    Tools::log()->warning('Datos recibidos: ' . json_encode($data));

    if (empty($data['idturno'])) {
        Tools::log()->warning('No se ha seleccionado un turno');
        return false;
    }

    // Normaliza valores recibidos
    $kind   = (int)($data['kind']   ?? 1);   // 1=input, 2=output
    $origin = (int)($data['origin'] ?? 3);   // 1=manual, 3=panel (ajusta a tus constantes)
    $adjust = (($data['adjust'] ?? 'false') === 'true');

    $attendance = new \FacturaScripts\Plugins\HumanResources\Model\Attendance();
    $attendance->idemployee = (int)($data['idemployee'] ?? 0);
    $attendance->idturno    = (int)$data['idturno'];
    $attendance->origin     = $origin;
    $attendance->kind       = $kind;
    $attendance->location   = $data['location'] ?? '';

    if ($origin === \FacturaScripts\Plugins\HumanResources\Model\Attendance::ORIGIN_MANUAL) {
        // Manual: respeta fecha/hora/notas del formulario
        $attendance->authorized = false;
        $attendance->checkdate  = $data['date'] ?? date('Y-m-d');
        $attendance->checktime  = $data['time'] ?? date('H:i:s');
        $attendance->note       = $data['note'] ?? '';
    } else {
        // Automática: usa ahora si no llega fecha/hora
        $attendance->checkdate = $data['date'] ?? date('Y-m-d');
        $attendance->checktime = $data['time'] ?? date('H:i:s');
        $attendance->note      = $data['note'] ?? '';
    }

    // Si tu modelo tiene esto disponible, respétalo:
    if (method_exists($attendance, 'setAdjustToWordPeriod')) {
        $attendance->setAdjustToWordPeriod($adjust);
    }

    return $attendance->save();
}


    /**
     * Devuelve los turnos del empleado actual
     */
    public function getEmployeeShifts(): array
    {
        if (!$this->employee || !$this->employee->id) {
            return [];
        }

        $employeeShift = new EmployeeShift();
        $where = [
            new DataBaseWhere('idemployee', $this->employee->id),
            new DataBaseWhere('active', true)
        ];

        return $employeeShift->all($where);
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

    /**
     * Verifica si el empleado tiene un turno asignado para la fecha dada
     */
    public function hasShiftForDate(string $date): bool
    {
        if (!$this->employee || !$this->employee->id) {
            return false;
        }

        $employeeShift = new EmployeeShift();
        $where = [
            new DataBaseWhere('idemployee', $this->employee->id),
            new DataBaseWhere('active', true),
            new DataBaseWhere('start_date', $date, '<='),
            new DataBaseWhere('end_date', $date, '>=')
        ];

        return count($employeeShift->all($where)) > 0;
    }

    /**
     * Devuelve el calendario de turnos del empleado para el mes actual
     */
    public function getShiftCalendar(string $month = null): array
    {
        $month = $month ?? date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        if (!$this->employee || !$this->employee->id) {
            return [];
        }

        $employeeShift = new EmployeeShift();
        $where = [
            new DataBaseWhere('idemployee', $this->employee->id),
            new DataBaseWhere('active', true),
            new DataBaseWhere('start_date', $end, '<='),
            new DataBaseWhere('end_date', $start, '>=')
        ];

        return $employeeShift->all($where);
    }
}