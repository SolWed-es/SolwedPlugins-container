<?php

namespace FacturaScripts\Plugins\BackupSetting;

use FacturaScripts\Core\Template\InitClass;

class Init extends InitClass
{
    /**
     * Initialize the plugin.
     * We avoid calling unavailable classes from Init to prevent fatal errors.
     */
    public function init(): void
    {
        // Intentionally left blank. The controller registers itself on the menu
        // by setting showonmenu in getPageData().
    }

    public function update(): void
    {
        // No update logic needed yet.
    }

    /**
     * Required by InitClass. Called when the plugin is uninstalled.
     * Leave empty if there is no cleanup to perform.
     */
    public function uninstall(): void
    {
        // No uninstall logic needed yet.
    }
}