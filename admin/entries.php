<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/helpers.php';

$adminToken = $GLOBALS['config']['admin']['token'] ?? '';
$eventId = $_GET['eventid'] ?? null;

if (!$eventId || !isValidUuid($eventId)) {
    header('Location: events.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Entries - Rockband Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .slot-card {
            transition: all 0.2s;
        }
        .slot-card.empty {
            border-style: dashed;
            opacity: 0.7;
        }
        .slot-card.dragging {
            opacity: 0.5;
        }
        .slot-card.drag-over {
            border-color: #0d6efd;
            background-color: #f0f7ff;
        }
        .album-art-small {
            width: 40px;
            height: 40px;
            object-fit: cover;
        }
        .slot-number {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .preview-btn {
            cursor: pointer;
        }
        .preview-btn.playing {
            color: #198754;
        }
        .preview-btn.loading {
            color: #6c757d;
        }
        .preview-btn.error {
            color: #dc3545;
        }
        .preview-btn i {
            font-size: 1.25rem;
            line-height: 1;
        }
        .preview-btn i.bi-hourglass-split::before {
            font-size: 1rem;
        }
        .slot-card.finished {
            opacity: 0.5;
            background-color: #f8f9fa;
        }
        .slot-card.finished .fw-bold {
            text-decoration: line-through;
        }
        .finished-check {
            cursor: pointer;
            font-size: 1.25rem;
        }
        .finished-check.checked {
            color: #198754;
        }
    </style>
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="events.php">Events</a></li>
                <li class="breadcrumb-item active" id="eventNameBreadcrumb">Loading...</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 id="eventName">Loading...</h1>
            <div>
                <span id="eventStatus" class="badge me-2"></span>
                <button class="btn btn-outline-secondary" onclick="loadEntries()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Performance Lineup</span>
                        <small class="text-muted">Drag to reorder</small>
                    </div>
                    <div class="card-body p-0">
                        <div id="entriesList" class="list-group list-group-flush">
                            <div class="list-group-item text-center py-4">
                                <div class="spinner-border"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Event Info</div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <dt>Start Time</dt>
                            <dd id="eventStart">-</dd>
                            <dt>End Time</dt>
                            <dd id="eventEnd">-</dd>
                            <dt>Slots Filled</dt>
                            <dd id="slotsFilled">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Entry Modal -->
    <div class="modal fade" id="editEntryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Slot <span id="editSlotNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editEntryForm">
                        <input type="hidden" id="editEntryId">
                        <input type="hidden" id="editPosition">
                        <div class="mb-3">
                            <label for="editPerformerName" class="form-label">Performer Name</label>
                            <input type="text" class="form-control" id="editPerformerName">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Song</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="editSongDisplay" readonly>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#songPickerModal">
                                    <i class="bi bi-search"></i> Change
                                </button>
                            </div>
                            <input type="hidden" id="editSongId">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" onclick="clearEntry()">
                        <i class="bi bi-trash"></i> Clear Slot
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveEntry()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Song Picker Modal -->
    <div class="modal fade" id="songPickerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Song</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" id="songSearchInput" placeholder="Search songs...">
                    <div id="songPickerList" class="list-group" style="max-height: 400px; overflow-y: auto;">
                        <div class="list-group-item text-center">Loading songs...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ADMIN_TOKEN = <?= json_encode($adminToken) ?>;
        const EVENT_ID = <?= json_encode($eventId) ?>;
        const API_BASE = '../api';

        let event = null;
        let entries = [];
        let songs = [];
        let currentAudio = null;
        let editEntryModal, songPickerModal;
        let draggedItem = null;

        document.addEventListener('DOMContentLoaded', function() {
            editEntryModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
            songPickerModal = new bootstrap.Modal(document.getElementById('songPickerModal'));

            loadEntries();
            loadSongs();

            document.getElementById('songSearchInput').addEventListener('input', filterSongs);
        });

        async function loadEntries() {
            try {
                const response = await fetch(`${API_BASE}/entries.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_token: ADMIN_TOKEN,
                        action: 'list',
                        event_id: EVENT_ID
                    })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                event = data.event;
                entries = data.entries;

                updateEventInfo();
                renderEntries();
            } catch (err) {
                console.error('Failed to load entries:', err);
                document.getElementById('entriesList').innerHTML =
                    '<div class="list-group-item text-danger">Failed to load entries: ' + escapeHtml(err.message) + '</div>';
            }
        }

        async function loadSongs() {
            try {
                const response = await fetch(`${API_BASE}/songs.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_token: ADMIN_TOKEN, action: 'list' })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                songs = data.songs || [];
                renderSongPicker(songs);
            } catch (err) {
                console.error('Failed to load songs:', err);
            }
        }

        function updateEventInfo() {
            document.getElementById('eventName').textContent = event.name;
            document.getElementById('eventNameBreadcrumb').textContent = event.name;
            document.getElementById('eventStart').textContent = formatDateTime(event.start_time);
            document.getElementById('eventEnd').textContent = formatDateTime(event.end_time);

            const filled = entries.filter(e => e.performer_name).length;
            document.getElementById('slotsFilled').textContent = `${filled} / ${event.num_entries}`;

            const status = getEventStatus();
            const statusEl = document.getElementById('eventStatus');
            statusEl.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusEl.className = 'badge bg-' + { active: 'success', upcoming: 'primary', past: 'secondary' }[status];
        }

        function getEventStatus() {
            const now = new Date();
            const start = new Date(event.start_time);
            const end = new Date(event.end_time);
            if (now >= start && now <= end) return 'active';
            if (now < start) return 'upcoming';
            return 'past';
        }

        function renderEntries() {
            const list = document.getElementById('entriesList');
            const totalSlots = event.num_entries;

            // Create a map of position -> entry
            const entryMap = {};
            entries.forEach(e => entryMap[e.position] = e);

            let html = '';
            for (let pos = 1; pos <= totalSlots; pos++) {
                const entry = entryMap[pos];
                const isEmpty = !entry || !entry.performer_name;

                const isFinished = entry?.finished || false;
                html += `
                    <div class="list-group-item slot-card ${isEmpty ? 'empty' : ''} ${isFinished ? 'finished' : ''}"
                         data-position="${pos}"
                         data-entry-id="${entry?.entry_id || ''}"
                         draggable="${!isEmpty && !isFinished}"
                         ondragstart="handleDragStart(event)"
                         ondragend="handleDragEnd(event)"
                         ondragover="handleDragOver(event)"
                         ondragleave="handleDragLeave(event)"
                         ondrop="handleDrop(event)">
                        <div class="d-flex align-items-center">
                            <div class="slot-number bg-light rounded me-3">${pos}</div>
                            ${isEmpty ? `
                                <div class="flex-grow-1 text-muted">
                                    <em>Empty slot</em>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="addEntry(${pos})">
                                    <i class="bi bi-plus"></i> Add
                                </button>
                            ` : `
                                <i class="bi ${isFinished ? 'bi-check-circle-fill checked' : 'bi-circle'} finished-check me-3"
                                   data-entry-id="${entry.entry_id}"
                                   onclick="event.stopPropagation(); toggleFinished(this, ${entry.entry_id}, ${!isFinished})"
                                   title="${isFinished ? 'Mark as not finished' : 'Mark as finished'}"></i>
                                ${entry.album_art
                                    ? `<img src="data:image/jpeg;base64,${entry.album_art}" class="album-art-small rounded me-3">`
                                    : '<div class="album-art-small bg-secondary rounded me-3 d-flex align-items-center justify-content-center"><i class="bi bi-music-note text-white"></i></div>'}
                                <div class="flex-grow-1">
                                    <div class="fw-bold">${escapeHtml(entry.performer_name)}</div>
                                    <div class="text-muted small">
                                        ${entry.title ? escapeHtml(entry.title) + ' - ' + escapeHtml(entry.artist) : '<em>No song selected</em>'}
                                    </div>
                                </div>
                                ${entry.deezer_id ? `
                                    <i class="bi bi-play-circle preview-btn fs-4 me-2"
                                       data-deezer-id="${entry.deezer_id}"
                                       onclick="event.stopPropagation(); togglePreview(this)"></i>
                                ` : ''}
                                ${!isFinished ? `
                                    <button class="btn btn-sm btn-outline-secondary" onclick="editEntry(${pos})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                ` : ''}
                            `}
                        </div>
                    </div>
                `;
            }

            list.innerHTML = html;
        }

        function renderSongPicker(songsToShow) {
            const list = document.getElementById('songPickerList');
            if (songsToShow.length === 0) {
                list.innerHTML = '<div class="list-group-item text-muted">No songs found</div>';
                return;
            }

            list.innerHTML = songsToShow.map(song => `
                <div class="list-group-item list-group-item-action d-flex align-items-center" onclick="selectSong(${song.song_id})" style="cursor:pointer;">
                    ${song.album_art
                        ? `<img src="data:image/jpeg;base64,${song.album_art}" class="album-art-small rounded me-3">`
                        : '<div class="album-art-small bg-secondary rounded me-3"></div>'}
                    <div class="flex-grow-1">
                        <div class="fw-bold">${escapeHtml(song.title)}</div>
                        <div class="text-muted small">${escapeHtml(song.artist)}</div>
                    </div>
                </div>
            `).join('');
        }

        function filterSongs() {
            const query = document.getElementById('songSearchInput').value.toLowerCase();
            if (!query) {
                renderSongPicker(songs);
                return;
            }
            const filtered = songs.filter(s =>
                s.title.toLowerCase().includes(query) ||
                s.artist.toLowerCase().includes(query)
            );
            renderSongPicker(filtered);
        }

        function selectSong(songId) {
            const song = songs.find(s => s.song_id === songId);
            if (!song) return;

            document.getElementById('editSongId').value = songId;
            document.getElementById('editSongDisplay').value = `${song.title} - ${song.artist}`;
            songPickerModal.hide();
        }

        function addEntry(position) {
            document.getElementById('editEntryId').value = '';
            document.getElementById('editPosition').value = position;
            document.getElementById('editSlotNumber').textContent = position;
            document.getElementById('editPerformerName').value = '';
            document.getElementById('editSongId').value = '';
            document.getElementById('editSongDisplay').value = '';
            editEntryModal.show();
        }

        function editEntry(position) {
            const entryMap = {};
            entries.forEach(e => entryMap[e.position] = e);
            const entry = entryMap[position];

            document.getElementById('editEntryId').value = entry?.entry_id || '';
            document.getElementById('editPosition').value = position;
            document.getElementById('editSlotNumber').textContent = position;
            document.getElementById('editPerformerName').value = entry?.performer_name || '';
            document.getElementById('editSongId').value = entry?.song_id || '';
            document.getElementById('editSongDisplay').value = entry?.title ? `${entry.title} - ${entry.artist}` : '';
            editEntryModal.show();
        }

        async function saveEntry() {
            const entryId = document.getElementById('editEntryId').value;
            const position = document.getElementById('editPosition').value;
            const performerName = document.getElementById('editPerformerName').value;
            const songId = document.getElementById('editSongId').value;

            const requestBody = {
                admin_token: ADMIN_TOKEN,
                action: entryId ? 'update' : 'create',
                position: parseInt(position),
                performer_name: performerName,
                song_id: songId ? parseInt(songId) : null
            };

            if (entryId) {
                requestBody.entry_id = parseInt(entryId);
            } else {
                requestBody.event_id = EVENT_ID;
            }

            try {
                const response = await fetch(`${API_BASE}/entries.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestBody)
                });

                const result = await response.json();
                if (result.error) throw new Error(result.error);

                editEntryModal.hide();
                loadEntries();
            } catch (err) {
                alert('Failed to save: ' + err.message);
            }
        }

        async function clearEntry() {
            const entryId = document.getElementById('editEntryId').value;
            if (!entryId) {
                editEntryModal.hide();
                return;
            }

            if (!confirm('Clear this slot?')) return;

            try {
                const response = await fetch(`${API_BASE}/entries.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_token: ADMIN_TOKEN,
                        action: 'delete',
                        entry_id: parseInt(entryId)
                    })
                });
                const result = await response.json();
                if (result.error) throw new Error(result.error);

                editEntryModal.hide();
                loadEntries();
            } catch (err) {
                alert('Failed to clear: ' + err.message);
            }
        }

        // Drag and drop handlers
        function handleDragStart(e) {
            draggedItem = e.target.closest('.slot-card');
            draggedItem.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleDragEnd(e) {
            e.target.closest('.slot-card')?.classList.remove('dragging');
            document.querySelectorAll('.slot-card').forEach(el => el.classList.remove('drag-over'));
            draggedItem = null;
        }

        function handleDragOver(e) {
            e.preventDefault();
            const card = e.target.closest('.slot-card');
            if (card && card !== draggedItem) {
                card.classList.add('drag-over');
            }
        }

        function handleDragLeave(e) {
            e.target.closest('.slot-card')?.classList.remove('drag-over');
        }

        async function handleDrop(e) {
            e.preventDefault();
            const dropTarget = e.target.closest('.slot-card');
            if (!dropTarget || !draggedItem || dropTarget === draggedItem) return;

            dropTarget.classList.remove('drag-over');

            const fromPos = parseInt(draggedItem.dataset.position);
            const toPos = parseInt(dropTarget.dataset.position);
            const fromEntryId = draggedItem.dataset.entryId;
            const toEntryId = dropTarget.dataset.entryId;

            // Build order update
            const orderUpdates = [];

            if (fromEntryId) {
                orderUpdates.push({ entry_id: parseInt(fromEntryId), position: toPos });
            }
            if (toEntryId) {
                orderUpdates.push({ entry_id: parseInt(toEntryId), position: fromPos });
            }

            if (orderUpdates.length === 0) return;

            try {
                const response = await fetch(`${API_BASE}/entries.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_token: ADMIN_TOKEN,
                        action: 'reorder',
                        event_id: EVENT_ID,
                        order: orderUpdates
                    })
                });
                const result = await response.json();
                if (result.error) throw new Error(result.error);

                loadEntries();
            } catch (err) {
                alert('Failed to reorder: ' + err.message);
                loadEntries();
            }
        }

        async function toggleFinished(el, entryId, finished) {
            try {
                const response = await fetch(`${API_BASE}/entries.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_token: ADMIN_TOKEN,
                        action: 'update',
                        entry_id: entryId,
                        finished: finished
                    })
                });
                const result = await response.json();
                if (result.error) throw new Error(result.error);

                loadEntries();
            } catch (err) {
                alert('Failed to update: ' + err.message);
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
                    btn.classList.replace('bi-hourglass-split', 'bi-play-circle');
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
            el.classList.replace('bi-play-circle', 'bi-hourglass-split');

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
                el.classList.replace('bi-hourglass-split', 'bi-stop-circle');

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
            el.classList.replace('bi-hourglass-split', 'bi-exclamation-triangle');
            el.classList.replace('bi-stop-circle', 'bi-exclamation-triangle');
            currentAudio = null;
            currentDeezerId = null;
            setTimeout(() => {
                el.classList.remove('error');
                el.classList.replace('bi-exclamation-triangle', 'bi-play-circle');
            }, 2000);
        }

        function formatDateTime(dateStr) {
            return new Date(dateStr).toLocaleString(undefined, {
                weekday: 'short', month: 'short', day: 'numeric',
                hour: 'numeric', minute: '2-digit'
            });
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
