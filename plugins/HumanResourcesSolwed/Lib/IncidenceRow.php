<?php
/**
 * DTO para filas de incidencias con soporte para url() en ListView.
 */

namespace FacturaScripts\Plugins\HumanResourcesSolwed\Lib;

use FacturaScripts\Core\Tools;

class IncidenceRow
{
    public string $type = '';
    public ?int $idemployee = null;
    public string $employee_code = '';
    public string $employee_name = '';
    public ?int $project_in = null;
    public string $project_in_name = '';
    public ?int $project_out = null;
    public string $project_out_name = '';
    public ?int $entry_id = null;
    public ?int $exit_id = null;
    public string $entry_date = '';
    public string $entry_time = '';
    public string $exit_date = '';
    public string $exit_time = '';

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Devuelve la URL para editar el fichaje principal de la incidencia.
     * Prioriza entry_id, si no existe usa exit_id.
     */
    public function url(string $type = 'auto'): string
    {
        $id = $this->entry_id ?? $this->exit_id;
        if (empty($id)) {
            return '';
        }
        return Tools::config('route') . '/EditAttendance?code=' . $id;
    }

    /**
     * Devuelve el valor de la columna primaria (requerido por ListView).
     */
    public function primaryColumnValue(): string
    {
        return (string)($this->entry_id ?? $this->exit_id ?? '');
    }
}
