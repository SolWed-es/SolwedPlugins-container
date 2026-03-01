<?php
/**
 * This file is part of HumanResourcesSolwed plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HumanResourcesSolwed Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Plugins\HumanResourcesSolwed\Model\EmployeeShift;
use FacturaScripts\Plugins\HumanResourcesSolwed\Model\Shift;

/**
 * Controller to handle shift assignment actions
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class AssignShift extends Controller
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['showonmenu'] = false;
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->request->get('action');
        switch ($action) {
            case 'assign-shift':
                $this->assignShiftAction();
                break;

            case 'unassign-shift':
                $this->unassignShiftAction();
                break;

            case 'load-shifts-data':
                $this->loadShiftsDataAction();
                break;
        }
    }

    private function assignShiftAction()
    {
        try {
            $employeeId = $this->request->request->get('idemployee');
            $shiftId = $this->request->request->get('idshift');
            $startDate = $this->request->request->get('start_date');
            $endDate = $this->request->request->get('end_date');
            $notes = $this->request->request->get('notes', '');

            // Validate required fields
            if (empty($employeeId) || empty($shiftId)) {
                $this->toolBox()->i18nLog()->warning('shift-and-employee-required');
                $this->redirect($this->request->server->get('HTTP_REFERER', ''));
                return;
            }

            // Verify shift exists
            $shift = new Shift();
            if (!$shift->loadFromCode($shiftId)) {
                $this->toolBox()->i18nLog()->warning('shift-not-found');
                $this->redirect($this->request->server->get('HTTP_REFERER', ''));
                return;
            }

            // Create new assignment
            $employeeShift = new EmployeeShift();
            $employeeShift->idemployee = $employeeId;
            $employeeShift->idshift = $shiftId;
            $employeeShift->assignment_date = date('Y-m-d');
            $employeeShift->start_date = !empty($startDate) ? $startDate : null;
            $employeeShift->end_date = !empty($endDate) ? $endDate : null;
            $employeeShift->notes = $notes;
            $employeeShift->active = true;
            $employeeShift->nick = $this->user->nick;

            if ($employeeShift->save()) {
                $this->toolBox()->i18nLog()->notice('shift-assigned-successfully');
            } else {
                $this->toolBox()->i18nLog()->error('error-assigning-shift');
            }

        } catch (\Exception $e) {
            $this->toolBox()->log()->error('HumanResourcesSolwed: Error in assign-shift action: ' . $e->getMessage());
            $this->toolBox()->i18nLog()->error('error-assigning-shift');
        }

        $this->redirect($this->request->server->get('HTTP_REFERER', ''));
    }

    private function unassignShiftAction()
    {
        try {
            // Try both 'id' and 'assignment_id' for compatibility
            $assignmentId = $this->request->request->get('id') ?: $this->request->request->get('assignment_id');

            if (empty($assignmentId)) {
                $this->toolBox()->i18nLog()->warning('assignment-id-required');
                $this->redirect($this->request->server->get('HTTP_REFERER', ''));
                return;
            }

            $employeeShift = new EmployeeShift();
            if ($employeeShift->loadFromCode($assignmentId)) {
                if ($employeeShift->delete()) {
                    $this->toolBox()->i18nLog()->notice('shift-unassigned-successfully');
                } else {
                    $this->toolBox()->i18nLog()->error('error-unassigning-shift');
                }
            } else {
                $this->toolBox()->i18nLog()->warning('assignment-not-found');
            }

        } catch (\Exception $e) {
            $this->toolBox()->log()->error('HumanResourcesSolwed: Error in unassign-shift action: ' . $e->getMessage());
            $this->toolBox()->i18nLog()->error('error-unassigning-shift');
        }

        $this->redirect($this->request->server->get('HTTP_REFERER', ''));
    }

    private function loadShiftsDataAction()
    {
        try {
            $employeeId = $this->request->request->get('idemployee');

            if (empty($employeeId)) {
                $this->response->setContent(json_encode(['error' => 'Employee ID required']));
                return;
            }

            $employeeShift = new EmployeeShift();
            $assignedShifts = $employeeShift->all([
                new DataBaseWhere('idemployee', $employeeId)
            ], ['assignment_date' => 'DESC']);

            $data = [];
            foreach ($assignedShifts as $assignment) {
                $shift = $assignment->getShift();
                $data[] = [
                    'id' => $assignment->id,
                    'shift_id' => $assignment->idshift,
                    'location' => $shift ? $shift->location : 'N/A',
                    'shift_number' => $shift ? $shift->shift_number : 'N/A',
                    'assignment_date' => $assignment->assignment_date,
                    'start_date' => $assignment->start_date ?: '',
                    'end_date' => $assignment->end_date ?: '',
                    'notes' => $assignment->notes,
                    'active' => $assignment->active,
                    'is_currently_active' => $assignment->isActive()
                ];
            }

            $this->response->setContent(json_encode($data));

        } catch (\Exception $e) {
            $this->toolBox()->log()->error('HumanResourcesSolwed: Error in load-shifts-data action: ' . $e->getMessage());
            $this->response->setContent(json_encode(['error' => 'Error loading data']));
        }
    }
}
