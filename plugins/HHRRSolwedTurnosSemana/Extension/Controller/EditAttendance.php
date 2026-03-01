<?php
namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\ToolBox;

class EditAttendance
{
    public function execPreviousAction(): Closure
    {
        return function($action) {
            ToolBox::log()->warning('HHRRSolwedTurnosSemana: EditAttendance execPreviousAction - action: ' . $action);
            
            // Capturar el idturno del POST antes de procesar
            if ($this->request->isMethod('POST') && $action === 'save') {
                $idturno = $this->request->request->get('idturno');
                if ($idturno !== null && $idturno !== '') {
                    // Asignar directamente a la propiedad en lugar de usar el setter
                    $model = $this->getModel();
                    $model->idturno = $idturno;
                    
                    ToolBox::log()->warning('HHRRSolwedTurnosSemana: idturno asignado directamente: ' . $idturno);
                } else {
                    ToolBox::log()->warning('HHRRSolwedTurnosSemana: idturno vacío o null');
                }
            }
            
            return parent::execPreviousAction($action);
        };
    }
}