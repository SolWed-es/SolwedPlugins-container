<?php

namespace FacturaScripts\Plugins\CRM\Extension\Model;

use FacturaScripts\Core\Tools;
use Closure;

class CrmNota
{
    public function saveBefore(): Closure
    {
        return function(){

            //Si no pones la hora le asigno una, que si no no sale en algunos calendarios. No soporta "todo el día"
            if ($this->startdate && empty($this->starttime)){
                $this->starttime = '07:00:00';
            }

            //Calculo inicio
            $startDate = $this->startdate;
            $startTime = $this->starttime;
            $startCombine = date('Y-m-d H:i:s', strtotime("$startDate $startTime"));
            $this->start = $startCombine;

            //Si no hay fin
            if (empty($this->enddate) && empty($this->endtime)){
                $endCalculate = strtotime('+1 hours', strtotime($startCombine));
                $endCombine = date('Y-m-d H:i:s', $endCalculate);
                $this->end = $endCombine;
                return;
            }

            //fecha fin, pero no hora
            if (empty($this->endtime) && $this->enddate){

                $startTimeTimestamp = strtotime($startTime);
                $newStartTimeTimestamp = $startTimeTimestamp + (60 * 60); // Agregar 1 hora (60 minutos * 60 segundos)
                $newEndTime = date('H:i:s', $newStartTimeTimestamp);

                $this->endtime = $newEndTime;
                $endDate = $this->enddate;
                $endTime = $this->endtime;
                $endCombine = date('Y-m-d H:i:s', strtotime("$endDate $endTime"));
                $this->end = $endCombine;
                return;
            }

            $endDate = $this->enddate;
            $endTime = $this->endtime;
            $endCombine = date('Y-m-d H:i:s', strtotime("$endDate $endTime"));
            $this->end = $endCombine;

            //inicio < fin
            if (strtotime($this->end) < strtotime($this->start)){
                Tools::log()->warning('Revisa los datos: Fecha fin antes que inicio');
                return;
            }

            if (property_exists($this, 'idstatus') && empty($this->idstatus)) {
                $this->idstatus = 1;
            }
        };
    }
}
