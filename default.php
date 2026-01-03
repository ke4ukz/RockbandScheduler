<!DOCTYPE html>
<html>
    <head>
        <title>Rockband Scheduler</title>
    </head>
    <body>
<?php

error_log("default");
require_once "config.php" || die("unable to load config");
require_once "db.php" || die("unable to load db");

echo($GLOBALS['config']);
echo($GLOBALS['db']);

?>
    </body>
</html>
