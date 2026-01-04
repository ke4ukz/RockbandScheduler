<?php
/**
 * Deezer Search Proxy API - Public
 *
 * GET /api/deezer.php?q=search+query  - Search for tracks on Deezer
 *
 * This proxies requests to Deezer's API to avoid CORS issues
 * and to keep the API simple for the frontend.
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// No admin auth required - this is a public search proxy

$query = $_GET['q'] ?? '';
$limit = min((int)($_GET['limit'] ?? 25), 50); // Max 50 results

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Search query (q) is required']);
    exit;
}

// Search Deezer API
$deezerUrl = 'https://api.deezer.com/search/track?' . http_build_query([
    'q' => $query,
    'limit' => $limit
]);

$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'RockbandScheduler/1.0'
    ]
]);

$response = @file_get_contents($deezerUrl, false, $ctx);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to reach Deezer API']);
    exit;
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from Deezer API']);
    exit;
}

// Pass through the Deezer response
// Structure: { data: [ { id, title, duration, preview, artist: {name}, album: {title, cover_small, cover_medium} } ] }
echo json_encode($data);
?>
