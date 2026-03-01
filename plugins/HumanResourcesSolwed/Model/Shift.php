<?php
/**
 * This file is part of HumanResourcesSolwed plugin for FacturaScripts.
 * FacturaScripts Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * HumanResourcesSolwed Copyright (C) 2025 Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * This program and its files are under the terms of the license specified in the LICENSE file.
 */
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;

/**
 * Model for work shifts
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Shift extends ModelClass
{
    use ModelTrait;

    /**
     * Location of the shift
     *
     * @var string
     */
    public $location;

    /**
     * Shift number
     *
     * @var int
     */
    public $shift_number;

    /**
     * Monday active
     *
     * @var bool
     */
    public $monday;

    /**
     * Tuesday active
     *
     * @var bool
     */
    public $tuesday;

    /**
     * Wednesday active
     *
     * @var bool
     */
    public $wednesday;

    /**
     * Thursday active
     *
     * @var bool
     */
    public $thursday;

    /**
     * Friday active
     *
     * @var bool
     */
    public $friday;

    /**
     * Saturday active
     *
     * @var bool
     */
    public $saturday;

    /**
     * Sunday active
     *
     * @var bool
     */
    public $sunday;

    /**
     * Active status
     *
     * @var bool
     */
    public $active;

    /**
     * Auto-assignable status
     *
     * @var bool
     */
    public $autoassignable;

    /**
     * Notes
     *
     * @var string
     */
    public $notes;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->active = true;
        $this->autoassignable = false;
        $this->monday = true;
        $this->tuesday = true;
        $this->wednesday = true;
        $this->thursday = true;
        $this->friday = true;
        $this->saturday = false;
        $this->sunday = false;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     */
    public static function tableName(): string
    {
        return 'rrhh_shifts';
    }

    /**
     * Returns the name of the controller for this model.
     */
    public function modelClassName(): string
    {
        return 'Shift';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     */
    public function test(): bool
    {
        if (empty($this->location)) {
            $this->toolBox()->i18nLog()->warning('location-required');
            return false;
        }

        if (empty($this->shift_number)) {
            $this->toolBox()->i18nLog()->warning('shift-number-required');
            return false;
        }

        return parent::test();
    }

    /**
     * Returns week days configuration for this shift
     */
    public function getWeekDays()
    {
        $weekDay = new ShiftWeekDay();
        return $weekDay->all(
            [new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('idshift', $this->id)],
            ['dayofweek' => 'ASC']
        );
    }
}
