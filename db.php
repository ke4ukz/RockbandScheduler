<?php
/**
 * Rockband Scheduler - Database Connection
 * Copyright (C) 2026 Jonathan Dean
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

$GLOBALS['db'] = FALSE;
if ($GLOBALS['config'] !== NULL && $GLOBALS['config'] !== FALSE) {
    try {
        $host = $GLOBALS['config']['database']['host'];
        $name = $GLOBALS['config']['database']['name'];
        $user = $GLOBALS['config']['database']['user'];
        $pass = $GLOBALS['config']['database']['pass'];
        $GLOBALS['db'] = new PDO("mysql:host={$host};dbname={$name}", $user, $pass);
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        die('Error connecting to database');
    }
}

?>