<?php
/**
 * Event Signage Display Page
 *
 * Optimized for TV/large screen display showing:
 * - Current performer prominently
 * - Upcoming performers in queue
 * - Auto-refreshes for real-time updates
 */
require_once 'config.php';
require_once 'db.php';
require_once 'includes/helpers.php';

$db = $GLOBALS['db'];
$eventId = $_GET['eventid'] ?? null;
$error = null;
$event = null;

// Get default theme ID from config
$defaultThemeId = $GLOBALS['config']['theme']['default_theme_id'] ?? null;

// Validate event ID
if (!$eventId) {
    $error = 'No event specified.';
} elseif (!isValidUuid($eventId)) {
    $error = 'Invalid event ID.';
} else {
    try {
        $stmt = $db->prepare('
            SELECT BIN_TO_UUID(e.event_id) as event_id, e.name, e.location, e.start_time, e.end_time, e.num_entries,
                   e.theme_id,
                   t.primary_color, t.bg_gradient_start, t.bg_gradient_end, t.text_color
            FROM events e
            LEFT JOIN themes t ON e.theme_id = t.theme_id
            WHERE e.event_id = UUID_TO_BIN(?)
        ');
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            $error = 'Event not found.';
        } else {
            // If event has no theme, use the default theme from settings
            if (!$event['theme_id'] && $defaultThemeId) {
                $themeStmt = $db->prepare('
                    SELECT primary_color, bg_gradient_start, bg_gradient_end, text_color
                    FROM themes WHERE theme_id = ?
                ');
                $themeStmt->execute([$defaultThemeId]);
                $defaultTheme = $themeStmt->fetch(PDO::FETCH_ASSOC);
                if ($defaultTheme) {
                    $event['primary_color'] = $defaultTheme['primary_color'];
                    $event['bg_gradient_start'] = $defaultTheme['bg_gradient_start'];
                    $event['bg_gradient_end'] = $defaultTheme['bg_gradient_end'];
                    $event['text_color'] = $defaultTheme['text_color'];
                }
            }
        }
    } catch (PDOException $e) {
        error_log('Event lookup error: ' . $e->getMessage());
        $error = 'Unable to load event.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $event ? h($event['name']) . ' - Signage' : 'Event Signage' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= h($event['primary_color'] ?? '#6f42c1') ?>;
            --bg-gradient-start: <?= h($event['bg_gradient_start'] ?? '#1a1a2e') ?>;
            --bg-gradient-end: <?= h($event['bg_gradient_end'] ?? '#16213e') ?>;
            --text-color: <?= h($event['text_color'] ?? '#ffffff') ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow: hidden;
        }

        .signage-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            padding: 2vw;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1.5vw;
            border-bottom: 2px solid color-mix(in srgb, var(--text-color) 20%, transparent);
        }

        .event-name {
            font-size: 3vw;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .event-location {
            font-size: 1.5vw;
            opacity: 0.7;
        }

        .clock {
            font-size: 2.5vw;
            font-weight: 300;
            opacity: 0.9;
        }

        /* Main content area */
        .main-content {
            flex: 1;
            display: flex;
            gap: 2vw;
            padding-top: 2vw;
            min-height: 0;
        }

        /* Current performer - left side */
        .current-section {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .section-label {
            font-size: 1.5vw;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            opacity: 0.6;
            margin-bottom: 1vw;
        }

        .current-performer {
            flex: 1;
            background: color-mix(in srgb, var(--text-color) 8%, transparent);
            border-radius: 1.5vw;
            padding: 2vw;
            display: flex;
            align-items: center;
            gap: 2vw;
            border: 3px solid var(--primary-color);
            box-shadow: 0 0 40px color-mix(in srgb, var(--primary-color) 30%, transparent);
        }

        .current-album-art {
            width: 20vw;
            height: 20vw;
            border-radius: 1vw;
            object-fit: cover;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--text-color) 10%, transparent);
        }

        .current-album-placeholder {
            width: 20vw;
            height: 20vw;
            border-radius: 1vw;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--text-color) 10%, transparent);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .current-album-placeholder i {
            font-size: 8vw;
            opacity: 0.3;
        }

        .current-info {
            flex: 1;
            min-width: 0;
        }

        .current-slot-number {
            font-size: 1.5vw;
            background: var(--primary-color);
            display: inline-block;
            padding: 0.3vw 1vw;
            border-radius: 0.5vw;
            margin-bottom: 1vw;
        }

        .current-performer-name {
            font-size: 4.5vw;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 0.5vw;
        }

        .current-song-title {
            font-size: 2.5vw;
            opacity: 0.9;
            margin-bottom: 0.3vw;
        }

        .current-artist {
            font-size: 1.8vw;
            opacity: 0.7;
        }

        .no-current {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            opacity: 0.5;
        }

        .no-current i {
            font-size: 6vw;
            margin-bottom: 1vw;
        }

        .no-current-text {
            font-size: 2vw;
        }

        /* Up next queue - right side */
        .queue-section {
            width: 35%;
            display: flex;
            flex-direction: column;
        }

        .queue-list {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 1vw;
        }

        .queue-item {
            background: color-mix(in srgb, var(--text-color) 5%, transparent);
            border-radius: 1vw;
            padding: 1.2vw 1.5vw;
            display: flex;
            align-items: center;
            gap: 1.2vw;
            transition: all 0.3s ease;
        }

        .queue-item.next {
            background: color-mix(in srgb, var(--primary-color) 30%, transparent);
            border: 2px solid color-mix(in srgb, var(--primary-color) 50%, transparent);
        }

        .queue-slot-number {
            width: 3vw;
            height: 3vw;
            background: color-mix(in srgb, var(--text-color) 15%, transparent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3vw;
            font-weight: 600;
            flex-shrink: 0;
        }

        .queue-item.next .queue-slot-number {
            background: var(--primary-color);
        }

        .queue-album-art {
            width: 4vw;
            height: 4vw;
            border-radius: 0.5vw;
            object-fit: cover;
            flex-shrink: 0;
        }

        .queue-album-placeholder {
            width: 4vw;
            height: 4vw;
            border-radius: 0.5vw;
            background: color-mix(in srgb, var(--text-color) 10%, transparent);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .queue-album-placeholder i {
            font-size: 1.5vw;
            opacity: 0.3;
        }

        .queue-info {
            flex: 1;
            min-width: 0;
        }

        .queue-performer {
            font-size: 1.4vw;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .queue-song {
            font-size: 1.1vw;
            opacity: 0.7;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .queue-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            opacity: 0.4;
            font-size: 1.3vw;
        }

        /* Error state */
        .error-page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }

        .error-page i {
            font-size: 8vw;
            margin-bottom: 2vw;
            opacity: 0.5;
        }

        .error-page h1 {
            font-size: 3vw;
        }

        /* Loading state */
        .loading-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            opacity: 0.6;
        }

        .loading-state .spinner {
            width: 4vw;
            height: 4vw;
            border: 0.3vw solid color-mix(in srgb, var(--text-color) 20%, transparent);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1vw;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* All done state */
        .all-done {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .all-done i {
            font-size: 10vw;
            margin-bottom: 2vw;
            opacity: 0.6;
        }

        .all-done h2 {
            font-size: 4vw;
            margin-bottom: 1vw;
        }

        .all-done p {
            font-size: 2vw;
            opacity: 0.7;
        }

        /* Transitions for smooth updates */
        .fade-update {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0.5; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
<?php if ($error): ?>
    <div class="error-page">
        <i class="bi bi-exclamation-circle"></i>
        <h1><?= h($error) ?></h1>
    </div>
<?php else: ?>
    <div class="signage-container">
        <div class="header">
            <div>
                <div class="event-name"><?= h($event['name']) ?></div>
                <?php if ($event['location']): ?>
                    <div class="event-location"><i class="bi bi-geo-alt"></i> <?= h($event['location']) ?></div>
                <?php endif; ?>
            </div>
            <div class="clock" id="clock"></div>
        </div>

        <div class="main-content" id="mainContent">
            <div class="loading-state">
                <div class="spinner"></div>
                <div style="font-size: 1.5vw;">Loading...</div>
            </div>
        </div>
    </div>

    <script>
        const EVENT_ID = <?= json_encode($eventId) ?>;
        const API_BASE = 'api';
        const NUM_SLOTS = <?= (int)$event['num_entries'] ?>;
        const MAX_QUEUE_DISPLAY = 6; // Number of upcoming performers to show
        const POLL_INTERVAL_MS = 3000; // Poll every 3 seconds for faster updates

        let entries = [];
        let lastEntriesJson = '';

        // Update clock
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('clock').textContent = time;
        }

        // Start clock
        updateClock();
        setInterval(updateClock, 1000);

        // Load entries
        async function loadEntries() {
            try {
                const response = await fetch(`${API_BASE}/entries.php?event_id=${EVENT_ID}`);
                const data = await response.json();

                if (data.error) throw new Error(data.error);

                const newEntriesJson = JSON.stringify(data.entries || []);

                // Only update if data changed
                if (newEntriesJson !== lastEntriesJson) {
                    lastEntriesJson = newEntriesJson;
                    entries = data.entries || [];
                    renderSignage();
                }
            } catch (err) {
                console.error('Failed to load entries:', err);
            }
        }

        function renderSignage() {
            const container = document.getElementById('mainContent');

            // Build entry map by position
            const entryMap = {};
            entries.forEach(e => {
                if (e.performer_name || e.song_id) {
                    entryMap[e.position] = e;
                }
            });

            // Find current (first unfinished) and upcoming
            let current = null;
            const upcoming = [];

            for (let pos = 1; pos <= NUM_SLOTS; pos++) {
                const entry = entryMap[pos];
                if (!entry) continue;

                if (entry.finished) continue;

                if (!current) {
                    current = { ...entry, position: pos };
                } else if (upcoming.length < MAX_QUEUE_DISPLAY) {
                    upcoming.push({ ...entry, position: pos });
                }
            }

            // Check if all done
            const hasUnfinished = Object.values(entryMap).some(e => !e.finished);
            const hasEntries = Object.keys(entryMap).length > 0;

            if (!hasUnfinished && hasEntries) {
                // All performers done
                container.innerHTML = `
                    <div class="all-done">
                        <i class="bi bi-music-note-beamed"></i>
                        <h2>That's a wrap!</h2>
                        <p>Thanks for an amazing show!</p>
                    </div>
                `;
                return;
            }

            // Build current performer HTML
            let currentHtml;
            if (current) {
                const albumArt = current.album_art
                    ? `<img src="data:image/jpeg;base64,${current.album_art}" class="current-album-art">`
                    : `<div class="current-album-placeholder"><i class="bi bi-music-note-beamed"></i></div>`;

                currentHtml = `
                    <div class="current-performer fade-update">
                        ${albumArt}
                        <div class="current-info">
                            <div class="current-slot-number">Slot ${current.position}</div>
                            <div class="current-performer-name">${escapeHtml(current.performer_name) || 'Unknown'}</div>
                            ${current.title ? `
                                <div class="current-song-title">${escapeHtml(current.title)}</div>
                                <div class="current-artist">${escapeHtml(current.artist)}</div>
                            ` : '<div class="current-song-title" style="opacity: 0.5;">No song selected</div>'}
                        </div>
                    </div>
                `;
            } else {
                currentHtml = `
                    <div class="current-performer">
                        <div class="no-current">
                            <i class="bi bi-hourglass"></i>
                            <div class="no-current-text">Waiting for performers...</div>
                        </div>
                    </div>
                `;
            }

            // Build queue HTML
            let queueHtml;
            if (upcoming.length > 0) {
                queueHtml = upcoming.map((entry, idx) => {
                    const albumArt = entry.album_art
                        ? `<img src="data:image/jpeg;base64,${entry.album_art}" class="queue-album-art">`
                        : `<div class="queue-album-placeholder"><i class="bi bi-music-note"></i></div>`;

                    return `
                        <div class="queue-item ${idx === 0 ? 'next' : ''}">
                            <div class="queue-slot-number">${entry.position}</div>
                            ${albumArt}
                            <div class="queue-info">
                                <div class="queue-performer">${escapeHtml(entry.performer_name) || 'Unknown'}</div>
                                <div class="queue-song">${entry.title ? escapeHtml(entry.title) + ' - ' + escapeHtml(entry.artist) : 'No song selected'}</div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                queueHtml = `<div class="queue-empty">No one else in queue</div>`;
            }

            container.innerHTML = `
                <div class="current-section">
                    <div class="section-label">Now Performing</div>
                    ${currentHtml}
                </div>
                <div class="queue-section">
                    <div class="section-label">Up Next</div>
                    <div class="queue-list">
                        ${queueHtml}
                    </div>
                </div>
            `;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initial load
        loadEntries();

        // Start polling
        setInterval(loadEntries, POLL_INTERVAL_MS);
    </script>
<?php endif; ?>
</body>
</html>
