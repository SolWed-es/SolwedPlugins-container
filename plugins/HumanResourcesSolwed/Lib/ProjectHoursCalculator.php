<?php

namespace FacturaScripts\Plugins\HumanResourcesSolwed\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Attendance;
use FacturaScripts\Dinamic\Model\Employee;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;

/**
 * Calcula horas por proyecto y empleado a partir de rrhh_attendances,
 * emparejando fichajes de entrada/salida.
 */
class ProjectHoursCalculator
{
    /**
     * Calcula horas agregadas por proyecto y empleado.
     *
     * @param string|null $fromDate  Fecha inicio (Y-m-d) inclusive
     * @param string|null $toDate    Fecha fin (Y-m-d) inclusive
     * @param int|null    $idProyecto Filtro opcional de proyecto
     * @param int|null    $idEmployee Filtro opcional de empleado
     *
     * @return array lista de filas agregadas con claves:
     *  - idproyecto, project_code, project_name
     *  - idemployee, employee_code, employee_name
     *  - hours_total, hours_worked, hours_justified
     *  - sessions_count, first_date, last_date
     */
    public static function compute(
        ?string $fromDate,
        ?string $toDate,
        ?int $idProyecto = null,
        ?int $idEmployee = null
    ): array {
        [$rows, ] = self::computeWithIncidences($fromDate, $toDate, $idProyecto, $idEmployee);
        return $rows;
    }

    /**
     * Igual que compute(), pero devuelve también incidencias detectadas.
     *
     * @return array{0: array, 1: array}
     */
    public static function computeWithIncidences(
        ?string $fromDate,
        ?string $toDate,
        ?int $idProyecto = null,
        ?int $idEmployee = null
    ): array {
        $attendances = self::loadAttendances($fromDate, $toDate, $idProyecto, $idEmployee);
        if (empty($attendances)) {
            return [[], []];
        }

        $groupedByEmployee = [];
        foreach ($attendances as $att) {
            $groupedByEmployee[$att->idemployee][] = $att;
        }

        [$sessions, $incidences] = self::buildSessions($groupedByEmployee);
        if (empty($sessions)) {
            return [[], self::enrichIncidences($incidences)];
        }

        $aggregated = self::aggregateSessions($sessions);

        return [self::enrichWithNames($aggregated), self::enrichIncidences($incidences)];
    }

    /**
     * Carga asistencias filtradas y ordenadas.
     *
     * @return Attendance[]
     */
    private static function loadAttendances(
        ?string $fromDate,
        ?string $toDate,
        ?int $idProyecto,
        ?int $idEmployee
    ): array {
        $where = [];

        if (!empty($fromDate)) {
            $where[] = new DataBaseWhere('checkdate', $fromDate, '>=');
        }

        if (!empty($toDate)) {
            $where[] = new DataBaseWhere('checkdate', $toDate, '<=');
        }

        if (!empty($idProyecto)) {
            $where[] = new DataBaseWhere('idproyecto', $idProyecto);
        }

        if (!empty($idEmployee)) {
            $where[] = new DataBaseWhere('idemployee', $idEmployee);
        }

        $order = [
            'idemployee' => 'ASC',
            'checkdate' => 'ASC',
            'checktime' => 'ASC',
            'id' => 'ASC',
        ];

        return Attendance::all($where, $order, 0, 0);
    }

