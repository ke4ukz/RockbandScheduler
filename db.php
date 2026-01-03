<?php
error_log("loading db<BR>");
$GLOBALS['db'] = FALSE;
if ($GLOBALS['config'] !== FALSE) {
    try {
        $GLOBALS['db'] = new PDO("mysql:{$GLOBALS['config']['database']['host']}=localhost;dbname={$GLOBALS['config']['database']['dbname']}", $GLOBALS['config']['database']['username'], $GLOBALS['config']['database']['password']);
    } catch (PDOException $e) {
        die($e);
    }
}

?>