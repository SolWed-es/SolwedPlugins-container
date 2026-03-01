<?php

namespace FacturaScripts\Plugins\CRM\Extension\Model;

use Closure;

class ServicioAT
{
    public function saveBefore(): Closure
    {
        return function(){
            $startDate = $this->fecha;
            $startTime = $this->hora;
            $startCombine = date('Y-m-d H:i:s', strtotime("$startDate $startTime"));
            $this->start = $startCombine;
            $endCalculate = strtotime('+1 hours', strtotime($startCombine));
            $endCalculate = date('Y-m-d H:i:s', $endCalculate);
            $this->end = $endCalculate;
        };
    }
}
