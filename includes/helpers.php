<?php
/**
 * Shared helper functions for Rockband Scheduler
 */

/**
 * Convert a binary UUID from the database to a hex string
 */
function uuidFromBin($binary) {
    if ($binary === null) return null;
    $hex = bin2hex($binary);
    return sprintf('%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20)
    );
}

/**
 * Convert a hex UUID string to binary for database storage
 */
function uuidToBin($uuid) {
    if ($uuid === null) return null;
    $hex = str_replace('-', '', $uuid);
    return hex2bin($hex);
}

/**
 * Validate UUID format
 */
function isValidUuid($uuid) {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
}

/**
 * Check if admin token is valid
 * Returns true if token matches, false otherwise
 */
function verifyAdminToken($providedToken) {
    if (empty($providedToken)) return false;
    $configToken = $GLOBALS['config']['admin']['token'] ?? null;
    if (empty($configToken)) return false;
    return hash_equals($configToken, $providedToken);
}

/**
 * Send JSON response and exit
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send error JSON response and exit
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse(['error' => $message], $statusCode);
}

/**
 * Require admin authentication for API endpoint
 * Checks X-Admin-Token header or admin_token parameter
 */
function requireAdminAuth() {
    $token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? $_REQUEST['admin_token'] ?? null;
    if (!verifyAdminToken($token)) {
        jsonError('Unauthorized', 401);
    }
}

/**
 * Get request body as JSON
 */
function getJsonBody() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    return $data;
}

/**
 * Sanitize string for output
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
