<?php
/**
 * Events API - Admin only (all operations use POST with admin_token in body)
 *
 * All requests are POST with JSON body containing admin_token and action:
 *
 * POST /api/events.php
 *   { "admin_token": "...", "action": "list" }                    - List all events
 *   { "admin_token": "...", "action": "get", "event_id": "uuid" } - Get single event
 *   { "admin_token": "...", "action": "create", "name": "...", ... } - Create event (auto-generates QR)
 *   { "admin_token": "...", "action": "update", "event_id": "uuid", ... } - Update event
 *   { "admin_token": "...", "action": "delete", "event_id": "uuid" } - Delete event
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

// All events endpoints require admin auth
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
$eventId = $data['event_id'] ?? null;

try {
    switch ($action) {
        case 'list':
            listEvents($db);
            break;

        case 'get':
            if (!$eventId) {
                jsonError('event_id required');
            }
            getEvent($db, $eventId);
            break;

        case 'create':
            createEvent($db, $data);
            break;

        case 'update':
            if (!$eventId) {
                jsonError('event_id required for update');
            }
            updateEvent($db, $eventId, $data);
            break;

        case 'delete':
            if (!$eventId) {
                jsonError('event_id required for delete');
            }
            deleteEvent($db, $eventId);
            break;

        default:
            jsonError('Invalid action. Use: list, get, create, update, delete');
    }
} catch (PDOException $e) {
    error_log('Events API error: ' . $e->getMessage());
    jsonError('Database error', 500);
}

function listEvents($db) {
    $stmt = $db->query('
        SELECT BIN_TO_UUID(event_id) as event_id, name, location, start_time, end_time, num_entries,
               TO_BASE64(qr_image) as qr_image, created, modified
        FROM events
        ORDER BY start_time DESC
    ');
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert numeric fields
    foreach ($events as &$event) {
        $event['num_entries'] = (int)$event['num_entries'];
    }

    jsonResponse(['events' => $events]);
}

function getEvent($db, $eventId) {
    if (!isValidUuid($eventId)) {
        jsonError('Invalid event ID format');
    }

    $stmt = $db->prepare('
        SELECT BIN_TO_UUID(event_id) as event_id, name, location, start_time, end_time, num_entries,
               TO_BASE64(qr_image) as qr_image, created, modified
        FROM events
        WHERE event_id = UUID_TO_BIN(?)
    ');
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        jsonError('Event not found', 404);
    }

    $event['num_entries'] = (int)$event['num_entries'];

    jsonResponse(['event' => $event]);
}

function createEvent($db, $data) {
    // Validate required fields
    $required = ['name', 'start_time', 'end_time', 'num_entries'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonError("$field is required");
        }
    }

    // Validate times
    $startTime = strtotime($data['start_time']);
    $endTime = strtotime($data['end_time']);
    if (!$startTime || !$endTime) {
        jsonError('Invalid date/time format');
    }
    if ($endTime <= $startTime) {
        jsonError('End time must be after start time');
    }

    // Validate num_entries
    $numEntries = (int)$data['num_entries'];
    if ($numEntries < 1 || $numEntries > 255) {
        jsonError('num_entries must be between 1 and 255');
    }

    // Insert with auto-generated UUID
    $stmt = $db->prepare('
        INSERT INTO events (name, location, start_time, end_time, num_entries)
        VALUES (?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $data['name'],
        $data['location'] ?? null,
        date('Y-m-d H:i:s', $startTime),
        date('Y-m-d H:i:s', $endTime),
        $numEntries
    ]);

    // Get the generated UUID
    $stmt = $db->query('SELECT BIN_TO_UUID(event_id) as event_id FROM events WHERE event_id = LAST_INSERT_ID()');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // MySQL's LAST_INSERT_ID() doesn't work with UUID, so get the most recent
    $stmt = $db->prepare('
        SELECT BIN_TO_UUID(event_id) as event_id FROM events
        WHERE name = ? ORDER BY created DESC LIMIT 1
    ');
    $stmt->execute([$data['name']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $eventId = $result['event_id'];

    // Auto-generate QR code for the new event
    generateQrCodeForEvent($db, $eventId);

    jsonResponse(['success' => true, 'event_id' => $eventId], 201);
}

function updateEvent($db, $eventId, $data) {
    if (!isValidUuid($eventId)) {
        jsonError('Invalid event ID format');
    }

    // Check event exists
    $stmt = $db->prepare('SELECT event_id FROM events WHERE event_id = UUID_TO_BIN(?)');
    $stmt->execute([$eventId]);
    if (!$stmt->fetch()) {
        jsonError('Event not found', 404);
    }

    // Build dynamic update query
    $fields = [];
    $values = [];

    if (array_key_exists('name', $data)) {
        $fields[] = 'name = ?';
        $values[] = $data['name'];
    }

    if (array_key_exists('location', $data)) {
        $fields[] = 'location = ?';
        $values[] = $data['location'];
    }

    if (array_key_exists('start_time', $data)) {
        $startTime = strtotime($data['start_time']);
        if (!$startTime) {
            jsonError('Invalid start_time format');
        }
        $fields[] = 'start_time = ?';
        $values[] = date('Y-m-d H:i:s', $startTime);
    }

    if (array_key_exists('end_time', $data)) {
        $endTime = strtotime($data['end_time']);
        if (!$endTime) {
            jsonError('Invalid end_time format');
        }
        $fields[] = 'end_time = ?';
        $values[] = date('Y-m-d H:i:s', $endTime);
    }

    if (array_key_exists('num_entries', $data)) {
        $numEntries = (int)$data['num_entries'];
        if ($numEntries < 1 || $numEntries > 255) {
            jsonError('num_entries must be between 1 and 255');
        }
        $fields[] = 'num_entries = ?';
        $values[] = $numEntries;
    }

    if (empty($fields)) {
        jsonError('No fields to update');
    }

    $values[] = $eventId;
    $sql = 'UPDATE events SET ' . implode(', ', $fields) . ' WHERE event_id = UUID_TO_BIN(?)';

    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    jsonResponse(['success' => true]);
}

function deleteEvent($db, $eventId) {
    if (!isValidUuid($eventId)) {
        jsonError('Invalid event ID format');
    }

    $stmt = $db->prepare('DELETE FROM events WHERE event_id = UUID_TO_BIN(?)');
    $stmt->execute([$eventId]);

    if ($stmt->rowCount() === 0) {
        jsonError('Event not found', 404);
    }

    jsonResponse(['success' => true]);
}

/**
 * Generate and store QR code for an event (called on create)
 * Returns event URL on success, false on failure
 */
function generateQrCodeForEvent($db, $eventId) {
    // Generate QR code URL
    $baseUrl = $GLOBALS['config']['site']['base_url'] ?? '';
    if (empty($baseUrl)) {
        // Try to determine from request
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $baseUrl = $protocol . '://' . $host . $path;
    }
    $eventUrl = rtrim($baseUrl, '/') . '/?eventid=' . $eventId;

    // Use QR Server API to generate QR code (Google Charts is deprecated)
    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($eventUrl);

    // Try cURL first (more commonly available on shared hosts), fallback to file_get_contents
    $qrImage = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($qrApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $qrImage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) {
            $qrImage = false;
        }
    } elseif (ini_get('allow_url_fopen')) {
        $qrImage = @file_get_contents($qrApiUrl);
    }

    if ($qrImage === false) {
        return false;
    }

    // Store QR code in database
    $stmt = $db->prepare('UPDATE events SET qr_image = ? WHERE event_id = UUID_TO_BIN(?)');
    $stmt->execute([$qrImage, $eventId]);

    return $eventUrl;
}
?>
