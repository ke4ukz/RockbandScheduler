<?php
/**
 * Rockband Scheduler - Entries API
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
 * PUBLIC (no auth, event must be active):
 *   GET  /api/entries.php?event_id=uuid              - List entries for event
 *   POST /api/entries.php?event_id=uuid              - Create entry (auto-assigns next available slot)
 *        Body: { "performer_name": "...", "song_id": 123 }
 *        Returns: { "success": true, "entry_id": 456, "position": 1 }
 *
 * ADMIN (POST with admin_token in body):
 *   POST /api/entries.php
 *        { "admin_token": "...", "action": "list", "event_id": "uuid" }
 *        { "admin_token": "...", "action": "create", "event_id": "uuid", ... }
 *        { "admin_token": "...", "action": "update", "entry_id": 123, ... }
 *        { "admin_token": "...", "action": "delete", "entry_id": 123 }
 *        { "admin_token": "...", "action": "reorder", "event_id": "uuid", "order": [...] }
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

// Check if this is an admin request (has admin_token or csrf_token with action in body)
$data = getJsonBody();
$isAdminRequest = $data && (isset($data['admin_token']) || (isset($data['csrf_token']) && isset($data['action'])));

try {
    if ($method === 'GET') {
        // Public: list entries for event
        $eventId = $_GET['event_id'] ?? null;
        if (!$eventId) {
            jsonError('event_id required');
        }
        listEntries($db, $eventId, false);

    } elseif ($method === 'POST') {
        if ($isAdminRequest) {
            // Admin request - verify token and handle action
            requireAdminAuth();

            $action = $data['action'] ?? null;
            $eventId = $data['event_id'] ?? null;
            $entryId = $data['entry_id'] ?? null;

            switch ($action) {
                case 'list':
                    if (!$eventId) {
                        jsonError('event_id required');
                    }
                    listEntries($db, $eventId, true);
                    break;

                case 'create':
                    if (!$eventId) {
                        jsonError('event_id required');
                    }
                    adminCreateEntry($db, $eventId, $data);
                    break;

                case 'update':
                    if (!$entryId) {
                        jsonError('entry_id required');
                    }
                    updateEntry($db, $entryId, $data);
                    break;

                case 'delete':
                    if (!$entryId) {
                        jsonError('entry_id required');
                    }
                    deleteEntry($db, $entryId);
                    break;

                case 'reorder':
                    if (!$eventId) {
                        jsonError('event_id required');
                    }
                    reorderEntries($db, $eventId, $data);
                    break;

                default:
                    jsonError('Invalid action. Use: list, create, update, delete, reorder');
            }
        } else {
            // Public user signup
            $eventId = $_GET['event_id'] ?? null;
            if (!$eventId) {
                jsonError('event_id required');
            }
            userCreateEntry($db, $eventId, $data);
        }
    } else {
        jsonError('Method not allowed', 405);
    }
} catch (PDOException $e) {
    error_log('Entries API error: ' . $e->getMessage());
    jsonError('Database error', 500);
}

/**
 * Check if event exists
 * Returns event data if valid, or calls jsonError if not
 * Note: No time-based checks - if someone has the URL/QR code, they can access
 */
function validateEventAccess($db, $eventId) {
    if (!isValidUuid($eventId)) {
        jsonError('Invalid event ID format');
    }

    $stmt = $db->prepare('
        SELECT BIN_TO_UUID(event_id) as event_id, name, start_time, end_time, num_entries
        FROM events
        WHERE event_id = UUID_TO_BIN(?)
    ');
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        jsonError('Event not found', 404);
    }

    return $event;
}

