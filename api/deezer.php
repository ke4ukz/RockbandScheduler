<?php
/**
 * Rockband Scheduler - Deezer Search Proxy API
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
 * GET /api/deezer.php?q=search+query     - Search for tracks on Deezer
 * GET /api/deezer.php?album_id=12345     - Get album details (for release year)
 * GET /api/deezer.php?track_id=12345     - Get track details (for fresh preview URL)
 *
 * This proxies requests to Deezer's API to avoid CORS issues
 * and to keep the API simple for the frontend.
 */

require_once __DIR__ . '/../includes/config.php';

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
        $error = error_get_last();
        error_log("Deezer API error (track $trackId): " . ($error['message'] ?? 'unknown error'));
        http_response_code(502);
        echo json_encode(['error' => 'Failed to reach Deezer API']);
        exit;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Deezer API error (track $trackId): JSON decode failed - " . json_last_error_msg());
        http_response_code(502);
        echo json_encode(['error' => 'Invalid response from Deezer API']);
        exit;
    }

    if (isset($data['error'])) {
        error_log("Deezer API error (track $trackId): " . ($data['error']['message'] ?? 'unknown'));
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
        $error = error_get_last();
        error_log("Deezer API error (album $albumId): " . ($error['message'] ?? 'unknown error'));
        http_response_code(502);
        echo json_encode(['error' => 'Failed to reach Deezer API']);
        exit;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Deezer API error (album $albumId): JSON decode failed - " . json_last_error_msg());
        http_response_code(502);
        echo json_encode(['error' => 'Invalid response from Deezer API']);
        exit;
    }

    if (isset($data['error'])) {
        error_log("Deezer API error (album $albumId): " . ($data['error']['message'] ?? 'unknown'));
        http_response_code(404);
        echo json_encode(['error' => 'Album not found']);
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
    $error = error_get_last();
    error_log("Deezer API error (search '$query'): " . ($error['message'] ?? 'unknown error'));
    http_response_code(502);
    echo json_encode(['error' => 'Failed to reach Deezer API']);
    exit;
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Deezer API error (search '$query'): JSON decode failed - " . json_last_error_msg());
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from Deezer API']);
    exit;
}

// Pass through the Deezer response
// Structure: { data: [ { id, title, duration, preview, artist: {name}, album: {id, title, cover_small, cover_medium} } ] }
echo json_encode($data);
?>
