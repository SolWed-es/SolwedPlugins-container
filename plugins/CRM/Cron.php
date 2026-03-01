<?php
/**
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM;

use FacturaScripts\Core\Template\CronClass;
use FacturaScripts\Plugins\CRM\CronJob\OldCustomersList;
use FacturaScripts\Plugins\CRM\CronJob\CheckData;
use FacturaScripts\Plugins\CRM\CronJob\NotifyNote;
use FacturaScripts\Plugins\CRM\CronJob\SendCampaign;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class Cron extends CronClass
{
    public function run(): void
    {
        // procesos que se ejecutan varias veces al día
        $this->job(NotifyNote::JOB_NAME)
            ->every('1 hour')
            ->run(function () {
                NotifyNote::run();
            });

        $this->job(SendCampaign::JOB_NAME)
            ->every('1 hour')
            ->run(function () {
                SendCampaign::run();
            });

        // procesos que se ejecutan una vez al mes
        $this->job(CheckData::JOB_NAME)
            ->everyDay(1, 3)
            ->run(function () {
                CheckData::run();
            });

        $this->job(OldCustomersList::JOB_NAME)
            ->everyDay(1, 4)
            ->run(function () {
                OldCustomersList::run();
            });
    }
}
