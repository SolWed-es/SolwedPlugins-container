<?php
namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Attendance;
use FacturaScripts\Core\Tools;

class ListAttendance
{
    public function createViews(): Closure
    {
        return function() {
            ToolBox::log()->warning('HHRRSolwedTurnosSemana: Extensión createViews ejecutándose');
            
            // Llamar al método original PRIMERO
            parent::createViews();
            
            // Recrear la vista ListAttendance con el modelo Attendance
            if (isset($this->views['ListAttendance'])) {
                ToolBox::log()->warning('HHRRSolwedTurnosSemana: Recreando vista ListAttendance');
                
                // Eliminar la vista existente
                unset($this->views['ListAttendance']);
                
                // Crear nueva vista con modelo Attendance
                $this->addView('ListAttendance', 'Attendance', 'attendances', 'fas fa-clock');
                
                // Campos de búsqueda incluyendo idturno
                $this->addSearchFields('ListAttendance', [
                    'idemployee', 'credentialid', 'checkdate', 
                    'checktime', 'note', 'idturno'
                ]);

                // Ordenamientos
                $this->addOrderBy('ListAttendance', ['checkdate', 'checktime'], 'date', 2);
                $this->addOrderBy('ListAttendance', ['idemployee'], 'employee');
                $this->addOrderBy('ListAttendance', ['credentialid'], 'credential');
                $this->addOrderBy('ListAttendance', ['idturno'], 'turno');

                // Filtros originales
                $filterCheckDate = $this->request->get('filtercheckdate');
                if (false === isset($filterCheckDate)) {
                    $this->request->request->set('filtercheckdate', Tools::settings('rrhh', 'filtercheckdate', ''));
                }
                $this->addFilterPeriod('ListAttendance', 'checkdate', 'date', 'checkdate');
                $this->addFilterAutocomplete('ListAttendance', 'employee', 'employee', 'idemployee', 'Employee', 'id', 'nombre');

                $absenceConceptValues = $this->codeModel->all('rrhh_absencesconcepts', 'id', 'name');
                $this->addFilterSelect('ListAttendance', 'idabsenceconcept', 'absence-concept', 'idabsenceconcept', $absenceConceptValues);
                
                // CORREGIDO: Cambiar 'name' por 'location' en el filtro de turnos
                $turnoValues = $this->codeModel->all('rrhh_shifts', 'id', 'location');
                if (!empty($turnoValues)) {
                    $this->addFilterSelect('ListAttendance', 'idturno', 'turno', 'idturno', $turnoValues);
                }
                
                $this->addFilterSelect('ListAttendance', 'origin', 'origin', 'origin', [
                    ['code' => Attendance::ORIGIN_MANUAL, 'description' => Tools::lang()->trans('manual')],
                    ['code' => Attendance::ORIGIN_JUSTIFIED, 'description' => Tools::lang()->trans('justified')],
                    ['code' => Attendance::ORIGIN_EXTERNAL, 'description' => Tools::lang()->trans('external')],
                ]);

                $this->addFilterSelect('ListAttendance', 'kind', 'type', 'kind', [
                    ['code' => Attendance::KIND_INPUT, 'description' => Tools::lang()->trans('input')],
                    ['code' => Attendance::KIND_OUTPUT, 'description' => Tools::lang()->trans('output')],
                ]);

                $this->addFilterSelectWhere('ListAttendance', 'paid', [
                    ['label' => Tools::lang()->trans('all'), 'where' => []],
                    ['label' => Tools::lang()->trans('only-pending'), 'where' => [new DataBaseWhere('authorized', false)]],
                ]);

                // Botón de importar
                $this->addButton('ListAttendance', [
                    'type' => 'modal',
                    'action' => 'import',
                    'label' => 'import',
                    'color' => 'warning',
                    'icon' => 'fas fa-file-import',
                ]);
                
                ToolBox::log()->warning('HHRRSolwedTurnosSemana: Vista ListAttendance recreada correctamente con filtro de turnos usando location');
            }
            
            ToolBox::log()->warning('HHRRSolwedTurnosSemana: createViews completado');
        };
    }
}