<?php
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Attendance;
use FacturaScripts\Dinamic\Model\EmployeeHoliday;
use FacturaScripts\Plugins\HumanResources\Controller\EmployeePanel as ParentEmployeePanel;
use FacturaScripts\Plugins\HumanResources\Lib\DateTimeTools;

class EmployeePanel extends ParentEmployeePanel
{
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

        if ($action === 'insert-attendance') {
            return $this->execInsertAttendanceWithLocalization();
        } elseif ($action === 'insert-holidays') {
            return $this->execInsertHolidaysWithStatus();
        } else {
            return parent::execPreviousAction($action);
        }
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
