<?php
/**
 * Rockband Scheduler - Public Event Signup Page
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

require_once 'config.php';
require_once 'db.php';
require_once 'includes/helpers.php';

$db = $GLOBALS['db'];
$eventId = $_GET['eventid'] ?? null;
$error = null;
$event = null;

// Get signup settings
$signupSettings = $GLOBALS['config']['signup'] ?? [];
$requireName = $signupSettings['require_name'] ?? true;
$requireSong = $signupSettings['require_song'] ?? true;

// Get default theme ID from config
$defaultThemeId = $GLOBALS['config']['theme']['default_theme_id'] ?? null;

// Validate event ID and check if event is active
if (!$eventId) {
    $error = 'No event specified. Please scan a valid QR code.';
} elseif (!isValidUuid($eventId)) {
    $error = 'Invalid event link. Please scan a valid QR code.';
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
            $error = 'Event not found. Please scan a valid QR code.';
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

            // No time check - if someone has the QR code/URL, they can access the event
        }
    } catch (PDOException $e) {
        error_log('Event lookup error: ' . $e->getMessage());
        $error = 'Unable to load event. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?= $event ? h($event['name']) : 'Rockband Scheduler' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= h($event['primary_color'] ?? '#6f42c1') ?>;
            --bg-gradient-start: <?= h($event['bg_gradient_start'] ?? '#1a1a2e') ?>;
            --bg-gradient-end: <?= h($event['bg_gradient_end'] ?? '#16213e') ?>;
            --text-color: <?= h($event['text_color'] ?? '#ffffff') ?>;
        }

        body {
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            color: var(--text-color);
        }

        .header {
            background: rgba(0,0,0,0.3);
            padding: 1rem;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .header-inner {
            position: relative;
        }

        .header h1 {
            font-size: 1.25rem;
            margin: 0;
            font-weight: 600;
        }

        .header .location {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .header .deezer-credit {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            height: 70%;
            max-height: 48px;
        }

        .signage-link {
            display: block;
            text-align: center;
            padding: 1rem;
            color: var(--text-color);
            opacity: 0.5;
            font-size: 0.85rem;
            text-decoration: none;
            cursor: pointer;
        }

        .signage-link:hover {
            opacity: 0.7;
            color: var(--text-color);
        }

        .slot-list {
            padding: 1rem;
            padding-bottom: 100px; /* Space for bottom nav */
        }

        .slot-card {
            background: color-mix(in srgb, var(--text-color) 10%, transparent);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .slot-card.available {
            border: 2px dashed color-mix(in srgb, var(--text-color) 40%, transparent);
            cursor: pointer;
        }

        .slot-card.available:hover,
        .slot-card.available:active {
            background: color-mix(in srgb, var(--text-color) 15%, transparent);
            border-color: var(--primary-color);
        }

        .slot-card.taken {
            background: color-mix(in srgb, var(--text-color) 5%, transparent);
        }

        .slot-card.finished {
            opacity: 0.4;
        }

        .slot-card.finished .performer {
            text-decoration: line-through;
        }

        .slot-number {
            width: 44px;
            height: 44px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .slot-card.taken .slot-number {
            background: color-mix(in srgb, var(--text-color) 20%, transparent);
        }

        .slot-info {
            flex: 1;
            min-width: 0;
        }

        .slot-info .performer {
            font-weight: 600;
            font-size: 1rem;
        }

        .slot-info .song {
            font-size: 0.85rem;
            opacity: 0.8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .slot-info .empty-text {
            opacity: 0.6;
            font-style: italic;
        }

        .album-art {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .preview-btn {
            font-size: 1.5rem;
            cursor: pointer;
            flex-shrink: 0;
            color: color-mix(in srgb, var(--text-color) 70%, transparent);
        }

        .preview-btn:hover {
            color: var(--text-color);
        }

        .preview-btn.playing {
            color: #198754;
        }

        .preview-btn.error {
            color: #dc3545;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .preview-btn.loading {
            color: color-mix(in srgb, var(--text-color) 50%, transparent);
            animation: spin 1s linear infinite;
            /* Override general .loading class */
            display: inline;
            flex-direction: unset;
            padding: 0;
        }

        /* Sign up modal - full screen on mobile */
        .signup-modal .modal-dialog {
            margin: 0;
            max-width: 100%;
            min-height: 100vh;
        }

        .signup-modal .modal-content {
            min-height: 100vh;
            border-radius: 0;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            color: var(--text-color);
            border: none;
        }

        .signup-modal .modal-header {
            border-bottom: 1px solid color-mix(in srgb, var(--text-color) 10%, transparent);
        }

        .signup-modal .btn-close {
            /* Remove Bootstrap's white filter and use theme text color */
            filter: none;
            opacity: 0.7;
            color: var(--text-color);
            background: transparent;
            font-size: 1.5rem;
        }

        .signup-modal .btn-close::before {
            content: "\00d7"; /* × symbol */
            display: block;
        }

        .signup-modal .btn-close:hover {
            opacity: 1;
        }

        .signup-modal .modal-body {
            padding: 1.5rem;
        }

        .signup-modal .form-control {
            background: color-mix(in srgb, var(--text-color) 10%, transparent);
            border: 1px solid color-mix(in srgb, var(--text-color) 30%, transparent);
            color: var(--text-color);
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
        }

        .signup-modal .form-control:focus {
            background: color-mix(in srgb, var(--text-color) 15%, transparent);
            border-color: var(--primary-color);
            color: var(--text-color);
            box-shadow: 0 0 0 0.25rem color-mix(in srgb, var(--primary-color) 25%, transparent);
        }

        .signup-modal .form-control::placeholder {
            color: color-mix(in srgb, var(--text-color) 50%, transparent);
        }

        .song-search-results {
            max-height: 50vh;
            overflow-y: auto;
        }

        .song-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: color-mix(in srgb, var(--text-color) 5%, transparent);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .song-item:hover,
        .song-item:active {
            background: color-mix(in srgb, var(--text-color) 10%, transparent);
        }

        .song-item.selected {
            background: var(--primary-color);
        }

        .song-item .song-art {
            width: 48px;
            height: 48px;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .song-item .song-details {
            flex: 1;
            min-width: 0;
        }

        .song-item .song-title {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .song-item .song-artist {
            font-size: 0.85rem;
            opacity: 0.8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-signup {
            background: var(--primary-color);
            border: none;
            color: #fff;
            font-size: 1.1rem;
            padding: 0.875rem 2rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .btn-signup:hover {
            background: var(--primary-color);
            filter: brightness(0.85);
            color: #fff;
        }

        .btn-signup:disabled {
            background: color-mix(in srgb, var(--text-color) 20%, transparent);
            color: color-mix(in srgb, var(--text-color) 50%, transparent);
        }

        .error-page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            text-align: center;
        }

        .error-page i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .error-page h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .error-page p {
            opacity: 0.8;
        }

        /* Loading state */
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            opacity: 0.7;
        }
    </style>
</head>
<body>
<?php if ($error): ?>
    <div class="error-page">
        <i class="bi bi-exclamation-circle"></i>
        <h1>Oops!</h1>
        <p><?= h($error) ?></p>
    </div>
<?php else: ?>
<?php
// Determine if text color is light or dark to choose appropriate Deezer logo
$textColor = $event['text_color'] ?? '#ffffff';
$isLightText = true; // default to white logo
if (preg_match('/^#([0-9a-fA-F]{6})$/', $textColor, $matches)) {
    $r = hexdec(substr($matches[1], 0, 2));
    $g = hexdec(substr($matches[1], 2, 2));
    $b = hexdec(substr($matches[1], 4, 2));
    // Calculate relative luminance (perceived brightness)
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    $isLightText = $luminance > 0.5;
}
$deezerLogo = $isLightText ? 'images/Vertical-mw-rgb.svg' : 'images/Vertical-mb-rgb.svg';
?>
    <div class="header">
        <div class="header-inner">
            <h1><?= h($event['name']) ?></h1>
            <?php if ($event['location']): ?>
                <div class="location"><i class="bi bi-geo-alt"></i> <?= h($event['location']) ?></div>
            <?php endif; ?>
            <a href="https://www.deezer.com" target="_blank" rel="noopener" title="Powered by Deezer">
                <img src="<?= h($deezerLogo) ?>" alt="Deezer" class="deezer-credit">
            </a>
        </div>
    </div>

    <div class="slot-list" id="slotList">
        <div class="loading">
            <div class="spinner-border mb-2"></div>
            <div>Loading lineup...</div>
        </div>
    </div>

    <a href="#" class="signage-link" onclick="openSignageDisplay(); return false;">
        Open signup display
    </a>

    <!-- Sign Up Modal -->
    <div class="modal fade signup-modal" id="signupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sign Up for Slot <span id="signupSlotNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label">Your Name</label>
                        <input type="text" class="form-control" id="performerName" placeholder="Enter your name" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Choose Your Song</label>
                        <input type="text" class="form-control mb-3" id="songSearch" placeholder="Search songs...">
                        <div class="song-search-results" id="songResults">
                            <div class="loading">
                                <div class="spinner-border spinner-border-sm"></div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="selectedSongId">
                    <input type="hidden" id="selectedSlotPosition">
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-signup" id="confirmSignup" disabled>
                        <i class="bi bi-check-lg"></i> Sign Up
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const EVENT_ID = <?= json_encode($eventId) ?>;
        const API_BASE = 'api';
        const NUM_SLOTS = <?= (int)$event['num_entries'] ?>;
        const REQUIRE_NAME = <?= json_encode($requireName) ?>;
        const REQUIRE_SONG = <?= json_encode($requireSong) ?>;

        let entries = [];
        let songs = [];
        let currentAudio = null;
        let signupModal;
        let isModalOpen = false;
        let pollInterval = null;
        const POLL_INTERVAL_MS = 5000; // Poll every 5 seconds

        document.addEventListener('DOMContentLoaded', function() {
            signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
            loadData();

            document.getElementById('songSearch').addEventListener('input', filterSongs);
            document.getElementById('performerName').addEventListener('input', validateForm);
            document.getElementById('confirmSignup').addEventListener('click', submitSignup);

            // Track modal open/close state
            document.getElementById('signupModal').addEventListener('shown.bs.modal', () => isModalOpen = true);
            document.getElementById('signupModal').addEventListener('hidden.bs.modal', () => isModalOpen = false);

            // Start background polling
            startPolling();
        });

        function startPolling() {
            pollInterval = setInterval(pollForUpdates, POLL_INTERVAL_MS);
        }

        async function pollForUpdates() {
            // Don't update if modal is open (user is filling form)
            if (isModalOpen) return;

            try {
                const response = await fetch(`${API_BASE}/entries.php?event_id=${EVENT_ID}`);
                const data = await response.json();

                if (data.error) return; // Silently fail on poll errors

                // Check if data actually changed before re-rendering
                const newEntriesJson = JSON.stringify(data.entries || []);
                const oldEntriesJson = JSON.stringify(entries);

                if (newEntriesJson !== oldEntriesJson) {
                    // Save scroll position
                    const scrollPos = window.scrollY;

                    entries = data.entries || [];
                    // Don't update songs - they rarely change and we don't want to disrupt anything
                    renderSlots();

                    // Restore scroll position
                    window.scrollTo(0, scrollPos);
                }
            } catch (err) {
                // Silently fail on poll errors - don't disrupt user experience
                console.debug('Poll failed:', err);
            }
        }

        async function loadData() {
            try {
                const response = await fetch(`${API_BASE}/entries.php?event_id=${EVENT_ID}`);
                const data = await response.json();

                if (data.error) throw new Error(data.error);

                entries = data.entries || [];
                songs = data.songs || [];

                renderSlots();
            } catch (err) {
                console.error('Failed to load data:', err);
                document.getElementById('slotList').innerHTML =
                    '<div class="text-center text-danger p-4">Failed to load. Please refresh.</div>';
            }
        }

        function renderSlots() {
            const container = document.getElementById('slotList');
            const entryMap = {};
            entries.forEach(e => entryMap[e.position] = e);

            let html = '';
            for (let pos = 1; pos <= NUM_SLOTS; pos++) {
                const entry = entryMap[pos];
                // Slot is taken if it has a performer name OR a song
                const isTaken = entry && (entry.performer_name || entry.song_id);

                if (isTaken) {
                    const isFinished = entry.finished || false;
                    html += `
                        <div class="slot-card taken ${isFinished ? 'finished' : ''}">
                            <div class="slot-number">${pos}</div>
                            ${entry.album_art
                                ? `<img src="data:image/jpeg;base64,${entry.album_art}" class="album-art">`
                                : ''}
                            <div class="slot-info">
                                <div class="performer">${entry.performer_name ? escapeHtml(entry.performer_name) : '<em style="opacity:0.6">No name</em>'}</div>
                                <div class="song">${entry.title ? escapeHtml(entry.title) + ' - ' + escapeHtml(entry.artist) : 'No song selected'}</div>
                            </div>
                            ${entry.deezer_id ? `
                                <i class="bi bi-play-circle preview-btn"
                                   data-deezer-id="${entry.deezer_id}"
                                   onclick="togglePreview(this)"></i>
                            ` : ''}
                        </div>
                    `;
                } else {
                    html += `
                        <div class="slot-card available" onclick="openSignup(${pos})">
                            <div class="slot-number">${pos}</div>
                            <div class="slot-info">
                                <div class="empty-text">Tap to sign up</div>
                            </div>
                            <i class="bi bi-plus-circle" style="font-size: 1.5rem; opacity: 0.5;"></i>
                        </div>
                    `;
                }
            }

            container.innerHTML = html;
        }

        function openSignup(position) {
            document.getElementById('signupSlotNumber').textContent = position;
            document.getElementById('selectedSlotPosition').value = position;
            document.getElementById('performerName').value = '';
            document.getElementById('songSearch').value = '';
            document.getElementById('selectedSongId').value = '';

            // Reset signup button state
            const btn = document.getElementById('confirmSignup');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Sign Up';

            renderSongList(songs);
            signupModal.show();

            // Focus name input after modal opens
            document.getElementById('signupModal').addEventListener('shown.bs.modal', function handler() {
                document.getElementById('performerName').focus();
                this.removeEventListener('shown.bs.modal', handler);
            });
        }

        const MAX_DISPLAY_SONGS = 50; // Limit initial display for performance

        function renderSongList(songsToShow, isFiltered = false) {
            const container = document.getElementById('songResults');

            if (songsToShow.length === 0) {
                container.innerHTML = isFiltered
                    ? '<div class="text-center text-muted py-3">No matching songs</div>'
                    : '<div class="text-center text-muted py-3">No songs available</div>';
                return;
            }

            // If showing full list and it's large, prompt user to search
            if (!isFiltered && songsToShow.length > MAX_DISPLAY_SONGS) {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-search d-block mb-2" style="font-size: 2rem; opacity: 0.5;"></i>
                        <div>${songsToShow.length} songs available</div>
                        <div class="small">Type to search for your song</div>
                    </div>
                `;
                return;
            }

            const selectedId = document.getElementById('selectedSongId').value;
            const displaySongs = songsToShow.slice(0, MAX_DISPLAY_SONGS);
            const hasMore = songsToShow.length > MAX_DISPLAY_SONGS;

            let html = displaySongs.map(song => `
                <div class="song-item ${song.song_id == selectedId ? 'selected' : ''}"
                     onclick="selectSong(${song.song_id})"
                     data-song-id="${song.song_id}">
                    ${song.album_art
                        ? `<img src="data:image/jpeg;base64,${song.album_art}" class="song-art">`
                        : '<div class="song-art bg-secondary"></div>'}
                    <div class="song-details">
                        <div class="song-title">${escapeHtml(song.title)}</div>
                        <div class="song-artist">${escapeHtml(song.artist)}</div>
                    </div>
                    ${song.deezer_id ? `
                        <i class="bi bi-play-circle preview-btn"
                           onclick="event.stopPropagation(); togglePreview(this)"
                           data-deezer-id="${song.deezer_id}"></i>
                    ` : ''}
                </div>
            `).join('');

            if (hasMore) {
                html += `<div class="text-center text-muted py-2 small">Showing ${MAX_DISPLAY_SONGS} of ${songsToShow.length} matches. Refine your search.</div>`;
            }

            container.innerHTML = html;
        }

        function filterSongs() {
            const query = document.getElementById('songSearch').value.toLowerCase();
            if (!query) {
                renderSongList(songs, false);
                return;
            }
            const filtered = songs.filter(s =>
                s.title.toLowerCase().includes(query) ||
                s.artist.toLowerCase().includes(query)
            );
            renderSongList(filtered, true);
        }

        function selectSong(songId) {
            document.getElementById('selectedSongId').value = songId;

            // Update visual selection
            document.querySelectorAll('.song-item').forEach(el => {
                el.classList.toggle('selected', el.dataset.songId == songId);
            });

            validateForm();
        }

        function validateForm() {
            const name = document.getElementById('performerName').value.trim();
            const songId = document.getElementById('selectedSongId').value;

            // Check requirements based on settings
            const nameOk = !REQUIRE_NAME || name;
            const songOk = !REQUIRE_SONG || songId;
            // At least one must be provided
            const hasData = name || songId;

            document.getElementById('confirmSignup').disabled = !nameOk || !songOk || !hasData;
        }

        async function submitSignup() {
            const position = document.getElementById('selectedSlotPosition').value;
            const name = document.getElementById('performerName').value.trim();
            const songId = document.getElementById('selectedSongId').value;

            // Check requirements based on settings
            const nameOk = !REQUIRE_NAME || name;
            const songOk = !REQUIRE_SONG || songId;
            const hasData = name || songId;
            if (!nameOk || !songOk || !hasData) return;

            const btn = document.getElementById('confirmSignup');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Signing up...';

            try {
                const response = await fetch(`${API_BASE}/entries.php?event_id=${EVENT_ID}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        position: parseInt(position),
                        performer_name: name,
                        song_id: songId ? parseInt(songId) : null
                    })
                });

                const result = await response.json();

                if (result.error) {
                    throw new Error(result.error);
                }

                signupModal.hide();
                loadData(); // Refresh the list
            } catch (err) {
                const errorMsg = err.message || 'Failed to sign up. Please try again.';

                // If slot was taken, refresh data and close modal with helpful message
                if (errorMsg.toLowerCase().includes('already taken')) {
                    alert('This slot was just taken by someone else. Please choose another slot.');
                    signupModal.hide();
                    loadData();
                } else {
                    alert(errorMsg);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg"></i> Sign Up';
                }
            }
        }

        let currentDeezerId = null;

        async function togglePreview(el) {
            const deezerId = el.dataset.deezerId;

            // Stop any playing audio
            if (currentAudio) {
                currentAudio.pause();
                document.querySelectorAll('.preview-btn').forEach(btn => {
                    btn.classList.remove('playing', 'loading', 'error');
                    btn.classList.replace('bi-stop-circle', 'bi-play-circle');
                    btn.classList.replace('bi-arrow-repeat', 'bi-play-circle');
                    btn.classList.replace('bi-exclamation-triangle', 'bi-play-circle');
                });

                if (currentDeezerId === deezerId) {
                    currentAudio = null;
                    currentDeezerId = null;
                    return;
                }
            }

            // Show loading state
            el.classList.add('loading');
            el.classList.replace('bi-play-circle', 'bi-arrow-repeat');

            try {
                // Fetch fresh preview URL from Deezer
                const response = await fetch(`${API_BASE}/deezer.php?track_id=${deezerId}`);
                const data = await response.json();

                if (data.error || !data.preview) {
                    throw new Error(data.error || 'No preview available');
                }

                // Play the fresh URL
                currentAudio = new Audio(data.preview);
                currentDeezerId = deezerId;
                el.classList.remove('loading');
                el.classList.add('playing');
                el.classList.replace('bi-arrow-repeat', 'bi-stop-circle');

                await currentAudio.play();

                currentAudio.addEventListener('ended', () => {
                    el.classList.remove('playing');
                    el.classList.replace('bi-stop-circle', 'bi-play-circle');
                    currentAudio = null;
                    currentDeezerId = null;
                });

                currentAudio.addEventListener('error', () => {
                    showPreviewError(el);
                });

            } catch (err) {
                console.error('Preview failed:', err);
                showPreviewError(el);
            }
        }

        function showPreviewError(el) {
            el.classList.remove('playing', 'loading');
            el.classList.add('error');
            el.classList.replace('bi-arrow-repeat', 'bi-exclamation-triangle');
            el.classList.replace('bi-stop-circle', 'bi-exclamation-triangle');
            currentAudio = null;
            currentDeezerId = null;
            setTimeout(() => {
                el.classList.remove('error');
                el.classList.replace('bi-exclamation-triangle', 'bi-play-circle');
            }, 2000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function openSignageDisplay() {
            if (confirm('Open the signup display? This shows a QR code for TVs and large screens.')) {
                window.location.href = `signup-display.php?eventid=${EVENT_ID}`;
            }
        }
    </script>
<?php endif; ?>
</body>
</html>