    /**
     * Construye sesiones válidas a partir de fichajes por empleado.
     *
     * @param array<int, Attendance[]> $grouped
     * @return array lista de sesiones con:
     *  - idemployee, idproyecto, start, end, minutes, origin, has_absence
     */
    private static function buildSessions(array $grouped): array
    {
        $sessions = [];
        $incidences = [];

        foreach ($grouped as $idEmployee => $attendances) {
            /** @var Attendance|null $open */
            $open = null;

            foreach ($attendances as $att) {
                if ($att->kind === Attendance::KIND_INPUT) {
                    // Nueva entrada
                    if (null !== $open) {
                        // Entrada previa sin cerrar -> incidencia
                        $incidences[] = self::incidence(
                            'open_entry_without_exit',
                            $idEmployee,
                            $open->id,
                            null,
                            (int)($open->idproyecto ?? 0),
                            null,
                            $open->checkdate,
                            $open->checktime
                        );
                    }
                    $open = $att;
                    continue;
                }

                if ($att->kind === Attendance::KIND_OUTPUT) {
                    if (null === $open) {
                        // Salida sin entrada -> incidencia
                        $incidences[] = self::incidence(
                            'exit_without_entry',
                            $idEmployee,
                            null,
                            $att->id,
                            null,
                            (int)($att->idproyecto ?? 0),
                            $att->checkdate,
                            $att->checktime
                        );
                        continue;
                    }

                    // Comprobamos coherencia de proyecto
                    $projectIn = (int)($open->idproyecto ?? 0);
                    $projectOut = (int)($att->idproyecto ?? 0);
                    if ($projectIn <= 0 || $projectOut <= 0 || $projectIn !== $projectOut) {
                        $incidences[] = self::incidence(
                            'mismatched_project',
                            $idEmployee,
                            $open->id,
                            $att->id,
                            $projectIn,
                            $projectOut,
                            $open->checkdate,
                            $open->checktime,
                            $att->checkdate,
                            $att->checktime
                        );
                        $open = null;
                        continue;
                    }

                    $start = self::toDateTimeString($open->checkdate, $open->checktime);
                    $end = self::toDateTimeString($att->checkdate, $att->checktime);
                    $minutes = self::diffInMinutes($start, $end);
                    if ($minutes <= 0) {
                        $open = null;
                        continue;
                    }

                    $hasAbsence = !empty($open->idabsenceconcept) || !empty($att->idabsenceconcept);
                    $origin = (int)$open->origin;

                    $sessions[] = [
                        'idemployee' => $idEmployee,
                        'idproyecto' => $projectIn,
                        'start' => $start,
                        'end' => $end,
                        'minutes' => $minutes,
                        'origin' => $origin,
                        'has_absence' => $hasAbsence,
                        'date' => $open->checkdate,
                    ];

                    $open = null;
                }
            }

            if (null !== $open) {
                $incidences[] = self::incidence(
                    'open_entry_at_end',
                    $idEmployee,
                    $open->id,
                    null,
                    (int)($open->idproyecto ?? 0),
                    null,
                    $open->checkdate,
                    $open->checktime
                );
            }
        }

        return [$sessions, $incidences];
    }

    private static function toDateTimeString(string $date, string $time): string
    {
        $d = trim($date);
        $t = trim($time);
        if ('' === $d) {
            $d = date('Y-m-d');
        }
        if ('' === $t) {
            $t = '00:00:00';
        }
        return $d . ' ' . $t;
    }

    private static function diffInMinutes(string $start, string $end): int
    {
        $startTs = strtotime($start) ?: 0;
        $endTs = strtotime($end) ?: 0;
        if ($endTs <= $startTs || 0 === $startTs || 0 === $endTs) {
            return 0;
        }

        return (int)round(($endTs - $startTs) / 60);
    }

    /**
     * @param array $sessions
     * @return array agregados indexados por [idproyecto][idemployee]
     */
    private static function aggregateSessions(array $sessions): array
    {
        $agg = [];

        foreach ($sessions as $s) {
            $idp = (int)$s['idproyecto'];
            $ide = (int)$s['idemployee'];

            if (!isset($agg[$idp])) {
                $agg[$idp] = [];
            }
            if (!isset($agg[$idp][$ide])) {
                $agg[$idp][$ide] = [
                    'idproyecto' => $idp,
                    'idemployee' => $ide,
                    'minutes_total' => 0,
                    'minutes_justified' => 0,
                    'sessions_count' => 0,
                    'first_date' => $s['date'],
                    'last_date' => $s['date'],
                ];
            }

            $row = &$agg[$idp][$ide];
            $row['minutes_total'] += $s['minutes'];

            // Consideramos justificadas las sesiones con ausencia o con origin JUSTIFIED
            if ($s['has_absence'] || $s['origin'] === Attendance::ORIGIN_JUSTIFIED) {
                $row['minutes_justified'] += $s['minutes'];
            }

            $row['sessions_count']++;

            if ($s['date'] < $row['first_date']) {
                $row['first_date'] = $s['date'];
            }
            if ($s['date'] > $row['last_date']) {
                $row['last_date'] = $s['date'];
            }
        }

        return $agg;
    }

    /**
     * Convierte los agregados en una lista plana y añade nombres de proyecto/empleado.
     */
    private static function enrichWithNames(array $agg): array
    {
        if (empty($agg)) {
            return [];
        }

        $projectIds = array_keys($agg);
        $employeeIds = [];
        foreach ($agg as $byProject) {
            $employeeIds = array_merge($employeeIds, array_keys($byProject));
        }
        $employeeIds = array_values(array_unique($employeeIds));

        $projects = self::loadProjects($projectIds);
        $employees = self::loadEmployees($employeeIds);

        $rows = [];
        foreach ($agg as $idp => $byProject) {
            foreach ($byProject as $ide => $row) {
                $minutesTotal = (int)$row['minutes_total'];
                $minutesJustified = (int)$row['minutes_justified'];
                $hoursTotal = $minutesTotal / 60.0;
                $hoursJustified = $minutesJustified / 60.0;
                $hoursWorked = max(0.0, $hoursTotal - $hoursJustified);

                $proj = $projects[$idp] ?? null;
                $emp = $employees[$ide] ?? null;

                $rows[] = [
                    'idproyecto' => $idp,
                    'project_code' => $proj['code'] ?? (string)$idp,
                    'project_name' => $proj['name'] ?? '',
                    'idemployee' => $ide,
                    'employee_code' => $emp['code'] ?? (string)$ide,
                    'employee_name' => $emp['name'] ?? '',
                    'hours_total' => $hoursTotal,
                    'hours_justified' => $hoursJustified,
                    'hours_worked' => $hoursWorked,
                    'sessions_count' => $row['sessions_count'],
                    'first_date' => $row['first_date'],
                    'last_date' => $row['last_date'],
                ];
            }
        }

        return $rows;
    }

