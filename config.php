<?php

error_log("loading config<BR>");

$GLOBALS['config'] = FALSE;
$configPath = realpath(__DIR__ . '/../../config/rockband_scheduler_config.ini');
if (file_exists($configPath)) {
    $config = parse_ini_file($configPath, true);
    if ($config) {
        $GLOBALS['config'] = $config;
    }
}



unset($configPath, $config);

?>