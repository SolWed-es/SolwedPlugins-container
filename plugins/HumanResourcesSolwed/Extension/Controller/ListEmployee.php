<?php
/**
 * This file is part of HumanResourcesSolwed plugin for FacturaScripts
 * Copyright (C) 2024 Jose Ferran
 *
 * This program is proprietary software: you can not redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\HumanResourcesSolwed\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;

/**
 * Extension for ListEmployee controller to modify the order of ListEmployeeHoliday view
 *
 * @author Jose Ferran
 */
class ListEmployee
{
    /**
     * Modify the createViews method to change the order of ListEmployeeHoliday view
     * This method is executed after the original createViews() method
     *
     * @return Closure
     */
    public function createViews(): Closure
    {
        return function () {
            $loadDistinctValues = function (string $sql): array {
                $values = [];
                try {
                    foreach ($this->dataBase->select($sql) as $row) {
                        if (empty($row['value'])) {
                            continue;
                        }
                        $label = (string)$row['value'];
                        $values[] = [
                            'code' => $label,
                            'description' => $label,
                        ];
                    }
                } catch (\Throwable $dbError) {
                    Tools::log()->warning('HumanResourcesSolwed: error loading filter values -> ' . $dbError->getMessage());
                }
                return $values;
            };

            // Configuración de la vista de vacaciones
            if (isset($this->views['ListEmployeeHoliday'])) {
                $view = $this->views['ListEmployeeHoliday'];
                $view->showFilters = true;
                $view->orderOptions = [];

                $yearValues = $loadDistinctValues('SELECT DISTINCT applyto AS value FROM rrhh_employeesholidays WHERE applyto IS NOT NULL ORDER BY applyto DESC');
                if (!empty($yearValues)) {
                    $this->addFilterSelect('ListEmployeeHoliday', 'applyto-year', 'apply-to', 'holidays.applyto', $yearValues);
                } else {
                    $this->addFilterNumber('ListEmployeeHoliday', 'applyto-year', 'apply-to', 'holidays.applyto', '=');
                }

                $this->addOrderBy('ListEmployeeHoliday', ['holidays.holidaystatus'], 'Estado', 2);
                $this->addOrderBy('ListEmployeeHoliday', ['holidays.startdate'], 'start-date');
                $this->addOrderBy('ListEmployeeHoliday', ['employees.nombre', 'holidays.startdate'], 'name');
                $this->addOrderBy('ListEmployeeHoliday', ['holidays.idemployee'], 'employee');
            }

            // Configuración de la vista de nóminas
            if (isset($this->views['ListPayRoll'])) {
                $view = $this->views['ListPayRoll'];
                $view->showFilters = true;
                $view->orderOptions = [];

                $yearValues = $loadDistinctValues('SELECT DISTINCT YEAR(startdate) AS value FROM rrhh_payroll WHERE startdate IS NOT NULL ORDER BY value DESC');
                $this->addFilterSelect('ListPayRoll', 'payroll-year', 'year', 'YEAR(startdate)', $yearValues);

                $this->addOrderBy('ListPayRoll', ['startdate'], 'start-date', 2);
                $this->addOrderBy('ListPayRoll', ['enddate'], 'end-date');
                $this->addOrderBy('ListPayRoll', ['creationdate'], 'date');
                $this->addOrderBy('ListPayRoll', ['name'], 'name');
            }
        };
    }

}
