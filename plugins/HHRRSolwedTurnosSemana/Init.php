<?php
namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Base\ToolBox;

class Init extends InitClass
{
    public function init(): void
    {
        // Log de inicio
        ToolBox::log()->warning('HHRRSolwedTurnosSemana: INICIANDO carga de extensiones');
        
        // Load model extensions
       /*  $this->loadExtension(new Extension\Model\Attendance());
        ToolBox::log()->warning('HHRRSolwedTurnosSemana: Extensión Model\Attendance CARGADA'); */
        
        // Load controller extensions
        $this->loadExtension(new Extension\Controller\ListAttendance());
        ToolBox::log()->warning('HHRRSolwedTurnosSemana: Extensión Controller\ListAttendance CARGADA');

        // NUEVO: Cargar extensión EditAttendance si la creas
        $this->loadExtension(new Extension\Controller\EditAttendance());
        ToolBox::log()->warning('HHRRSolwedTurnosSemana: Extensión Controller\EditAttendance CARGADA');
        

        // Plugin initialization
        ToolBox::log()->warning('HHRRSolwedTurnosSemana: Plugin inicializado correctamente - TODAS LAS EXTENSIONES CARGADAS');
    }

    public function update(): void
    {
        // Limpiar caché al actualizar
        ToolBox::cache()->clear();
        
        // Log para verificar que la extensión de tabla se está aplicando
        ToolBox::log()->warning('HHRRSolwedTurnosSemana: Actualizando plugin - aplicando extensiones de tabla');
    }

    public function uninstall(): void
    {
        // Plugin uninstallation cleanup
        ToolBox::log()->warning('HHRRSolwedTurnosSemana: Plugin desinstalado correctamente');
    }
}