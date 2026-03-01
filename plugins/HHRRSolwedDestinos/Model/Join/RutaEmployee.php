<?php
/**
 * This file is part of HHRRSolwedDestinos plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HHRRSolwedDestinos Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HHRRSolwedDestinos\Model\Join;

use FacturaScripts\Core\Model\Base\JoinModel;

/**
 * Model to get routes with their assigned employees
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class RutaEmployee extends JoinModel
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new \FacturaScripts\Plugins\HHRRSolwedDestinos\Model\Ruta());
    }

    protected function getFields(): array
    {
        return [
            'idruta' => 'rutas.idruta',
            'nombre' => 'rutas.nombre',
            'fecha_inicio' => 'rutas.fecha_inicio',
            'fecha_fin' => 'rutas.fecha_fin',
            'optimizada' => 'rutas.optimizada',
            'created_at' => 'rutas.created_at',
            'updated_at' => 'rutas.updated_at',
            'employees' => 'GROUP_CONCAT(DISTINCT rrhh_employees.nick ORDER BY rrhh_employees.nick SEPARATOR ", ")'
        ];
    }

    protected function getSQLFrom(): string
    {
        return 'rutas'
            . ' LEFT JOIN rrhh_employeeroutes ON rrhh_employeeroutes.idruta = rutas.idruta'
            . ' LEFT JOIN rrhh_employees ON rrhh_employees.idemployee = rrhh_employeeroutes.idemployee';
    }

    protected function getTables(): array
    {
        return ['rutas', 'rrhh_employeeroutes', 'rrhh_employees'];
    }

    public function primaryColumn(): string
    {
        return 'idruta';
    }
}