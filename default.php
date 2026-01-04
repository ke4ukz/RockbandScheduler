<?php
require_once 'config.php';
require_once 'db.php';
require_once 'includes/helpers.php';

$db = $GLOBALS['db'];
$eventId = $_GET['eventid'] ?? null;
$error = null;
$event = null;

// Validate event ID and check if event is active
if (!$eventId) {
    $error = 'No event specified. Please scan a valid QR code.';
} elseif (!isValidUuid($eventId)) {
    $error = 'Invalid event link. Please scan a valid QR code.';
} else {
    try {
        $stmt = $db->prepare('
            SELECT BIN_TO_UUID(event_id) as event_id, name, location, start_time, end_time, num_entries
            FROM events
            WHERE event_id = UUID_TO_BIN(?)
        ');
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            $error = 'Event not found. Please scan a valid QR code.';
        } else {
            $now = new DateTime();
            $start = new DateTime($event['start_time']);
            $end = new DateTime($event['end_time']);

            if ($now < $start) {
                $error = 'This event hasn\'t started yet. Check back at ' . $start->format('g:i A') . '.';
            } elseif ($now > $end) {
                $error = 'This event has ended.';
            }
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
            --primary-color: #6f42c1;
        }

        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
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

        .header h1 {
            font-size: 1.25rem;
            margin: 0;
            font-weight: 600;
        }

        .header .location {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .slot-list {
            padding: 1rem;
            padding-bottom: 100px; /* Space for bottom nav */
        }

        .slot-card {
            background: rgba(255,255,255,0.1);
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
            border: 2px dashed rgba(255,255,255,0.3);
            cursor: pointer;
        }

        .slot-card.available:hover,
        .slot-card.available:active {
            background: rgba(255,255,255,0.15);
            border-color: var(--primary-color);
        }

        .slot-card.taken {
            background: rgba(255,255,255,0.05);
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
            background: rgba(255,255,255,0.2);
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
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            font-size: 1.25rem;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-btn i {
            font-size: 1.25rem;
            line-height: 1;
        }

        /* Force consistent icon size for hourglass */
        .preview-btn i.bi-hourglass-split::before {
            font-size: 1rem;
        }

        .preview-btn.playing {
            background: #198754;
        }

        .preview-btn.error {
            background: #dc3545;
        }

        .preview-btn.loading {
            background: rgba(255,255,255,0.2);
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
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            border: none;
        }

        .signup-modal .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .signup-modal .modal-body {
            padding: 1.5rem;
        }

        .signup-modal .form-control {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
        }

        .signup-modal .form-control:focus {
            background: rgba(255,255,255,0.15);
            border-color: var(--primary-color);
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.25);
        }

        .signup-modal .form-control::placeholder {
            color: rgba(255,255,255,0.5);
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
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .song-item:hover,
        .song-item:active {
            background: rgba(255,255,255,0.1);
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
            background: #5a32a3;
            color: #fff;
        }

        .btn-signup:disabled {
            background: rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.5);
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
    <div class="header">
        <h1><?= h($event['name']) ?></h1>
        <?php if ($event['location']): ?>
            <div class="location"><i class="bi bi-geo-alt"></i> <?= h($event['location']) ?></div>
        <?php endif; ?>
    </div>

    <div class="slot-list" id="slotList">
        <div class="loading">
            <div class="spinner-border mb-2"></div>
            <div>Loading lineup...</div>
        </div>
    </div>

    <!-- Sign Up Modal -->
    <div class="modal fade signup-modal" id="signupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sign Up for Slot <span id="signupSlotNumber"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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

        let entries = [];
        let songs = [];
        let currentAudio = null;
        let signupModal;

        document.addEventListener('DOMContentLoaded', function() {
            signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
            loadData();

            document.getElementById('songSearch').addEventListener('input', filterSongs);
            document.getElementById('performerName').addEventListener('input', validateForm);
            document.getElementById('confirmSignup').addEventListener('click', submitSignup);
        });

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
                const isTaken = entry && entry.performer_name;

                if (isTaken) {
                    html += `
                        <div class="slot-card taken">
                            <div class="slot-number">${pos}</div>
                            ${entry.album_art
                                ? `<img src="data:image/jpeg;base64,${entry.album_art}" class="album-art">`
                                : ''}
                            <div class="slot-info">
                                <div class="performer">${escapeHtml(entry.performer_name)}</div>
                                <div class="song">${entry.title ? escapeHtml(entry.title) + ' - ' + escapeHtml(entry.artist) : 'No song selected'}</div>
                            </div>
                            ${entry.deezer_id ? `
                                <button class="preview-btn" data-deezer-id="${entry.deezer_id}" onclick="togglePreview(this)">
                                    <i class="bi bi-play-fill"></i>
                                </button>
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
            document.getElementById('confirmSignup').disabled = true;

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
                        <button class="preview-btn" onclick="event.stopPropagation(); togglePreview(this)" data-deezer-id="${song.deezer_id}">
                            <i class="bi bi-play-fill"></i>
                        </button>
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
            document.getElementById('confirmSignup').disabled = !name || !songId;
        }

        async function submitSignup() {
            const position = document.getElementById('selectedSlotPosition').value;
            const name = document.getElementById('performerName').value.trim();
            const songId = document.getElementById('selectedSongId').value;

            if (!name || !songId) return;

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
                        song_id: parseInt(songId)
                    })
                });

                const result = await response.json();

                if (result.error) {
                    throw new Error(result.error);
                }

                signupModal.hide();
                loadData(); // Refresh the list
            } catch (err) {
                alert(err.message || 'Failed to sign up. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Sign Up';
            }
        }

        let currentDeezerId = null;

        async function togglePreview(el) {
            const deezerId = el.dataset.deezerId;
            const icon = el.querySelector('i');

            // Stop any playing audio
            if (currentAudio) {
                currentAudio.pause();
                document.querySelectorAll('.preview-btn').forEach(btn => {
                    btn.classList.remove('playing', 'loading', 'error');
                    btn.querySelector('i').className = 'bi bi-play-fill';
                });

                if (currentDeezerId === deezerId) {
                    currentAudio = null;
                    currentDeezerId = null;
                    return;
                }
            }

            // Show loading state
            el.classList.add('loading');
            icon.className = 'bi bi-hourglass-split';

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
                icon.className = 'bi bi-stop-fill';

                await currentAudio.play();

                currentAudio.addEventListener('ended', () => {
                    el.classList.remove('playing');
                    icon.className = 'bi bi-play-fill';
                    currentAudio = null;
                    currentDeezerId = null;
                });

                currentAudio.addEventListener('error', () => {
                    showPreviewError(el, icon);
                });

            } catch (err) {
                console.error('Preview failed:', err);
                showPreviewError(el, icon);
            }
        }

        function showPreviewError(el, icon) {
            el.classList.remove('playing', 'loading');
            el.classList.add('error');
            icon.className = 'bi bi-exclamation-triangle-fill';
            currentAudio = null;
            currentDeezerId = null;
            setTimeout(() => {
                el.classList.remove('error');
                icon.className = 'bi bi-play-fill';
            }, 2000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
<?php endif; ?>
</body>
</html>
