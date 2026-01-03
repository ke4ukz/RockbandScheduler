<?php
error_log("loading db");
$GLOBALS['db'] = FALSE;
if ($GLOBALS['config']!== NULL && $GLOBALS['config'] !== FALSE) {
    try {
        $GLOBALS['db'] = new PDO("mysql:{$GLOBALS['config']['database']['host']}=localhost;dbname={$GLOBALS['config']['database']['dbname']}", $GLOBALS['config']['database']['username'], $GLOBALS['config']['database']['password']);
    } catch (PDOException $e) {
        error_log($e);
        die('error connecting to db');
    }
}

?>