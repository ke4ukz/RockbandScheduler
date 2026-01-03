<!DOCTYPE html>
<html>
    <head>
        <title>Rockband Scheduler</title>
    </head>
    <body>
<?php
// Ensure this file is outside your web root!
echo(__DIR__ . "\n\n");
$configPath = __DIR__ . '../../config/rockband_scheduler_config.ini'; // Adjust path as needed

file_exists($configPath) || die("Configuration file not found.");

$dbSettings = parse_ini_file($configPath, true);

if (!$dbSettings) {
    die("Failed to parse configuration file.");
}

$dbHost = $dbSettings['database']['host'];
$dbName = $dbSettings['database']['dbname'];
$dbUser = $dbSettings['database']['username'];
$dbPass = $dbSettings['database']['password'];

echo ("config loaded");
?>
    </body>
</html>
