<?php

namespace FacturaScripts\Plugins\HumanResourcesSolwed\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\EmployeeHoliday;
use FacturaScripts\Dinamic\Model\Join\EmployeeDocument;

class EditEmployee
{
    private const PAYROLL_TYPES = [3];
    private const CAE_TYPES = [6];

    public function createViews(): Closure
    {
        $payrollTypes = self::PAYROLL_TYPES;
        $caeTypes = self::CAE_TYPES;

        return function () use ($payrollTypes, $caeTypes) {
            $codeParam = $this->request->query->get('code', $this->request->input('code', 0));
            $employeeId = (int)($codeParam ?: ($this->getModel()->id ?? 0));
            if ($employeeId <= 0) {
                return;
            }

            // ----- Holidays (Admin Tab) -----
            $yearParam = $this->request->query->get('holidayYear', $this->request->input('holidayYear', 'all'));
            $selectedYear = (is_numeric($yearParam)) ? (int)$yearParam : null;
            if ($yearParam === 'all' || $yearParam === '' || $yearParam === null) {
                $selectedYear = null;
            }

            $orderParam = strtolower((string)$this->request->query->get('holidayOrder', $this->request->input('holidayOrder', 'desc')));
            $holidayOrder = in_array($orderParam, ['asc', 'desc'], true) ? $orderParam : 'desc';

            $this->holidayYearFilter = $selectedYear;
            $this->holidayOrder = $holidayOrder;

            $holidayWhere = [ new DataBaseWhere('idemployee', $employeeId) ];
            $holidayRows = EmployeeHoliday::all($holidayWhere, ['startdate' => strtoupper($holidayOrder)]);

            $holidayGroups = [];
            foreach ($holidayRows as $holiday) {
                $year = $holiday->applyto
                    ? (int)$holiday->applyto
                    : (int)date('Y', strtotime($holiday->startdate ?? 'now'));

                $holiday->year_group = $year;

                if (!isset($holidayGroups[$year])) {
                    $holidayGroups[$year] = [
                        'items' => [],
                        'totals' => ['total' => 0, 'enjoyed' => 0, 'pending' => 0],
                    ];
                }

                $holidayGroups[$year]['items'][] = $holiday;
                $holidayGroups[$year]['totals']['total'] += (int)$holiday->totaldays;
                if ($holiday->canDelete) {
                    $holidayGroups[$year]['totals']['pending'] += (int)$holiday->totaldays;
                } else {
                    $holidayGroups[$year]['totals']['enjoyed'] += (int)$holiday->totaldays;
                }
            }

            krsort($holidayGroups, SORT_NUMERIC);
            $this->holidayAvailableYears = array_keys($holidayGroups);
            $this->holidayGroups = (null === $selectedYear)
                ? $holidayGroups
                : array_filter(
                    $holidayGroups,
                    static fn ($yearKey) => $yearKey === $selectedYear,
                    ARRAY_FILTER_USE_KEY
                );

            // ----- Employee Files (Documents/Payroll/CAE) -----
            $filesOrderParam = strtolower((string)$this->request->query->get('filesorder', $this->request->input('filesorder', 'desc')));
            $filesOrder = in_array($filesOrderParam, ['asc', 'desc'], true) ? $filesOrderParam : 'desc';
            $this->filesOrder = $filesOrder;

            $docWhere = [ new DataBaseWhere('doc.idemployee', $employeeId) ];
            $documents = EmployeeDocument::all($docWhere, ['rel.creationdate' => strtoupper($filesOrder)]);

            $fileCounters = ['documents' => 0, 'payroll' => 0, 'cae' => 0];
            $fileGroups = ['documents' => [], 'payroll' => [], 'cae' => []];

            foreach ($documents as $doc) {
                $type = (int)$doc->iddoctype;
                $category = in_array($type, $payrollTypes, true)
                    ? 'payroll'
                    : (in_array($type, $caeTypes, true) ? 'cae' : 'documents');

                $year = (int)($doc->year_group ?: date('Y', strtotime($doc->creationdate ?? 'now')));

                if (!isset($fileGroups[$category][$year])) {
                    $fileGroups[$category][$year] = [];
                }

                $fileGroups[$category][$year][] = $doc;
                ++$fileCounters[$category];
            }

            foreach ($fileGroups as $category => $groups) {
                krsort($groups, SORT_NUMERIC);
                $fileGroups[$category] = $groups;
            }

            $this->employeeFileCounters = $fileCounters;
            $this->employeeFileGroups = $fileGroups;
        };
    }
}
