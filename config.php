<?php

//error_log("loading config");

$GLOBALS['config'] = [];
$configPath = realpath(__DIR__ . '/../../config/rockband_scheduler_config.ini');
if ($configPath && file_exists($configPath)) {
    $GLOBALS['config'] = parse_ini_file($configPath, true) ?: [];
    //error_log('config loaded');
}
unset($configPath);

// Default settings for signup requirements
if (!isset($GLOBALS['config']['signup']) || !is_array($GLOBALS['config']['signup'])) {
    $GLOBALS['config']['signup'] = [];
}
// Default: require both name and song for user signups
$GLOBALS['config']['signup']['require_name'] =
    ($GLOBALS['config']['signup']['require_name'] ?? '1') === '1';
$GLOBALS['config']['signup']['require_song'] =
    ($GLOBALS['config']['signup']['require_song'] ?? '1') === '1';

// Theme settings
if (!isset($GLOBALS['config']['theme']) || !is_array($GLOBALS['config']['theme'])) {
    $GLOBALS['config']['theme'] = [];
}
$GLOBALS['config']['theme']['default_theme_id'] =
    $GLOBALS['config']['theme']['default_theme_id'] ?? null;

?>