<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = $GLOBALS['db'];
$adminToken = $GLOBALS['config']['admin']['token'] ?? '';

// Get quick stats
$songCount = 0;
$eventCount = 0;

if ($db) {
    try {
        $songCount = $db->query('SELECT COUNT(*) FROM songs')->fetchColumn();
        $eventCount = $db->query('SELECT COUNT(*) FROM events')->fetchColumn();
    } catch (PDOException $e) {
        error_log('Dashboard stats error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rockband Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="default.php">Rockband Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="songs.php">Songs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">Settings</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Dashboard</h1>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <div class="col">
                <div class="card text-bg-primary h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-music-note-list"></i> Song Library</h5>
                        <p class="card-text display-4"><?= $songCount ?></p>
                        <div class="mt-auto">
                            <a href="songs.php" class="btn btn-light">Manage Songs</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card text-bg-info h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-calendar-event"></i> Total Events</h5>
                        <p class="card-text display-4"><?= $eventCount ?></p>
                        <div class="mt-auto">
                            <a href="events.php" class="btn btn-light">Manage Events</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card text-bg-success h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-broadcast"></i> Active Now</h5>
                        <p class="card-text display-4" id="activeCount">-</p>
                        <div id="activeEventsList" class="mb-2"></div>
                        <div class="mt-auto">
                            <a href="events.php" class="btn btn-light" id="viewEventsBtn">View Events</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="songs.php?action=add" class="btn btn-outline-primary">
                                <i class="bi bi-plus-lg"></i> Add Song
                            </a>
                            <a href="events.php?action=add" class="btn btn-outline-primary">
                                <i class="bi bi-plus-lg"></i> Create Event
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ADMIN_TOKEN = <?= json_encode($adminToken) ?>;
        const API_BASE = '../api';

        document.addEventListener('DOMContentLoaded', loadActiveCount);

        async function loadActiveCount() {
            try {
                const response = await fetch(`${API_BASE}/events.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_token: ADMIN_TOKEN, action: 'list' })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                const now = new Date();
                const activeEvents = (data.events || []).filter(event => {
                    const start = new Date(event.start_time);
                    const end = new Date(event.end_time);
                    return now >= start && now <= end;
                });

                document.getElementById('activeCount').textContent = activeEvents.length;

                // Show links to active events
                const listEl = document.getElementById('activeEventsList');
                if (activeEvents.length > 0) {
                    listEl.innerHTML = activeEvents.map(event =>
                        `<a href="entries.php?eventid=${event.event_id}" class="btn btn-light btn-sm mb-2 me-2">
                            <i class="bi bi-list-ol"></i> ${escapeHtml(event.name)}
                        </a>`
                    ).join('');
                }
            } catch (err) {
                console.error('Failed to load active count:', err);
                document.getElementById('activeCount').textContent = '?';
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
