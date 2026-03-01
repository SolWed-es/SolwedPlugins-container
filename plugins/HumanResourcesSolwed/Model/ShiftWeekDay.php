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
 * Model for shift week days configuration
 * Stores daily schedules for each shift
 *
 * @author José Ferrán
 */
class ShiftWeekDay extends ModelClass
{
    use ModelTrait;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     * Foreign key to shift
     *
     * @var int
     */
    public $idshift;

    /**
     * Day of week (1=Monday, 7=Sunday)
     *
     * @var int
     */
    public $dayofweek;

    /**
     * Morning entry time
     *
     * @var string
     */
    public $morning_entry;

    /**
     * Morning exit time
     *
     * @var string
     */
    public $morning_exit;

    /**
     * Afternoon entry time
     *
     * @var string
     */
    public $afternoon_entry;

    /**
     * Afternoon exit time
     *
     * @var string
     */
    public $afternoon_exit;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->dayofweek = 1;
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
        return 'rrhh_shift_week_days';
    }

    /**
     * Returns the name of the controller for this model.
     */
    public function modelClassName(): string
    {
        return 'ShiftWeekDay';
    }

    /**
     * Returns the shift associated with this day configuration
     *
     * @return Shift|null
     */
    public function getShift()
    {
        if (empty($this->idshift)) {
            return null;
        }

        $shift = new Shift();
        return $shift->loadFromCode($this->idshift) ? $shift : null;
    }

    /**
     * Returns the day name in Spanish
     *
     * @return string
     */
    public function getDayName(): string
    {
        $days = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];

        return $days[$this->dayofweek] ?? '';
    }

    /**
     * Validates the model data
     *
     * @return bool
     */
    public function test(): bool
    {
        if (empty($this->idshift)) {
            $this->toolBox()->i18nLog()->warning('shift-required');
            return false;
        }

        if ($this->dayofweek < 1 || $this->dayofweek > 7) {
            $this->toolBox()->i18nLog()->warning('invalid-day-of-week');
            return false;
        }

        // Validate time formats
        if (!$this->validateTimeFormat($this->morning_entry) && !empty($this->morning_entry)) {
            $this->toolBox()->i18nLog()->warning('invalid-morning-entry-time');
            return false;
        }

        if (!$this->validateTimeFormat($this->morning_exit) && !empty($this->morning_exit)) {
            $this->toolBox()->i18nLog()->warning('invalid-morning-exit-time');
            return false;
        }

        if (!$this->validateTimeFormat($this->afternoon_entry) && !empty($this->afternoon_entry)) {
            $this->toolBox()->i18nLog()->warning('invalid-afternoon-entry-time');
            return false;
        }

        if (!$this->validateTimeFormat($this->afternoon_exit) && !empty($this->afternoon_exit)) {
            $this->toolBox()->i18nLog()->warning('invalid-afternoon-exit-time');
            return false;
        }

        // Validate time ranges
        if (!empty($this->morning_entry) && !empty($this->morning_exit)) {
            if (strtotime($this->morning_entry) >= strtotime($this->morning_exit)) {
                $this->toolBox()->i18nLog()->warning('morning-exit-must-be-after-entry');
                return false;
            }
        }

        if (!empty($this->afternoon_entry) && !empty($this->afternoon_exit)) {
            if (strtotime($this->afternoon_entry) >= strtotime($this->afternoon_exit)) {
                $this->toolBox()->i18nLog()->warning('afternoon-exit-must-be-after-entry');
                return false;
            }
        }

        return parent::test();
    }

    /**
     * Validates time format (HH:MM:SS or HH:MM)
     *
     * @param string $time
     * @return bool
     */
    private function validateTimeFormat(?string $time): bool
    {
        if (empty($time)) {
            return true;
        }

        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time);
    }

    /**
     * Returns a string with the schedule for this day
     *
     * @return string
     */
    public function getScheduleString(): string
    {
        $schedule = [];

        if (!empty($this->morning_entry) && !empty($this->morning_exit)) {
            $schedule[] = 'Mañana: ' . substr($this->morning_entry, 0, 5) . ' - ' . substr($this->morning_exit, 0, 5);
        }

        if (!empty($this->afternoon_entry) && !empty($this->afternoon_exit)) {
            $schedule[] = 'Tarde: ' . substr($this->afternoon_entry, 0, 5) . ' - ' . substr($this->afternoon_exit, 0, 5);
        }

        return implode(', ', $schedule);
    }

    /**
     * Returns the day of week name translated
     *
     * @return string
     */
    public function getDayOfWeekName(): string
    {
        $days = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday'
        ];

        return isset($days[$this->dayofweek]) ?
            $this->toolBox()->i18n()->trans($days[$this->dayofweek]) :
            (string)$this->dayofweek;
    }

    /**
     * Returns array of days of week for select widget
     *
     * @return array
     */
    public static function getDaysOfWeek(): array
    {
        $i18n = new \FacturaScripts\Core\Base\Translator();
        return [
            1 => $i18n->trans('monday'),
            2 => $i18n->trans('tuesday'),
            3 => $i18n->trans('wednesday'),
            4 => $i18n->trans('thursday'),
            5 => $i18n->trans('friday'),
            6 => $i18n->trans('saturday'),
            7 => $i18n->trans('sunday')
        ];
    }
}
