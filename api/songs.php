<?php
/**
 * Songs API - Admin only (all operations use POST with admin_token in body)
 *
 * All requests are POST with JSON body containing admin_token and action:
 *
 * POST /api/songs.php
 *   { "admin_token": "...", "action": "list" }                    - List all songs
 *   { "admin_token": "...", "action": "get", "song_id": 123 }     - Get single song
 *   { "admin_token": "...", "action": "create", "title": "...", ... } - Create song
 *   { "admin_token": "...", "action": "update", "song_id": 123, ... } - Update song
 *   { "admin_token": "...", "action": "delete", "song_id": 123 }  - Delete song
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

// Only accept POST
if ($method !== 'POST') {
    jsonError('Method not allowed. Use POST with action in body.', 405);
}

$data = getJsonBody();
if (!$data) {
    jsonError('Invalid JSON body');
}

$action = $data['action'] ?? null;
$songId = $data['song_id'] ?? null;

try {
    switch ($action) {
        case 'list':
            listSongs($db);
            break;

        case 'get':
            if (!$songId) {
                jsonError('song_id required');
            }
            getSong($db, $songId);
            break;

        case 'create':
            createSong($db, $data);
            break;

        case 'update':
            if (!$songId) {
                jsonError('song_id required for update');
            }
            updateSong($db, $songId, $data);
            break;

        case 'delete':
            if (!$songId) {
                jsonError('song_id required for delete');
            }
            deleteSong($db, $songId);
            break;

        default:
            jsonError('Invalid action. Use: list, get, create, update, delete');
    }
} catch (PDOException $e) {
    error_log('Songs API error: ' . $e->getMessage());

    // Provide more helpful error messages for common issues
    $msg = $e->getMessage();
    if (strpos($msg, 'Data too long') !== false) {
        // Extract which column is too long
        if (preg_match("/column '(\w+)'/", $msg, $matches)) {
            jsonError("Data too long for {$matches[1]} field", 400);
        }
        jsonError('Data too long for database field', 400);
    } elseif (strpos($msg, 'Duplicate entry') !== false) {
        jsonError('Song already exists', 409);
    } elseif (strpos($msg, 'Incorrect string value') !== false) {
        jsonError('Invalid characters in song data (encoding issue)', 400);
    }

    // Return the actual error for debugging (sanitize sensitive info)
    $safeMsg = preg_replace('/SQLSTATE\[\w+\]:\s*/', '', $msg);
    $safeMsg = preg_replace('/at row \d+/', '', $safeMsg);
    jsonError('Database error: ' . $safeMsg, 500);
}

function listSongs($db) {
    $stmt = $db->query('
        SELECT song_id, artist, album, title, year, duration, deezer_id,
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
        SELECT song_id, artist, album, title, year, duration, deezer_id,
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

function createSong($db, $data) {
    // Validate required fields
    $required = ['title', 'artist', 'album', 'year'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonError("$field is required");
        }
    }

    // Check for existing song if skip_existing is set
    if (!empty($data['skip_existing'])) {
        // Prefer deezer_id for duplicate check (exact track match)
        // Fall back to title+artist if no deezer_id
        if (!empty($data['deezer_id'])) {
            $stmt = $db->prepare('SELECT song_id FROM songs WHERE deezer_id = ?');
            $stmt->execute([$data['deezer_id']]);
        } else {
            $stmt = $db->prepare('
                SELECT song_id FROM songs
                WHERE LOWER(title) = LOWER(?) AND LOWER(artist) = LOWER(?)
            ');
            $stmt->execute([$data['title'], $data['artist']]);
        }
        if ($stmt->fetch()) {
            jsonError('Song already exists', 409);
        }
    }

    // Fetch album art if URL provided
    $albumArt = null;
    if (!empty($data['album_art_url'])) {
        $albumArt = fetchImageAsBlob($data['album_art_url']);
    }

    $stmt = $db->prepare('
        INSERT INTO songs (artist, album, title, year, duration, deezer_id, album_art)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $data['artist'],
        $data['album'],
        $data['title'],
        (int)$data['year'],
        (int)($data['duration'] ?? 0),
        $data['deezer_id'] ?? null,
        $albumArt
    ]);

    $songId = $db->lastInsertId();

    jsonResponse(['success' => true, 'song_id' => (int)$songId], 201);
}

function updateSong($db, $songId, $data) {
    // Check song exists
    $stmt = $db->prepare('SELECT song_id FROM songs WHERE song_id = ?');
    $stmt->execute([$songId]);
    if (!$stmt->fetch()) {
        jsonError('Song not found', 404);
    }

    // Build dynamic update query
    $fields = [];
    $values = [];

    $allowedFields = ['artist', 'album', 'title', 'year', 'duration', 'deezer_id'];
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

function fetchImageAsBlob($url, $maxSize = 65535) {
    // Try cURL first, fallback to file_get_contents
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) {
            $imageData = false;
        }
    } elseif (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'RockbandScheduler/1.0'
            ]
        ]);
        $imageData = @file_get_contents($url, false, $ctx);
    } else {
        return null;
    }

    if ($imageData === false) {
        return null;
    }

    // Verify it's actually an image
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);
    if (strpos($mimeType, 'image/') !== 0) {
        return null;
    }

    // If image is too large, try to compress it
    if (strlen($imageData) > $maxSize && function_exists('imagecreatefromstring')) {
        $imageData = compressImage($imageData, $maxSize, $mimeType);
    }

    // If still too large after compression, skip it
    if (strlen($imageData) > $maxSize) {
        error_log("Image too large after compression: " . strlen($imageData) . " bytes (max: $maxSize)");
        return null;
    }

    return $imageData;
}

/**
 * Compress an image to fit within maxSize bytes
 */
function compressImage($imageData, $maxSize, $mimeType) {
    $image = @imagecreatefromstring($imageData);
    if (!$image) {
        return $imageData;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    // Try progressively smaller sizes and lower quality
    $scales = [1.0, 0.75, 0.5, 0.35, 0.25];
    $qualities = [85, 70, 55, 40];

    foreach ($scales as $scale) {
        $newWidth = (int)($width * $scale);
        $newHeight = (int)($height * $scale);

        if ($newWidth < 50 || $newHeight < 50) {
            continue; // Don't go smaller than 50px
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        foreach ($qualities as $quality) {
            ob_start();
            if ($mimeType === 'image/png') {
                // PNG quality is 0-9 (0 = no compression, 9 = max)
                $pngQuality = (int)(9 - ($quality / 10));
                imagepng($resized, null, $pngQuality);
            } else {
                imagejpeg($resized, null, $quality);
            }
            $compressed = ob_get_clean();

            if (strlen($compressed) <= $maxSize) {
                imagedestroy($resized);
                imagedestroy($image);
                return $compressed;
            }
        }

        imagedestroy($resized);
    }

    imagedestroy($image);
    return $imageData; // Return original if we couldn't compress enough
}
?>
