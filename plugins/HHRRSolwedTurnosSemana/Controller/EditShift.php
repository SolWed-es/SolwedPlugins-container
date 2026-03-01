<?php
namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Model\ShiftWeekDay;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

class EditShift extends EditController
{
    private const VIEW_WEEK_DAYS = 'ListShiftWeekDay';

    public function getModelClassName(): string
    {
        return 'Shift';
    }

    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'shift';
        $pagedata['icon'] = 'fas fa-clock';
        $pagedata['menu'] = 'rrhh';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    protected function createViews()
    {
        // Vista principal para editar el turno (usa el Twig automáticamente)
        parent::createViews();
        
        // Pestaña para gestionar días de la semana
        $this->createViewWeekDays();
        
        // Configurar posición de las pestañas (abajo como en EditRuta)
        $this->setTabsPosition('bottom');
    }

    /**
     * Create view to manage week days
     */
    protected function createViewWeekDays()
    {
        $view = $this->addEditListView(self::VIEW_WEEK_DAYS, 'ShiftWeekDay', 'weekly-schedule', 'fas fa-calendar-week');
        $view->setInLine(true);

        // Permitir reordenar por día de la semana
        $this->setSettings(self::VIEW_WEEK_DAYS, 'sortable', true);
    }

    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        
        // Obtener el ID del turno
        $primaryKey = $this->views[$mainViewName]->model->primaryColumn();
        $idshift = $this->getViewModelValue($mainViewName, $primaryKey);

        if ($viewName === $mainViewName) {
            parent::loadData($viewName, $view);
            return;
        }

        switch ($viewName) {
            case self::VIEW_WEEK_DAYS:
                $where = [new DataBaseWhere('idshift', $idshift)];
                $view->loadData('', $where, ['dayofweek' => 'ASC']);

                if (!empty($idshift)) {
                    $view->model->idshift = $idshift;
                }
                break;
        }
    }

    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'save':
                return $this->saveAction();
            default:
                return parent::execPreviousAction($action);
        }
    }

    protected function saveAction()
    {
        if (!$this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        }

        // Obtener datos del formulario
        $data = $this->request->request->all();
        
        // Guardar el turno principal
        $model = $this->getModel();
        $model->loadFromData($data);
        
        if (!$model->save()) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            return true;
        }

        // Procesar días de la semana si vienen del formulario Twig
        $weekDaysData = $this->request->request->get('weekdays', []);
        if (!empty($weekDaysData)) {
            if ($this->saveWeekDays($model)) {
                $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            } else {
                $this->toolBox()->i18nLog()->error('error-saving-weekdays');
            }
        } else {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        }
        
        return true;
    }

    private function saveWeekDays($shift): bool
    {
        $weekDaysData = $this->request->request->get('weekdays', []);
        
        // Eliminar días existentes para este turno
        $existingWeekDay = new ShiftWeekDay();
        $existingDays = $existingWeekDay->all([new DataBaseWhere('idshift', $shift->id)]);
        foreach ($existingDays as $day) {
            $day->delete();
        }
        
        // Guardar nuevos días
        foreach ($weekDaysData as $weekDayData) {
            if (empty($weekDayData['dayofweek'])) {
                continue;
            }
            
            $weekDay = new ShiftWeekDay();
            $weekDay->idshift = $shift->id;
            $weekDay->dayofweek = (int)$weekDayData['dayofweek'];
            $weekDay->morning_entry = !empty($weekDayData['morning_entry']) ? $weekDayData['morning_entry'] : null;
            $weekDay->morning_exit = !empty($weekDayData['morning_exit']) ? $weekDayData['morning_exit'] : null;
            $weekDay->afternoon_entry = !empty($weekDayData['afternoon_entry']) ? $weekDayData['afternoon_entry'] : null;
            $weekDay->afternoon_exit = !empty($weekDayData['afternoon_exit']) ? $weekDayData['afternoon_exit'] : null;
            
            if (!$weekDay->save()) {
                return false;
            }
        }
        
        return true;
    }
}