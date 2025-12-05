<?php

namespace FacturaScripts\Plugins\MerakiPlugin\Extension\Controller;

class ListVariante
{
    public function createViews()
    {
        return function () {
        $tallaetiquetas = $this->codeModel->all('variantes', 'tallaetiqueta', 'tallaetiqueta');
        
            $this->addFilterSelect('ListVariante', 'tallaetiqueta', 'Selecciona talla', 'tallaetiqueta', $tallaetiquetas);
        }
    }
    }
