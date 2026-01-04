<?php
/**
 * Events API - Admin only
 *
 * GET    /api/events.php                                    - List all events
 * GET    /api/events.php?event_id=uuid                      - Get single event
 * POST   /api/events.php                                    - Create event
 * PUT    /api/events.php?event_id=uuid                      - Update event
 * DELETE /api/events.php?event_id=uuid                      - Delete event
 * POST   /api/events.php?event_id=uuid&action=generate_qr   - Generate QR code
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
$eventId = $_GET['event_id'] ?? null;
$action = $_GET['action'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($eventId) {
                getEvent($db, $eventId);
            } else {
                listEvents($db);
            }
            break;

        case 'POST':
            if ($eventId && $action === 'generate_qr') {
                generateQrCode($db, $eventId);
            } else {
                createEvent($db);
            }
            break;

        case 'PUT':
            if (!$eventId) {
                jsonError('event_id required for update');
            }
            updateEvent($db, $eventId);
            break;

        case 'DELETE':
            if (!$eventId) {
                jsonError('event_id required for delete');
            }
            deleteEvent($db, $eventId);
            break;

        default:
            jsonError('Method not allowed', 405);
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

function createEvent($db) {
    $data = getJsonBody();
    if (!$data) {
        jsonError('Invalid JSON body');
    }

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

    jsonResponse(['success' => true, 'event_id' => $result['event_id']], 201);
}

function updateEvent($db, $eventId) {
    if (!isValidUuid($eventId)) {
        jsonError('Invalid event ID format');
    }

    $data = getJsonBody();
    if (!$data) {
        jsonError('Invalid JSON body');
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

function generateQrCode($db, $eventId) {
    if (!isValidUuid($eventId)) {
        jsonError('Invalid event ID format');
    }

    // Check event exists
    $stmt = $db->prepare('SELECT event_id FROM events WHERE event_id = UUID_TO_BIN(?)');
    $stmt->execute([$eventId]);
    if (!$stmt->fetch()) {
        jsonError('Event not found', 404);
    }

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

    // Use Google Charts API to generate QR code
    $qrApiUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode($eventUrl);

    $qrImage = @file_get_contents($qrApiUrl);
    if ($qrImage === false) {
        jsonError('Failed to generate QR code', 500);
    }

    // Store QR code in database
    $stmt = $db->prepare('UPDATE events SET qr_image = ? WHERE event_id = UUID_TO_BIN(?)');
    $stmt->execute([$qrImage, $eventId]);

    jsonResponse(['success' => true, 'url' => $eventUrl]);
}
?>
