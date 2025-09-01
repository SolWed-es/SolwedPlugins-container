<?php

/**
 * Script to generate plugin-list.json from GitHub repository structure
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
$outputFile = 'plugin-list.json';

echo "Generating plugin list for: $repoOwner/$repoName\n";

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
        'download_url' => "https://github.com/{$repoOwner}/{$repoName}/raw/main/plugins/{$pluginName}/{$pluginName}.zip",
        'health' => 5,
        'last_updated' => $lastUpdated,
        'compatibility' => checkCompatibility(
            floatval($iniData['min_version'] ?? '0.0'),
            floatval($iniData['min_php'] ?? '7.3')
        )
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
 * Check plugin compatibility with current FacturaScripts versions
 */
function checkCompatibility($minVersion, $minPhp): array
{
    // Simulate current versions for compatibility scoring
    $currentFsVersion = 2023.06;
    $currentPhpVersion = PHP_VERSION;

    $fsCompatible = $minVersion <= $currentFsVersion;
    $phpCompatible = version_compare($currentPhpVersion, $minPhp, '>=');

    $score = 5; // Default score

    if (!$fsCompatible) {
        $score -= 2;
    }

    if (!$phpCompatible) {
        $score -= 2;
    }

    return [
        'fs_version' => $fsCompatible,
        'php_version' => $phpCompatible,
        'score' => max(1, $score), // Minimum score of 1
        'current_fs_version' => $currentFsVersion,
        'current_php_version' => $currentPhpVersion
    ];
}
