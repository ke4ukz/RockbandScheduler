<?php

$GLOBALS['config'] = FALSE;
$configPath = realpath(__DIR__ . '/../../config/rockband_scheduler_config.ini');
if (!file_exists($configPath)) {
    return FALSE;
}
$config = parse_ini_file($configPath, true);
if (!$config) { return FALSE }

$GLOBALS['config'] = $config;

unset($configPath, $config);

?>