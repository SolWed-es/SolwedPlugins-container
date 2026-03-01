<?php

namespace FacturaScripts\Plugins\BackupSetting;

use FacturaScripts\Core\Tools;

class Config
{
    private const FILE_NAME = 'BackupSetting.json';

    private static function filePath(): string
    {
        return Tools::folder('MyFiles', 'Config', self::FILE_NAME);
    }

    public static function load(): array
    {
        $file = self::filePath();
        if (!file_exists($file)) {
            return [];
        }

        $raw = @file_get_contents($file);
        $data = json_decode((string) $raw, true);
        return is_array($data) ? $data : [];
    }

    public static function getFrequency(): string
    {
        $value = self::load()['frequency'] ?? 'weekly';
        return in_array($value, ['daily', 'weekly', 'monthly'], true) ? $value : 'weekly';
    }

    public static function saveFrequency(string $frequency): bool
    {
        if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
            return false;
        }

        $folder = Tools::folder('MyFiles', 'Config');
        if (false === Tools::folderCheckOrCreate($folder)) {
            return false;
        }

        $data = self::load();
        $data['frequency'] = $frequency;

        return false !== @file_put_contents(
            self::filePath(),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}