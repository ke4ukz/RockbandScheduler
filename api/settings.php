<?php
/**
 * Settings API - Admin only
 *
 * POST /api/settings.php
 *   { "admin_token": "...", "action": "get" }     - Get current settings
 *   { "admin_token": "...", "action": "update", "settings": {...} } - Update settings
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

// All settings endpoints require admin auth
requireAdminAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonError('Method not allowed. Use POST with action in body.', 405);
}

$data = getJsonBody();
if (!$data) {
    jsonError('Invalid JSON body');
}

$action = $data['action'] ?? null;

// Config file path
$configPath = realpath(__DIR__ . '/../../config') . '/rockband_scheduler_config.ini';

switch ($action) {
    case 'get':
        getSettings();
        break;

    case 'update':
        updateSettings($configPath, $data['settings'] ?? []);
        break;

    default:
        jsonError('Invalid action. Use: get, update');
}

function getSettings() {
    $config = $GLOBALS['config'];

    jsonResponse([
        'settings' => [
            'signup' => [
                'require_name' => $config['signup']['require_name'] ?? true,
                'require_song' => $config['signup']['require_song'] ?? true
            ]
        ]
    ]);
}

function updateSettings($configPath, $newSettings) {
    // Read current config file
    $config = $GLOBALS['config'];

    // Update signup settings
    if (isset($newSettings['signup'])) {
        if (!isset($config['signup'])) {
            $config['signup'] = [];
        }
        if (isset($newSettings['signup']['require_name'])) {
            $config['signup']['require_name'] = $newSettings['signup']['require_name'] ? '1' : '0';
        }
        if (isset($newSettings['signup']['require_song'])) {
            $config['signup']['require_song'] = $newSettings['signup']['require_song'] ? '1' : '0';
        }
    }

    // Write back to INI file
    $iniContent = "";

    foreach ($config as $section => $values) {
        if (is_array($values)) {
            $iniContent .= "[$section]\n";
            foreach ($values as $key => $value) {
                // Handle booleans that were converted
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }
                $iniContent .= "$key = \"$value\"\n";
            }
            $iniContent .= "\n";
        }
    }

    if (!is_writable(dirname($configPath))) {
        jsonError('Config directory is not writable', 500);
    }

    if (file_put_contents($configPath, $iniContent) === false) {
        jsonError('Failed to write config file', 500);
    }

    // Re-read to return updated settings
    $GLOBALS['config'] = parse_ini_file($configPath, true);

    // Re-apply defaults
    if (!isset($GLOBALS['config']['signup'])) {
        $GLOBALS['config']['signup'] = [];
    }
    $GLOBALS['config']['signup']['require_name'] =
        ($GLOBALS['config']['signup']['require_name'] ?? '1') === '1';
    $GLOBALS['config']['signup']['require_song'] =
        ($GLOBALS['config']['signup']['require_song'] ?? '1') === '1';

    getSettings();
}
?>
