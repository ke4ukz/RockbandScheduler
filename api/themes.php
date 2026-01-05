<?php
/**
 * Themes API - Admin operations for listing themes
 *
 * GET /api/themes.php - List all themes (public, for user page to load theme)
 * POST /api/themes.php
 *   { "admin_token": "...", "action": "list" } - List all themes (admin)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
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
