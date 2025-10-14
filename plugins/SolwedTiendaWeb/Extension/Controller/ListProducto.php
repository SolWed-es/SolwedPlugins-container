<?php

namespace FacturaScripts\Plugins\SolwedTiendaWeb\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Para modificar el comportamiento o añadir pestañas o secciones a controladores de otros plugins (o del core)
 * podemos crear una extensión de ese controlador.
 *
 * https://facturascripts.com/publicaciones/extensiones-de-modelos
 */
class ListProducto
{
    public function createViews(): Closure
    {
        return function () {
            // tu código aquí
            // createViews() se ejecuta una vez realizado el createViews() del controlador.
            $this->addFilterSelectWhere('ListProducto', 'woo_id', [
                ['label' => 'Tienda WEB', 'where' => []],
                ['label' => 'Sincronizados con Tienda WEB', 'where' => [new DataBaseWhere('woo_id', null, 'IS NOT')]],
                ['label' => 'No sincronizados con Tienda WEB', 'where' => [new DataBaseWhere('woo_id', null, 'IS')]],
            ]);
        };
    }

    public function execAfterAction(): Closure
    {
        return function ($action) {
            // tu código aquí
            // execAfterAction() se ejecuta tras el execAfterAction() del controlador.
        };
    }

    public function execPreviousAction(): Closure
    {
        return function ($action) {
            // tu código aquí
            // execPreviousAction() se ejecuta después del execPreviousAction() del controlador.
            // Si devolvemos false detenemos la ejecución del controlador.
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            // tu código aquí
            // loadData() se ejecuta tras el loadData() del controlador. Recibe los parámetros $viewName y $view.
        };
    }
}
