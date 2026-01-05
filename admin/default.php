<?php
/**
 * Rockband Scheduler - Admin Dashboard
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

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

startAdminSession();
$csrfToken = getCsrfToken();

$db = $GLOBALS['db'];

// Get quick stats
$songCount = 0;
$songStats = [];

if ($db) {
    try {
        $songCount = $db->query('SELECT COUNT(*) FROM songs')->fetchColumn();

        // Get song statistics
        // Shortest song (with duration > 0)
        $stmt = $db->query('SELECT title, artist, duration FROM songs WHERE duration > 0 ORDER BY duration ASC LIMIT 1');
        $songStats['shortest'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Longest song
        $stmt = $db->query('SELECT title, artist, duration FROM songs ORDER BY duration DESC LIMIT 1');
        $songStats['longest'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Top 5 most selected
        $stmt = $db->query('SELECT title, artist, selection_count FROM songs WHERE selection_count > 0 ORDER BY selection_count DESC LIMIT 5');
        $songStats['most_selected'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top 5 least selected (that have been selected at least once)
        $stmt = $db->query('SELECT title, artist, selection_count FROM songs WHERE selection_count > 0 ORDER BY selection_count ASC LIMIT 5');
        $songStats['least_selected'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Most recently selected
        $stmt = $db->query('SELECT title, artist, last_selected FROM songs WHERE last_selected IS NOT NULL ORDER BY last_selected DESC LIMIT 1');
        $songStats['most_recent'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Oldest selected (longest time since selection)
        $stmt = $db->query('SELECT title, artist, last_selected FROM songs WHERE last_selected IS NOT NULL ORDER BY last_selected ASC LIMIT 1');
        $songStats['oldest_selected'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Never selected count
        $songStats['never_selected_count'] = $db->query('SELECT COUNT(*) FROM songs WHERE selection_count = 0')->fetchColumn();

    } catch (PDOException $e) {
        error_log('Dashboard stats error: ' . $e->getMessage());
    }
}

// Helper to format duration
function formatDuration($seconds) {
    if (!$seconds) return '-';
    $mins = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf('%d:%02d', $mins, $secs);
}

// Helper to format relative time
function timeAgo($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $time);
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
    <script src="admin-theme.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <a class="navbar-brand" href="default.php">Rockband Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="default.php" title="Dashboard"><i class="bi bi-house-door"></i><span class="d-lg-none ms-2">Dashboard</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="songs.php" title="Songs"><i class="bi bi-music-note-list"></i><span class="d-lg-none ms-2">Songs</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php" title="Events"><i class="bi bi-calendar-event"></i><span class="d-lg-none ms-2">Events</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php" title="Settings"><i class="bi bi-gear"></i><span class="d-lg-none ms-2">Settings</span></a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="help.php" title="Help"><i class="bi bi-question-circle"></i><span class="d-lg-none ms-2">Help</span></a>
                    </li>
                    <li class="nav-item">
                        <button class="btn nav-link" onclick="toggleAdminTheme()" title="Toggle theme"><i id="themeToggleIcon" class="bi bi-moon-fill"></i><span class="d-lg-none ms-2">Theme</span></button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Dashboard</h1>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <div class="col">
                <div class="card text-bg-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-music-note-list"></i> Song Library</h5>
                        <p class="card-text display-4"><?= $songCount ?></p>
                        <a href="songs.php" class="btn btn-light">Manage Songs</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card text-bg-info">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-calendar-event"></i> Upcoming Events</h5>
                        <p class="card-text display-4" id="upcomingCount">-</p>
                        <a href="events.php" class="btn btn-light">Manage Events</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card text-bg-success">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-broadcast"></i> Active Now</h5>
                        <p class="card-text display-4" id="activeCount">-</p>
                        <div id="activeEventsList"><span class="btn btn-light invisible">Placeholder</span></div>
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

        <!-- Song Statistics -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Song Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Duration Stats -->
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-muted mb-3"><i class="bi bi-clock"></i> Duration</h6>
                                    <?php if ($songStats['shortest']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Shortest:</small>
                                            <div class="fw-bold"><?= h($songStats['shortest']['title']) ?></div>
                                            <small class="text-muted"><?= h($songStats['shortest']['artist']) ?> &bull; <?= formatDuration($songStats['shortest']['duration']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($songStats['longest']): ?>
                                        <div>
                                            <small class="text-muted">Longest:</small>
                                            <div class="fw-bold"><?= h($songStats['longest']['title']) ?></div>
                                            <small class="text-muted"><?= h($songStats['longest']['artist']) ?> &bull; <?= formatDuration($songStats['longest']['duration']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!$songStats['shortest'] && !$songStats['longest']): ?>
                                        <p class="text-muted mb-0">No song data</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Most Selected -->
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-muted mb-3"><i class="bi bi-fire"></i> Most Popular</h6>
                                    <?php if (!empty($songStats['most_selected'])): ?>
                                        <?php foreach ($songStats['most_selected'] as $i => $song): ?>
                                            <div class="<?= $i < count($songStats['most_selected']) - 1 ? 'mb-2' : '' ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="text-truncate me-2">
                                                        <span class="fw-bold"><?= h($song['title']) ?></span>
                                                        <br><small class="text-muted"><?= h($song['artist']) ?></small>
                                                    </div>
                                                    <span class="badge bg-primary"><?= $song['selection_count'] ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No selections yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Least Selected -->
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-muted mb-3"><i class="bi bi-snow"></i> Least Popular</h6>
                                    <?php if (!empty($songStats['least_selected'])): ?>
                                        <?php foreach ($songStats['least_selected'] as $i => $song): ?>
                                            <div class="<?= $i < count($songStats['least_selected']) - 1 ? 'mb-2' : '' ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="text-truncate me-2">
                                                        <span class="fw-bold"><?= h($song['title']) ?></span>
                                                        <br><small class="text-muted"><?= h($song['artist']) ?></small>
                                                    </div>
                                                    <span class="badge bg-secondary"><?= $song['selection_count'] ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No selections yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-muted mb-3"><i class="bi bi-calendar-check"></i> Selection Activity</h6>
                                    <?php if ($songStats['most_recent']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Most recent:</small>
                                            <div class="fw-bold"><?= h($songStats['most_recent']['title']) ?></div>
                                            <small class="text-muted"><?= h($songStats['most_recent']['artist']) ?> &bull; <?= timeAgo($songStats['most_recent']['last_selected']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($songStats['oldest_selected']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Longest ago:</small>
                                            <div class="fw-bold"><?= h($songStats['oldest_selected']['title']) ?></div>
                                            <small class="text-muted"><?= h($songStats['oldest_selected']['artist']) ?> &bull; <?= timeAgo($songStats['oldest_selected']['last_selected']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($songStats['never_selected_count'] > 0): ?>
                                        <div>
                                            <small class="text-muted">Never selected:</small>
                                            <div><span class="badge bg-warning text-dark"><?= $songStats['never_selected_count'] ?> songs</span></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!$songStats['most_recent'] && !$songStats['oldest_selected'] && $songStats['never_selected_count'] == 0): ?>
                                        <p class="text-muted mb-0">No selection data</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
        const API_BASE = '../api';

        document.addEventListener('DOMContentLoaded', loadActiveCount);

        async function loadActiveCount() {
            try {
                const response = await fetch(`${API_BASE}/events.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: CSRF_TOKEN, action: 'list' })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                const now = new Date();
                const allEvents = data.events || [];

                // Upcoming: events that haven't ended yet (includes active)
                const upcomingEvents = allEvents.filter(event => {
                    const end = new Date(event.end_time);
                    return end > now;
                });

                // Active: currently running events
                const activeEvents = allEvents.filter(event => {
                    const start = new Date(event.start_time);
                    const end = new Date(event.end_time);
                    return now >= start && now <= end;
                });

                document.getElementById('upcomingCount').textContent = upcomingEvents.length;
                document.getElementById('activeCount').textContent = activeEvents.length;

                // Show links to active events (or invisible placeholder for equal card height)
                const listEl = document.getElementById('activeEventsList');
                if (activeEvents.length > 0) {
                    listEl.innerHTML = activeEvents.map(event =>
                        `<a href="entries.php?eventid=${event.event_id}" class="btn btn-outline-light btn-sm me-2 mb-2">
                            <i class="bi bi-list-ol"></i> ${escapeHtml(event.name)}
                        </a>`
                    ).join('');
                } else {
                    listEl.innerHTML = '<span class="btn btn-light invisible">Placeholder</span>';
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
