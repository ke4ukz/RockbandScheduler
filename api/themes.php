<?php
/**
 * Rockband Scheduler - Themes API
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
 *
 * GET /api/themes.php - List all themes (public, for user page to load theme)
 * POST /api/themes.php
 *   { "admin_token": "...", "action": "list" } - List all themes (admin)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$db = $GLOBALS['db'];
if (!$db) {
    jsonError('Database connection failed', 500);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Public endpoint for user pages to get themes
        listThemes($db);
    } elseif ($method === 'POST') {
        // Admin endpoint
        requireAdminAuth();
        $data = getJsonBody();
        $action = $data['action'] ?? 'list';

        switch ($action) {
            case 'list':
                listThemes($db);
                break;
            default:
                jsonError('Invalid action. Use: list');
        }
    } else {
        jsonError('Method not allowed', 405);
    }
} catch (PDOException $e) {
    error_log('Themes API error: ' . $e->getMessage());
    jsonError('Database error', 500);
}

function listThemes($db) {
    $stmt = $db->query('
        SELECT theme_id, name, primary_color, bg_gradient_start, bg_gradient_end, text_color
        FROM themes
        ORDER BY name
    ');
    $themes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert theme_id to int
    foreach ($themes as &$theme) {
        $theme['theme_id'] = (int)$theme['theme_id'];
    }

    jsonResponse(['themes' => $themes]);
}
?>