function listEntries($db, $eventId, $isAdmin) {
    $event = validateEventAccess($db, $eventId);

    $stmt = $db->prepare('
        SELECT e.entry_id, e.position, e.performer_name, e.modified, e.finished,
               s.song_id, s.title, s.artist, s.album, s.deezer_id,
               TO_BASE64(s.album_art) as album_art
        FROM entries e
        LEFT JOIN songs s ON e.song_id = s.song_id
        WHERE e.event_id = UUID_TO_BIN(?)
        ORDER BY e.position ASC
    ');
    $stmt->execute([$eventId]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert types
    foreach ($entries as &$entry) {
        $entry['entry_id'] = (int)$entry['entry_id'];
        $entry['position'] = (int)$entry['position'];
        $entry['song_id'] = $entry['song_id'] ? (int)$entry['song_id'] : null;
        $entry['finished'] = (bool)$entry['finished'];
    }

    // Also fetch available songs for user selection
    $stmt = $db->query('
        SELECT song_id, artist, title, deezer_id,
               TO_BASE64(album_art) as album_art
        FROM songs
        ORDER BY artist, title
    ');
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($songs as &$song) {
        $song['song_id'] = (int)$song['song_id'];
    }

    jsonResponse([
        'event' => $event,
        'entries' => $entries,
        'songs' => $songs,
        'total_slots' => (int)$event['num_entries']
    ]);
}

function userCreateEntry($db, $eventId, $data) {
    $event = validateEventAccess($db, $eventId);

    if (!$data) {
        jsonError('Invalid JSON body');
    }

    $performerName = trim($data['performer_name'] ?? '');
    $songId = $data['song_id'] ?? null;

    // Both name and song are required for public signup
    if (empty($performerName)) {
        jsonError('performer_name is required');
    }
    if (mb_strlen($performerName) > 150) {
        jsonError('performer_name is too long (max 150 characters)');
    }
    if (empty($songId)) {
        jsonError('song_id is required');
    }

    // Verify song exists
    $stmt = $db->prepare('SELECT song_id FROM songs WHERE song_id = ?');
    $stmt->execute([$songId]);
    if (!$stmt->fetch()) {
        jsonError('Song not found', 404);
    }

    // Retry loop to handle race conditions when two users try to claim the same slot
    // The unique constraint (event_id, position) prevents duplicates at the database level
    $maxRetries = 3;
    $attempt = 0;
    $entryId = null;
    $position = null;

    while ($attempt < $maxRetries) {
        $attempt++;

        // Find next available position (auto-assign)
        $stmt = $db->prepare('
            SELECT position FROM entries
            WHERE event_id = UUID_TO_BIN(?)
        ');
        $stmt->execute([$eventId]);
        $takenPositions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $position = null;
        for ($i = 1; $i <= $event['num_entries']; $i++) {
            if (!in_array($i, $takenPositions)) {
                $position = $i;
                break;
            }
        }

        if ($position === null) {
            jsonError('All slots are full', 409);
        }

        // Try to create entry - may fail with duplicate key if another request took this slot
        try {
            $stmt = $db->prepare('
                INSERT INTO entries (event_id, song_id, position, performer_name)
                VALUES (UUID_TO_BIN(?), ?, ?, ?)
            ');
            $stmt->execute([
                $eventId,
                $songId,
                $position,
                $performerName
            ]);

            $entryId = $db->lastInsertId();
            break; // Success - exit retry loop

        } catch (PDOException $e) {
            // Check for duplicate key error (MySQL error 1062)
            if ($e->getCode() == 23000 && strpos($e->getMessage(), '1062') !== false) {
                // Another request claimed this slot - retry with next available
                if ($attempt >= $maxRetries) {
                    error_log("Entry creation failed after $maxRetries retries for event $eventId");
                    jsonError('Unable to claim slot, please try again', 409);
                }
                // Continue to next iteration
            } else {
                // Different error - rethrow
                throw $e;
            }
        }
    }

    // Update song selection stats
    updateSongSelectionStats($db, $songId);

    jsonResponse([
        'success' => true,
        'entry_id' => (int)$entryId,
        'position' => $position
    ], 201);
}

function adminCreateEntry($db, $eventId, $data) {
    $event = validateEventAccess($db, $eventId);

    if (empty($data['position'])) {
        jsonError('position is required');
    }

    $position = (int)$data['position'];
    if ($position < 1 || $position > $event['num_entries']) {
        jsonError('Invalid position');
    }

    // Check if position exists - if so, update it
    $stmt = $db->prepare('
        SELECT entry_id FROM entries
        WHERE event_id = UUID_TO_BIN(?) AND position = ?
    ');
    $stmt->execute([$eventId, $position]);
    $existing = $stmt->fetch();

    $songId = $data['song_id'] ?? null;
    $performerName = trim($data['performer_name'] ?? '');

    // Validate performer_name length
    if (mb_strlen($performerName) > 150) {
        jsonError('performer_name is too long (max 150 characters)');
    }

    if ($existing) {
        // Get the current song_id to check if it changed
        $stmt = $db->prepare('SELECT song_id FROM entries WHERE entry_id = ?');
        $stmt->execute([$existing['entry_id']]);
        $currentEntry = $stmt->fetch(PDO::FETCH_ASSOC);
        $oldSongId = $currentEntry['song_id'];

        // Update existing entry
        $stmt = $db->prepare('
            UPDATE entries SET
                performer_name = ?,
                song_id = ?
            WHERE entry_id = ?
        ');
        $stmt->execute([
            $performerName,
            $songId,
            $existing['entry_id']
        ]);

        // Update song selection stats if the song changed
        if ($songId && $songId != $oldSongId) {
            updateSongSelectionStats($db, $songId);
        }

        jsonResponse(['success' => true, 'entry_id' => (int)$existing['entry_id']]);
    } else {
        // Create new entry
        $stmt = $db->prepare('
            INSERT INTO entries (event_id, song_id, position, performer_name)
            VALUES (UUID_TO_BIN(?), ?, ?, ?)
        ');
        $stmt->execute([
            $eventId,
            $songId,
            $position,
            $performerName
        ]);

        // Update song selection stats if a song was selected
        if ($songId) {
            updateSongSelectionStats($db, $songId);
        }

        jsonResponse(['success' => true, 'entry_id' => (int)$db->lastInsertId()], 201);
    }
}

function updateEntry($db, $entryId, $data) {
    // Check entry exists and get current song_id
    $stmt = $db->prepare('SELECT entry_id, event_id, song_id FROM entries WHERE entry_id = ?');
    $stmt->execute([$entryId]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$entry) {
        jsonError('Entry not found', 404);
    }

    $oldSongId = $entry['song_id'];

    // Build update
    $fields = [];
    $values = [];

    if (array_key_exists('performer_name', $data)) {
        $performerName = trim($data['performer_name'] ?? '');
        if (mb_strlen($performerName) > 150) {
            jsonError('performer_name is too long (max 150 characters)');
        }
        $fields[] = 'performer_name = ?';
        $values[] = $performerName;
    }

    $newSongId = null;
    if (array_key_exists('song_id', $data)) {
        $fields[] = 'song_id = ?';
        $values[] = $data['song_id'];
        $newSongId = $data['song_id'];
    }

    if (array_key_exists('position', $data)) {
        $fields[] = 'position = ?';
        $values[] = (int)$data['position'];
    }

    if (array_key_exists('finished', $data)) {
        $fields[] = 'finished = ?';
        $values[] = $data['finished'] ? 1 : 0;
    }

    if (empty($fields)) {
        jsonError('No fields to update');
    }

    $values[] = $entryId;
    $sql = 'UPDATE entries SET ' . implode(', ', $fields) . ' WHERE entry_id = ?';

    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    // Update song selection stats if the song changed
    if ($newSongId !== null && $newSongId != $oldSongId && $newSongId) {
        updateSongSelectionStats($db, $newSongId);
    }

    jsonResponse(['success' => true]);
}

function deleteEntry($db, $entryId) {
    $stmt = $db->prepare('DELETE FROM entries WHERE entry_id = ?');
    $stmt->execute([$entryId]);

    if ($stmt->rowCount() === 0) {
        jsonError('Entry not found', 404);
    }

    jsonResponse(['success' => true]);
}

function reorderEntries($db, $eventId, $data) {
    if (!isValidUuid($eventId)) {
        jsonError('Invalid event ID format');
    }

    if (!isset($data['order']) || !is_array($data['order'])) {
        jsonError('order array required');
    }

    // order should be array of { entry_id, position }
    // Validate all items before starting transaction
    foreach ($data['order'] as $item) {
        if (!isset($item['entry_id']) || !isset($item['position'])) {
            jsonError('Each order item must have entry_id and position');
        }
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('UPDATE entries SET position = ? WHERE entry_id = ? AND event_id = UUID_TO_BIN(?)');

        foreach ($data['order'] as $item) {
            $stmt->execute([(int)$item['position'], (int)$item['entry_id'], $eventId]);
        }

        $db->commit();
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Entries reorder error: ' . $e->getMessage());
        jsonError('Failed to reorder entries', 500);
    }
}

/**
 * Update song selection statistics
 * Increments selection_count and sets last_selected to current timestamp
 */
function updateSongSelectionStats($db, $songId) {
    $stmt = $db->prepare('
        UPDATE songs
        SET selection_count = selection_count + 1,
            last_selected = NOW()
        WHERE song_id = ?
    ');
    $stmt->execute([$songId]);
}
?>
