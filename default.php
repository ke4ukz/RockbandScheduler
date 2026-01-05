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

        .slots-counter {
            text-align: center;
            padding: 1rem;
            font-size: 1rem;
            opacity: 0.8;
            background: rgba(0,0,0,0.1);
        }

        .slots-counter .count {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .signup-container {
            padding: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .step-indicator {
            text-align: center;
            font-size: 0.85rem;
            opacity: 0.6;
            margin-bottom: 0.5rem;
        }

        .step-title {
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .form-control {
            background: color-mix(in srgb, var(--text-color) 10%, transparent);
            border: 1px solid color-mix(in srgb, var(--text-color) 30%, transparent);
            color: var(--text-color);
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            background: color-mix(in srgb, var(--text-color) 15%, transparent);
            border-color: var(--primary-color);
            color: var(--text-color);
            box-shadow: 0 0 0 0.25rem color-mix(in srgb, var(--primary-color) 25%, transparent);
        }

        .form-control::placeholder {
            color: color-mix(in srgb, var(--text-color) 50%, transparent);
        }

        .song-list {
            overflow-y: auto;
            padding-bottom: 1rem;
        }

        .song-list-loading {
            text-align: center;
            padding: 1rem;
            opacity: 0.6;
        }

        .fixed-buttons {
            position: fixed;
            bottom: 1rem;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            padding: 0 1rem;
            pointer-events: none;
            z-index: 100;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
        }

        .fixed-buttons.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .fixed-buttons button {
            pointer-events: auto;
        }

        .btn-back-to-top {
            background: rgba(0,0,0,0.6);
            border: none;
            color: var(--text-color);
            font-size: 1rem;
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back-to-top:hover {
            background: rgba(0,0,0,0.8);
            color: var(--text-color);
        }

        .btn-fixed-next {
            background: var(--primary-color);
            border: none;
            color: #fff;
            font-size: 1rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .btn-fixed-next:hover {
            background: var(--primary-color);
            filter: brightness(0.85);
            color: #fff;
        }

        .btn-fixed-next:disabled {
            background: color-mix(in srgb, var(--text-color) 30%, transparent);
            color: color-mix(in srgb, var(--text-color) 50%, transparent);
            box-shadow: none;
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
        }

        .selected-song-display {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: color-mix(in srgb, var(--text-color) 10%, transparent);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .selected-song-display .song-art {
            width: 56px;
            height: 56px;
            border-radius: 8px;
            object-fit: cover;
        }

        .selected-song-display .song-details {
            flex: 1;
        }

        .selected-song-display .song-title {
            font-weight: 600;
        }

        .selected-song-display .song-artist {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .btn-primary-action {
            background: var(--primary-color);
            border: none;
            color: #fff;
            font-size: 1.1rem;
            padding: 0.875rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            width: 100%;
        }

        .btn-primary-action:hover {
            background: var(--primary-color);
            filter: brightness(0.85);
            color: #fff;
        }

        .btn-primary-action:disabled {
            background: color-mix(in srgb, var(--text-color) 20%, transparent);
            color: color-mix(in srgb, var(--text-color) 50%, transparent);
        }

        .btn-secondary-action {
            background: transparent;
            border: 2px solid color-mix(in srgb, var(--text-color) 30%, transparent);
            color: var(--text-color);
            font-size: 1rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .btn-secondary-action:hover {
            background: color-mix(in srgb, var(--text-color) 10%, transparent);
            border-color: color-mix(in srgb, var(--text-color) 50%, transparent);
            color: var(--text-color);
        }

        .button-row {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .button-row .btn-secondary-action {
            flex: 0 0 auto;
        }

        .button-row .btn-primary-action {
            flex: 1;
        }

        .success-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .success-state .success-icon {
            font-size: 4rem;
            color: #198754;
            margin-bottom: 1rem;
        }

        .success-state h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .success-state .slot-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .success-state .song-info {
            opacity: 0.8;
            margin-top: 0.5rem;
        }

        .full-message {
            text-align: center;
            padding: 3rem 1rem;
        }

        .full-message i {
            font-size: 3rem;
            opacity: 0.5;
            margin-bottom: 1rem;
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

        .copyright-link {
            display: block;
            text-align: center;
            padding: 1rem;
            color: var(--text-color);
            opacity: 0.4;
            font-size: 0.75rem;
            text-decoration: none;
        }

        .copyright-link:hover {
            opacity: 0.6;
            color: var(--text-color);
        }

        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            opacity: 0.7;
        }

        .signup-step {
            display: none;
        }

        .signup-step.active {
            display: block;
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
    <a href="copyright.php" class="copyright-link">&copy; 2026</a>
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

    <div class="slots-counter" id="slotsCounter">
        <span class="count" id="filledCount">-</span> of <span id="totalCount">-</span> spots filled
    </div>

    <div class="signup-container">
        <!-- Loading State -->
        <div id="loadingState" class="loading">
            <div class="spinner-border mb-2"></div>
            <div>Loading...</div>
        </div>

        <!-- Step 1: Song Selection -->
        <div id="step1" class="signup-step">
            <div class="step-indicator">Step 1 of 2</div>
            <div class="step-title">Choose Your Song</div>

            <div class="action-bar">
                <input type="text" class="form-control" id="songSearch" placeholder="Search songs...">
                <button class="btn-primary-action" id="nextBtn" disabled>
                    Next <i class="bi bi-arrow-right"></i>
                </button>
            </div>

            <div class="song-list" id="songResults"></div>
        </div>

        <!-- Step 2: Name Entry -->
        <div id="step2" class="signup-step">
            <div class="step-indicator">Step 2 of 2</div>
            <div class="step-title">Enter Your Name</div>

            <div class="selected-song-display" id="selectedSongDisplay">
                <div class="song-art bg-secondary" id="selectedArt"></div>
                <div class="song-details">
                    <div class="song-title" id="selectedTitle">-</div>
                    <div class="song-artist" id="selectedArtist">-</div>
                </div>
            </div>

            <input type="text" class="form-control" id="performerName" placeholder="Your name" autocomplete="off">

            <div class="button-row">
                <button class="btn-secondary-action" id="backBtn">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <button class="btn-primary-action" id="signupBtn" disabled>
                    <i class="bi bi-check-lg"></i> Sign Up
                </button>
            </div>
        </div>

        <!-- Success State -->
        <div id="successState" class="signup-step">
            <div class="success-state">
                <i class="bi bi-check-circle-fill success-icon"></i>
                <h2>You're signed up!</h2>
                <div>You're in spot</div>
                <div class="slot-number" id="assignedSlot">#-</div>
                <div class="song-info" id="successSongInfo">-</div>

                <button class="btn-primary-action mt-4" id="signupAgainBtn">
                    Sign Up Another Person
                </button>
            </div>
        </div>

        <!-- All Slots Full -->
        <div id="fullState" class="signup-step">
            <div class="full-message">
                <i class="bi bi-calendar-x"></i>
                <h2>All spots are filled!</h2>
                <p>Check back later - spots may open up.</p>
            </div>
        </div>
    </div>

    <a href="#" class="signage-link" onclick="openSignageDisplay(); return false;">
        Open signup display
    </a>

    <a href="copyright.php" class="copyright-link">&copy; 2026</a>

    <!-- Fixed scroll buttons (only visible on step 1 when scrolled) -->
    <div class="fixed-buttons" id="fixedButtons">
        <button class="btn-back-to-top" onclick="scrollToTop()">
            <i class="bi bi-arrow-up"></i> Top
        </button>
        <button class="btn-fixed-next" id="fixedNextBtn" disabled onclick="goToStep2()">
            Next <i class="bi bi-arrow-right"></i>
        </button>
    </div>

    <input type="hidden" id="selectedSongId">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const EVENT_ID = <?= json_encode($eventId) ?>;
        const API_BASE = 'api';
        let numSlots = <?= (int)$event['num_entries'] ?>;

        let entries = [];
        let songs = [];
        let currentAudio = null;
        let currentDeezerId = null;
        let pollInterval = null;
        const POLL_INTERVAL_MS = 5000;
        const SONGS_PER_BATCH = 20;
        let displayedSongCount = 0;
        let currentFilteredSongs = [];
        let isLoadingMore = false;

        document.addEventListener('DOMContentLoaded', function() {
            loadData();

            document.getElementById('songSearch').addEventListener('input', filterSongs);
            document.getElementById('nextBtn').addEventListener('click', goToStep2);
            document.getElementById('backBtn').addEventListener('click', goToStep1);
            document.getElementById('performerName').addEventListener('input', validateStep2);
            document.getElementById('signupBtn').addEventListener('click', submitSignup);
            document.getElementById('signupAgainBtn').addEventListener('click', resetForm);

            // Infinite scroll for song list
            window.addEventListener('scroll', handleScroll);

            startPolling();
        });

        function handleScroll() {
            // Check if we're near the bottom of the page for lazy loading
            const scrolledToBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 200);

            if (scrolledToBottom && !isLoadingMore && displayedSongCount < currentFilteredSongs.length) {
                loadMoreSongs();
            }

            // Show/hide fixed buttons based on scroll position
            updateFixedButtons();
        }

        function updateFixedButtons() {
            const fixedButtons = document.getElementById('fixedButtons');
            const currentStep = document.querySelector('.signup-step.active');
            const isStep1 = currentStep && currentStep.id === 'step1';
            const scrolledDown = window.scrollY > 150;

            if (isStep1 && scrolledDown) {
                fixedButtons.classList.add('visible');
            } else {
                fixedButtons.classList.remove('visible');
            }
        }

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function loadMoreSongs() {
            if (isLoadingMore) return;
            if (displayedSongCount >= currentFilteredSongs.length) return;

            isLoadingMore = true;
            const container = document.getElementById('songResults');

            // Remove loading indicator if it exists
            const loadingEl = container.querySelector('.song-list-loading');

            // Render next batch
            const nextBatch = currentFilteredSongs.slice(displayedSongCount, displayedSongCount + SONGS_PER_BATCH);
            const selectedId = document.getElementById('selectedSongId').value;

            const html = nextBatch.map(song => renderSongItem(song, selectedId)).join('');

            if (loadingEl) {
                loadingEl.remove();
            }

            container.insertAdjacentHTML('beforeend', html);
            displayedSongCount += nextBatch.length;

            // Add loading indicator if more songs available
            if (displayedSongCount < currentFilteredSongs.length) {
                container.insertAdjacentHTML('beforeend',
                    '<div class="song-list-loading"><div class="spinner-border spinner-border-sm"></div> Loading more songs...</div>'
                );
            }

            isLoadingMore = false;
        }

        function startPolling() {
            pollInterval = setInterval(pollForUpdates, POLL_INTERVAL_MS);
        }

        async function pollForUpdates() {
            try {
                const response = await fetch(`${API_BASE}/entries.php?event_id=${EVENT_ID}`);
                const data = await response.json();

                if (data.error) return;

                // Update total slots if event was edited
                if (data.total_slots && data.total_slots !== numSlots) {
                    numSlots = data.total_slots;
                }

                const newEntriesJson = JSON.stringify(data.entries || []);
                const oldEntriesJson = JSON.stringify(entries);

                if (newEntriesJson !== oldEntriesJson || data.total_slots !== numSlots) {
                    entries = data.entries || [];
                    updateSlotsCounter();
                }
            } catch (err) {
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

                // Update total slots from server (in case event was edited)
                if (data.total_slots) {
                    numSlots = data.total_slots;
                }

                updateSlotsCounter();

                // Hide loading, show appropriate state
                document.getElementById('loadingState').style.display = 'none';

                if (entries.length >= numSlots) {
                    showStep('fullState');
                } else {
                    showStep('step1');
                    renderSongList(songs);
                }
            } catch (err) {
                console.error('Failed to load data:', err);
                document.getElementById('loadingState').innerHTML =
                    '<div class="text-center text-danger p-4">Failed to load. Please refresh.</div>';
            }
        }

        function updateSlotsCounter() {
            document.getElementById('filledCount').textContent = entries.length;
            document.getElementById('totalCount').textContent = numSlots;

            const currentStep = document.querySelector('.signup-step.active');

            // Check if slots are now full and user is on step 1
            if (currentStep && currentStep.id === 'step1' && entries.length >= numSlots) {
                showStep('fullState');
            }

            // Check if slots have opened up and user is on full state
            if (currentStep && currentStep.id === 'fullState' && entries.length < numSlots) {
                showStep('step1');
                renderSongList(songs);
            }
        }

        function showStep(stepId) {
            document.querySelectorAll('.signup-step').forEach(el => el.classList.remove('active'));
            document.getElementById(stepId).classList.add('active');
            // Update fixed buttons visibility when step changes
            updateFixedButtons();
        }

        function goToStep1() {
            showStep('step1');
        }

        function goToStep2() {
            const songId = document.getElementById('selectedSongId').value;
            if (!songId) return;

            // Find selected song and display it
            const song = songs.find(s => s.song_id == songId);
            if (song) {
                if (song.album_art) {
                    document.getElementById('selectedArt').outerHTML =
                        `<img src="data:image/jpeg;base64,${song.album_art}" class="song-art" id="selectedArt">`;
                }
                document.getElementById('selectedTitle').textContent = song.title;
                document.getElementById('selectedArtist').textContent = song.artist;
            }

            // Scroll to top before showing step 2
            window.scrollTo({ top: 0, behavior: 'instant' });

            showStep('step2');
            document.getElementById('performerName').focus();
        }

        function validateStep2() {
            const name = document.getElementById('performerName').value.trim();
            document.getElementById('signupBtn').disabled = !name;
        }

        function resetForm() {
            document.getElementById('selectedSongId').value = '';
            document.getElementById('performerName').value = '';
            document.getElementById('songSearch').value = '';

            // Reset selected art back to placeholder
            const artEl = document.getElementById('selectedArt');
            if (artEl.tagName === 'IMG') {
                artEl.outerHTML = '<div class="song-art bg-secondary" id="selectedArt"></div>';
            }

            // Disable all next/signup buttons
            document.getElementById('nextBtn').disabled = true;
            document.getElementById('fixedNextBtn').disabled = true;
            document.getElementById('signupBtn').disabled = true;

            // Scroll to top and hide fixed buttons
            window.scrollTo({ top: 0, behavior: 'instant' });

            // Reload data to get fresh state
            loadData();
        }

        function renderSongItem(song, selectedId) {
            return `
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
            `;
        }

        function renderSongList(songsToShow) {
            const container = document.getElementById('songResults');

            // Reset state
            displayedSongCount = 0;
            currentFilteredSongs = songsToShow;

            if (songsToShow.length === 0) {
                const query = document.getElementById('songSearch').value;
                container.innerHTML = query
                    ? '<div class="text-center py-3" style="opacity:0.6">No matching songs</div>'
                    : '<div class="text-center py-3" style="opacity:0.6">No songs available</div>';
                return;
            }

            const selectedId = document.getElementById('selectedSongId').value;
            const initialBatch = songsToShow.slice(0, SONGS_PER_BATCH);

            let html = initialBatch.map(song => renderSongItem(song, selectedId)).join('');
            displayedSongCount = initialBatch.length;

            // Add loading indicator if more songs available
            if (displayedSongCount < songsToShow.length) {
                html += '<div class="song-list-loading"><div class="spinner-border spinner-border-sm"></div> Scroll for more songs...</div>';
            }

            container.innerHTML = html;
        }

        function filterSongs() {
            const query = document.getElementById('songSearch').value.toLowerCase();
            if (!query) {
                renderSongList(songs);
                return;
            }
            const filtered = songs.filter(s =>
                s.title.toLowerCase().includes(query) ||
                s.artist.toLowerCase().includes(query)
            );
            renderSongList(filtered);
        }

        function selectSong(songId) {
            document.getElementById('selectedSongId').value = songId;

            document.querySelectorAll('.song-item').forEach(el => {
                el.classList.toggle('selected', el.dataset.songId == songId);
            });

            // Enable both next buttons (inline and fixed)
            document.getElementById('nextBtn').disabled = false;
            document.getElementById('fixedNextBtn').disabled = false;
        }

        async function submitSignup() {
            const name = document.getElementById('performerName').value.trim();
            const songId = document.getElementById('selectedSongId').value;

            if (!name || !songId) return;

            const btn = document.getElementById('signupBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Signing up...';

            try {
                const response = await fetch(`${API_BASE}/entries.php?event_id=${EVENT_ID}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        performer_name: name,
                        song_id: parseInt(songId)
                    })
                });

                const result = await response.json();

                if (result.error) {
                    throw new Error(result.error);
                }

                // Show success
                const song = songs.find(s => s.song_id == songId);
                document.getElementById('assignedSlot').textContent = `#${result.position}`;
                document.getElementById('successSongInfo').textContent =
                    song ? `"${song.title}" by ${song.artist}` : '';

                // Reset button state before transitioning
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Sign Up';

                // Reload data to get accurate entry count (don't manually push - polling handles it)
                await loadData();

                showStep('successState');

            } catch (err) {
                const errorMsg = err.message || 'Failed to sign up. Please try again.';

                if (errorMsg.toLowerCase().includes('full')) {
                    alert('All spots are now filled!');
                    showStep('fullState');
                } else {
                    alert(errorMsg);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg"></i> Sign Up';
                }
            }
        }

        async function togglePreview(el) {
            const deezerId = el.dataset.deezerId;

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

            el.classList.add('loading');
            el.classList.replace('bi-play-circle', 'bi-arrow-repeat');

            try {
                const response = await fetch(`${API_BASE}/deezer.php?track_id=${deezerId}`);
                const data = await response.json();

                if (data.error || !data.preview) {
                    throw new Error(data.error || 'No preview available');
                }

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
