<?php

/**
 * Script to generate plugin-list.json and create ZIP files for each plugin
 */

// Get repository info from environment
$githubRepository = getenv('GITHUB_REPOSITORY') ?? '';
if ($githubRepository) {
    list($repoOwner, $repoName) = explode('/', $githubRepository);
} else {
    // Fallback for local testing
    $repoOwner = 'SolWed-es';
    $repoName = 'SolwedPlugins-container';
}

$pluginsPath = 'plugins';
$zipPath = 'zip';
$outputFile = 'plugin-list.json';

echo "Generating plugin list for: $repoOwner/$repoName\n";

// Create zip directory if it doesn't exist
if (!file_exists($zipPath)) {
    mkdir($zipPath, 0755, true);
}

// Get all plugin directories
$pluginDirs = glob($pluginsPath . '/*', GLOB_ONLYDIR);
$plugins = [];

if (empty($pluginDirs)) {
    echo "No plugin directories found in $pluginsPath/\n";
    file_put_contents($outputFile, json_encode([
        'metadata' => [
            'generated' => date('Y-m-d H:i:s'),
            'repo' => "https://github.com/$repoOwner/$repoName",
            'plugin_count' => 0,
            'warning' => 'No plugins found'
        ],
        'plugins' => []
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    exit(0);
}

foreach ($pluginDirs as $pluginDir) {
    $pluginName = basename($pluginDir);
    $iniFile = $pluginDir . '/facturascripts.ini';

    if (!file_exists($iniFile)) {
        echo "Skipping $pluginName: No facturascripts.ini found\n";
        continue;
    }

    // Parse the INI file
    $iniData = parse_ini_file($iniFile);
    if (!$iniData) {
        echo "Skipping $pluginName: Invalid INI file\n";
        continue;
    }

    // Get last commit date for this plugin
    $lastUpdated = shell_exec("git log -1 --format=%cd --date=short $pluginDir 2>/dev/null") ?? date('Y-m-d');
    $lastUpdated = trim($lastUpdated);

    // Create ZIP file for the plugin
    $zipFileName = "$pluginName.zip";
    $zipFilepath = "$zipPath/$zipFileName";

    if ($this->createPluginZip($pluginDir, $zipFilepath)) {
        echo "Created ZIP for: $pluginName\n";
    } else {
        echo "Failed to create ZIP for: $pluginName\n";
        continue;
    }

    // Extract plugin information
    $plugin = [
        'name' => $iniData['name'] ?? $pluginName,
        'description' => $iniData['description'] ?? 'No description available',
        'version' => floatval($iniData['version'] ?? '0.0'),
        'min_version' => floatval($iniData['min_version'] ?? '0.0'),
        'min_php' => floatval($iniData['min_php'] ?? '7.3'),
        'require' => isset($iniData['require']) ?
            array_map('trim', explode(',', $iniData['require'])) : [],
        'require_php' => isset($iniData['require_php']) ?
            array_map('trim', explode(',', $iniData['require_php'])) : [],
        'download_url' => "https://github.com/$repoOwner/$repoName/raw/main/$zipPath/$zipFileName",
        'last_updated' => $lastUpdated
    ];

    $plugins[] = $plugin;
    echo "Added plugin: {$plugin['name']} v{$plugin['version']}\n";
}

// Generate the JSON file
$jsonData = [
    'metadata' => [
        'generated' => date('Y-m-d H:i:s'),
        'repo' => "https://github.com/$repoOwner/$repoName",
        'plugin_count' => count($plugins),
        'workflow_run' => getenv('GITHUB_RUN_ID') ?: 'manual'
    ],
    'plugins' => $plugins
];

file_put_contents($outputFile, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Generated $outputFile with " . count($plugins) . " plugins\n";

/**
 * Create ZIP file for a plugin directory
 */
function createPluginZip(string $sourcePath, string $zipPath): bool
{
    // Initialize archive object
    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    // Create recursive directory iterator
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourcePath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourcePath) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    return $zip->close();
}