    /**
     * @param int[] $ids
     * @return array<int, array{code:string,name:string}>
     */
    private static function loadProjects(array $ids): array
    {
        $result = [];
        if (empty($ids) || !class_exists(Proyecto::class)) {
            return $result;
        }

        foreach ($ids as $id) {
            $proj = new Proyecto();
            if ($proj->load($id)) {
                $result[(int)$id] = [
                    'code' => (string)$proj->idproyecto,
                    'name' => (string)$proj->nombre,
                ];
            }
        }

        return $result;
    }

    /**
     * @param int[] $ids
     * @return array<int, array{code:string,name:string}>
     */
    private static function loadEmployees(array $ids): array
    {
        $result = [];
        if (empty($ids) || !class_exists(Employee::class)) {
            return $result;
        }

        foreach ($ids as $id) {
            $emp = new Employee();
            if ($emp->load($id)) {
                $result[(int)$id] = [
                    'code' => (string)$emp->id,
                    'name' => (string)$emp->nombre,
                ];
            }
        }

        return $result;
    }

    /**
     * Devuelve incidencias enriquecidas con nombres de proyecto/empleado.
     *
     * @param array $incidences
     * @return IncidenceRow[]
     */
    private static function enrichIncidences(array $incidences): array
    {
        if (empty($incidences)) {
            return [];
        }

        $projectIds = [];
        $employeeIds = [];
        foreach ($incidences as $inc) {
            if (!empty($inc['project_in'])) {
                $projectIds[] = $inc['project_in'];
            }
            if (!empty($inc['project_out'])) {
                $projectIds[] = $inc['project_out'];
            }
            if (!empty($inc['idemployee'])) {
                $employeeIds[] = $inc['idemployee'];
            }
        }
        $projectIds = array_values(array_unique(array_filter($projectIds)));
        $employeeIds = array_values(array_unique(array_filter($employeeIds)));

        $projects = self::loadProjects($projectIds);
        $employees = self::loadEmployees($employeeIds);

        $rows = [];
        foreach ($incidences as $inc) {
            $projIn = $inc['project_in'] ?? null;
            $projOut = $inc['project_out'] ?? null;
            $empId = $inc['idemployee'] ?? null;
            $entryId = $inc['entry_id'] ?? null;
            $exitId = $inc['exit_id'] ?? null;

            $rows[] = new IncidenceRow([
                'type' => Tools::lang()->trans($inc['type']),
                'idemployee' => $empId,
                'employee_code' => $empId !== null ? (string)$empId : '',
                'employee_name' => $empId !== null && isset($employees[$empId]) ? $employees[$empId]['name'] : '',
                'project_in' => $projIn,
                'project_in_name' => $projIn !== null && isset($projects[$projIn]) ? $projects[$projIn]['name'] : '',
                'project_out' => $projOut,
                'project_out_name' => $projOut !== null && isset($projects[$projOut]) ? $projects[$projOut]['name'] : '',
                'entry_id' => $entryId,
                'exit_id' => $exitId,
                'entry_date' => $inc['entry_date'] ?? '',
                'entry_time' => $inc['entry_time'] ?? '',
                'exit_date' => $inc['exit_date'] ?? '',
                'exit_time' => $inc['exit_time'] ?? '',
            ]);
        }

        return $rows;
    }

    /**
     * Helper para construir incidencias con la info necesaria.
     */
    private static function incidence(
        string $type,
        int $idemployee,
        ?int $entryId,
        ?int $exitId,
        ?int $projectIn,
        ?int $projectOut,
        ?string $entryDate,
        ?string $entryTime,
        ?string $exitDate = null,
        ?string $exitTime = null
    ): array {
        return [
            'type' => $type,
            'idemployee' => $idemployee,
            'entry_id' => $entryId,
            'exit_id' => $exitId,
            'project_in' => $projectIn,
            'project_out' => $projectOut,
            'entry_date' => $entryDate ?? '',
            'entry_time' => $entryTime ?? '',
            'exit_date' => $exitDate ?? '',
            'exit_time' => $exitTime ?? '',
        ];
    }
}
