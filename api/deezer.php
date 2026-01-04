<?php
/**
 * Deezer Search Proxy API - Public
 *
 * GET /api/deezer.php?q=search+query     - Search for tracks on Deezer
 * GET /api/deezer.php?album_id=12345     - Get album details (for release year)
 * GET /api/deezer.php?track_id=12345     - Get track details (for fresh preview URL)
 *
 * This proxies requests to Deezer's API to avoid CORS issues
 * and to keep the API simple for the frontend.
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// No admin auth required - this is a public search proxy

$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'RockbandScheduler/1.0'
    ]
]);

// Track details request (for fresh preview URL)
$trackId = $_GET['track_id'] ?? null;
if ($trackId) {
    $trackId = (int)$trackId;
    $deezerUrl = "https://api.deezer.com/track/{$trackId}";

    $response = @file_get_contents($deezerUrl, false, $ctx);

    if ($response === false) {
        http_response_code(502);
        echo json_encode(['error' => 'Failed to reach Deezer API']);
        exit;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || isset($data['error'])) {
        http_response_code(404);
        echo json_encode(['error' => 'Track not found']);
        exit;
    }

    // Return just the fields we need
    echo json_encode([
        'id' => $data['id'],
        'title' => $data['title'],
        'preview' => $data['preview'] ?? null
    ]);
    exit;
}

// Album details request
$albumId = $_GET['album_id'] ?? null;
if ($albumId) {
    $albumId = (int)$albumId;
    $deezerUrl = "https://api.deezer.com/album/{$albumId}";

    $response = @file_get_contents($deezerUrl, false, $ctx);

    if ($response === false) {
        http_response_code(502);
        echo json_encode(['error' => 'Failed to reach Deezer API']);
        exit;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || isset($data['error'])) {
        http_response_code(502);
        echo json_encode(['error' => 'Invalid response from Deezer API']);
        exit;
    }

    // Return just the fields we need
    echo json_encode([
        'id' => $data['id'],
        'title' => $data['title'],
        'release_date' => $data['release_date'] ?? null,
        'year' => isset($data['release_date']) ? (int)substr($data['release_date'], 0, 4) : null
    ]);
    exit;
}

// Track search request
$query = $_GET['q'] ?? '';
$limit = min((int)($_GET['limit'] ?? 25), 50); // Max 50 results

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Search query (q), album_id, or track_id is required']);
    exit;
}

// Search Deezer API
$deezerUrl = 'https://api.deezer.com/search/track?' . http_build_query([
    'q' => $query,
    'limit' => $limit
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
// Structure: { data: [ { id, title, duration, preview, artist: {name}, album: {id, title, cover_small, cover_medium} } ] }
echo json_encode($data);
?>
