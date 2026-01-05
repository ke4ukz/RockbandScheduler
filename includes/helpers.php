<?php
/**
 * Rockband Scheduler - Shared Helper Functions
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
 * Start admin session with secure settings
 * Call this at the top of admin pages
 */
function startAdminSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session cookie parameters
        session_set_cookie_params([
            'lifetime' => 0, // Session cookie (expires when browser closes)
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }

    // Mark this session as admin-authenticated
    // The /admin/ directory is protected by HTTP Basic Auth,
    // so if we reach this code, the user has already authenticated
    $_SESSION['admin_authenticated'] = true;

    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Get the CSRF token for the current session
 */
function getCsrfToken() {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Verify CSRF token from request
 */
function verifyCsrfToken($token) {
    if (empty($token)) return false;
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken)) return false;
    return hash_equals($sessionToken, $token);
}

/**
 * Check if the current request has a valid admin session
 */
function hasValidAdminSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Start session to check, but with same secure params
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
    return !empty($_SESSION['admin_authenticated']);
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
 * Accepts either:
 * 1. Session auth with CSRF token (for browser requests from admin pages)
 * 2. Admin token (for curl/scripts) - from JSON body, header, or query param
 */
function requireAdminAuth() {
    // Method 1: Check for valid admin session + CSRF token
    // This is the preferred method for browser requests
    $jsonBody = getJsonBodyCached();
    $csrfToken = $jsonBody['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if ($csrfToken && hasValidAdminSession() && verifyCsrfToken($csrfToken)) {
        return; // Authenticated via session
    }

    // Method 2: Check for admin token (for curl/scripts/backwards compatibility)
    $token = null;
    if ($jsonBody && isset($jsonBody['admin_token'])) {
        $token = $jsonBody['admin_token'];
    }
    // Fallback to header
    if (!$token) {
        $token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? null;
    }
    // Legacy fallback to query param (for backwards compatibility)
    if (!$token) {
        $token = $_GET['admin_token'] ?? null;
    }

    if (verifyAdminToken($token)) {
        return; // Authenticated via token
    }

    jsonError('Unauthorized', 401);
}

/**
 * Cache for JSON body to avoid multiple reads
 */
function getJsonBodyCached() {
    static $cached = null;
    static $parsed = false;

    if (!$parsed) {
        $parsed = true;
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $cached = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $cached = null;
            }
        }
    }
    return $cached;
}

/**
 * Get request body as JSON (uses cached version)
 */
function getJsonBody() {
    return getJsonBodyCached();
}

/**
 * Sanitize string for output
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
