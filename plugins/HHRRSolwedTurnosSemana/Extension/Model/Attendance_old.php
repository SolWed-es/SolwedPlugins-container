<?php
/**
 * This file is part of HHRRSolwedTurnosSemana plugin for FacturaScripts.
 */
namespace FacturaScripts\Plugins\HHRRSolwedTurnosSemana\Extension\Model;

class Attendance
{
    /**
     * ID del turno asignado a la asistencia
     *
     * @var int|null
     */
    public $idturno = null;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->idturno = null;
    }

    /**
     * Get the idturno
     *
     * @return int|null
     */
    public function getIdturno()
    {
        return $this->idturno;
    }

    /**
     * Set the idturno
     *
     * @param int|null $idturno
     */
    public function setIdturno($idturno = null)
    {
        $this->idturno = $idturno;
    }
}