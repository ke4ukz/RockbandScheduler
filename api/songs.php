<?php
/**
 * Songs API - Admin only
 *
 * GET    /api/songs.php              - List all songs
 * GET    /api/songs.php?song_id=123  - Get single song
 * POST   /api/songs.php              - Create song
 * PUT    /api/songs.php?song_id=123  - Update song
 * DELETE /api/songs.php?song_id=123  - Delete song
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

// All songs endpoints require admin auth
requireAdminAuth();

$db = $GLOBALS['db'];
if (!$db) {
    jsonError('Database connection failed', 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$songId = $_GET['song_id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($songId) {
                getSong($db, $songId);
            } else {
                listSongs($db);
            }
            break;

        case 'POST':
            createSong($db);
            break;

        case 'PUT':
            if (!$songId) {
                jsonError('song_id required for update');
            }
            updateSong($db, $songId);
            break;

        case 'DELETE':
            if (!$songId) {
                jsonError('song_id required for delete');
            }
            deleteSong($db, $songId);
            break;

        default:
            jsonError('Method not allowed', 405);
    }
} catch (PDOException $e) {
    error_log('Songs API error: ' . $e->getMessage());
    jsonError('Database error', 500);
}

function listSongs($db) {
    $stmt = $db->query('
        SELECT song_id, artist, album, title, year, duration, deezer_id, preview_url,
               TO_BASE64(album_art) as album_art
        FROM songs
        ORDER BY artist, title
    ');
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert numeric fields
    foreach ($songs as &$song) {
        $song['song_id'] = (int)$song['song_id'];
        $song['year'] = (int)$song['year'];
        $song['duration'] = (int)$song['duration'];
        $song['deezer_id'] = $song['deezer_id'] ? (int)$song['deezer_id'] : null;
    }

    jsonResponse(['songs' => $songs]);
}

function getSong($db, $songId) {
    $stmt = $db->prepare('
        SELECT song_id, artist, album, title, year, duration, deezer_id, preview_url,
               TO_BASE64(album_art) as album_art
        FROM songs
        WHERE song_id = ?
    ');
    $stmt->execute([$songId]);
    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$song) {
        jsonError('Song not found', 404);
    }

    $song['song_id'] = (int)$song['song_id'];
    $song['year'] = (int)$song['year'];
    $song['duration'] = (int)$song['duration'];
    $song['deezer_id'] = $song['deezer_id'] ? (int)$song['deezer_id'] : null;

    jsonResponse(['song' => $song]);
}

function createSong($db) {
    $data = getJsonBody();
    if (!$data) {
        jsonError('Invalid JSON body');
    }

    // Validate required fields
    $required = ['title', 'artist', 'album', 'year'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonError("$field is required");
        }
    }

    // Fetch album art if URL provided
    $albumArt = null;
    if (!empty($data['album_art_url'])) {
        $albumArt = fetchImageAsBlob($data['album_art_url']);
    }

    $stmt = $db->prepare('
        INSERT INTO songs (artist, album, title, year, duration, deezer_id, preview_url, album_art)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $data['artist'],
        $data['album'],
        $data['title'],
        (int)$data['year'],
        (int)($data['duration'] ?? 0),
        $data['deezer_id'] ?? null,
        $data['preview_url'] ?? null,
        $albumArt
    ]);

    $songId = $db->lastInsertId();

    jsonResponse(['success' => true, 'song_id' => (int)$songId], 201);
}

function updateSong($db, $songId) {
    $data = getJsonBody();
    if (!$data) {
        jsonError('Invalid JSON body');
    }

    // Check song exists
    $stmt = $db->prepare('SELECT song_id FROM songs WHERE song_id = ?');
    $stmt->execute([$songId]);
    if (!$stmt->fetch()) {
        jsonError('Song not found', 404);
    }

    // Build dynamic update query
    $fields = [];
    $values = [];

    $allowedFields = ['artist', 'album', 'title', 'year', 'duration', 'deezer_id', 'preview_url'];
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }

    // Handle album art URL
    if (!empty($data['album_art_url'])) {
        $albumArt = fetchImageAsBlob($data['album_art_url']);
        if ($albumArt) {
            $fields[] = 'album_art = ?';
            $values[] = $albumArt;
        }
    }

    if (empty($fields)) {
        jsonError('No fields to update');
    }

    $values[] = $songId;
    $sql = 'UPDATE songs SET ' . implode(', ', $fields) . ' WHERE song_id = ?';

    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    jsonResponse(['success' => true]);
}

function deleteSong($db, $songId) {
    $stmt = $db->prepare('DELETE FROM songs WHERE song_id = ?');
    $stmt->execute([$songId]);

    if ($stmt->rowCount() === 0) {
        jsonError('Song not found', 404);
    }

    jsonResponse(['success' => true]);
}

function fetchImageAsBlob($url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'RockbandScheduler/1.0'
        ]
    ]);

    $imageData = @file_get_contents($url, false, $ctx);
    if ($imageData === false) {
        return null;
    }

    // Verify it's actually an image
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);
    if (strpos($mimeType, 'image/') !== 0) {
        return null;
    }

    return $imageData;
}
?>
