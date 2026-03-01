<?php
/**
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\CronJob;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Template\CronJobClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Provincia;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class CheckData extends CronJobClass
{
    const JOB_NAME = 'crm-check-data';

    public static function run(): void
    {
        self::echo("\n\n* JOB: " . self::JOB_NAME . ' ...');

        self::checkProvinces();
        self::checkCities();

        self::saveEcho();
    }

    protected static function checkCities(): void
    {
        self::updateCities();

        // recorremos todas las ciudades con alias
        $whereAlias = [new DataBaseWhere('alias', '', '!=')];
        foreach (Ciudad::all($whereAlias, [], 0, 0) as $city) {
            // buscamos si existen contactos con una ciudad del listado de alias de la ciudad
            $whereCiudad = [
                new DataBaseWhere('ciudad', $city->alias, 'IN'),
                new DataBaseWhere('provincia', $city->getProvince()->provincia),
            ];
            foreach (Contacto::all($whereCiudad, [], 0, 0) as $contact) {
                if ($contact->ciudad == $city->ciudad) {
                    continue;
                }

                $before = $contact->ciudad;

                // si la ciudad del contacto es distinta a la oficial, actualizamos la ciudad del contacto
                $contact->ciudad = $city->ciudad;
                $contact->save();

                self::echo("\n-- Actualizando ciudad de contacto: " . $before . ' -> ' . $contact->ciudad);
            }
        }
    }

    protected static function checkProvinces(): void
    {
        self::updateProvinces();

        // recorremos todas las provincias
        $whereAlias = [new DataBaseWhere('alias', '', '!=')];
        foreach (Provincia::all($whereAlias, [], 0, 0) as $province) {
            // buscamos si existen contactos con una provincia del listado de alias de la provincia
            $whereProvincia = [new DataBaseWhere('provincia', $province->alias, 'IN')];
            foreach (Contacto::all($whereProvincia, [], 0, 0) as $contact) {
                if ($contact->provincia == $province->provincia) {
                    continue;
                }

                $before = $contact->provincia;

                // si la provincia del contacto es distinta a la oficial, actualizamos la provincia del contacto
                $contact->provincia = $province->provincia;
                $contact->save();

                self::echo("\n-- Actualizando provincia de contacto: " . $before . ' -> ' . $contact->provincia);
            }
        }
    }

    protected static function updateCities(): void
    {
        // leemos el archivo de ciudades
        $file_path = Tools::folder('Plugins', 'CRM', 'Data', 'ciudades_esp_alias.csv');
        if (!file_exists($file_path)) {
            self::echo("\n-- No se ha encontrado el archivo de ciudades: " . $file_path);
            return;
        }

        // leemos el archivo de ciudades
        foreach (file($file_path) as $line) {
            $data = explode(';', $line);
            if (count($data) != 2) {
                continue;
            }

            // buscamos la ciudad en la base de datos
            $city = new Ciudad();
            $where = [
                new DataBaseWhere('ciudad', $data[0]),
                new DataBaseWhere('alias', null),
            ];
            if (false === $city->loadFromCode('', $where)) {
                // no existe, la saltamos
                continue;
            }

            // asignamos el alias
            $city->alias = $data[1];
            $city->save();

            self::echo("\n-- Actualizando datos de ciudad: " . $data[0] . ' -> ' . $city->alias);
        }
    }

    protected static function updateProvinces(): void
    {
        // leemos el archivo de provincias
        $file_path = Tools::folder('Plugins', 'CRM', 'Data', 'provincias_esp_alias.csv');
        if (!file_exists($file_path)) {
            self::echo("\n-- No se ha encontrado el archivo de provincias: " . $file_path);
            return;
        }

        // leemos el archivo de provincias
        foreach (file($file_path) as $line) {
            $data = explode(';', $line);
            if (count($data) != 2) {
                continue;
            }

            // buscamos la provincia en la base de datos
            $province = new Provincia();
            $where = [
                new DataBaseWhere('codpais', 'ESP'),
                new DataBaseWhere('provincia', $data[0]),
                new DataBaseWhere('alias', null),
            ];
            if (false === $province->loadFromCode('', $where)) {
                // no existe, la saltamos
                continue;
            }

            // asignamos el alias
            $province->alias = $data[1];
            $province->save();

            self::echo("\n-- Actualizando datos de provincia: " . $data[0] . ' -> ' . $province->alias);
        }
    }
}
