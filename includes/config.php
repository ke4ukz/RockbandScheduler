<?php
/**
 * Rockband Scheduler - Configuration Loader
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

//error_log("loading config");

$GLOBALS['config'] = [];
$configPath = realpath(__DIR__ . '/../../../config/rockband_scheduler_config.ini');
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

// Content filter settings (Sightengine)
if (!isset($GLOBALS['config']['content_filter']) || !is_array($GLOBALS['config']['content_filter'])) {
    $GLOBALS['config']['content_filter'] = [];
}
// Default filter settings - all disabled by default
// Profanity levels: 0=off, 1=low, 2=medium, 3=high
$GLOBALS['config']['content_filter']['profanity_sexual'] =
    (int)($GLOBALS['config']['content_filter']['profanity_sexual'] ?? 0);
$GLOBALS['config']['content_filter']['profanity_discriminatory'] =
    (int)($GLOBALS['config']['content_filter']['profanity_discriminatory'] ?? 0);
$GLOBALS['config']['content_filter']['profanity_insult'] =
    (int)($GLOBALS['config']['content_filter']['profanity_insult'] ?? 0);
$GLOBALS['config']['content_filter']['profanity_inappropriate'] =
    (int)($GLOBALS['config']['content_filter']['profanity_inappropriate'] ?? 0);
$GLOBALS['config']['content_filter']['profanity_grawlix'] =
    (int)($GLOBALS['config']['content_filter']['profanity_grawlix'] ?? 0);
// Other content blocks are boolean toggles
$GLOBALS['config']['content_filter']['block_extremism'] =
    ($GLOBALS['config']['content_filter']['block_extremism'] ?? '0') === '1';
$GLOBALS['config']['content_filter']['block_violence'] =
    ($GLOBALS['config']['content_filter']['block_violence'] ?? '0') === '1';
$GLOBALS['config']['content_filter']['block_drugs'] =
    ($GLOBALS['config']['content_filter']['block_drugs'] ?? '0') === '1';

?>