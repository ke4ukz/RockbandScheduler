<?php
/**
 * Rockband Scheduler - Admin Entries Management
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
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .preview-btn.loading {
            animation: spin 1s linear infinite;
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
        .move-btns {
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-right: 0.5rem;
        }
        .move-btns .btn {
            padding: 0.15rem 0.35rem;
            font-size: 0.75rem;
            line-height: 1;
        }
        .busy-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .busy-overlay.show {
            display: flex;
        }
        .busy-overlay .spinner-border {
            width: 3rem;
            height: 3rem;
            color: white;
        }
    </style>
</head>
<body>
    <div class="busy-overlay" id="busyOverlay">
        <div class="spinner-border"></div>
    </div>

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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="help.php"><i class="bi bi-question-circle"></i> Help</a>
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
                <a href="events.php?action=edit&eventid=<?= h($eventId) ?>" class="btn btn-outline-primary me-1">
                    <i class="bi bi-pencil"></i> Edit Event
                </a>
                <a href="../?eventid=<?= h($eventId) ?>" target="_blank" class="btn btn-outline-secondary me-1">
                    <i class="bi bi-box-arrow-up-right"></i> Signup Page
                </a>
                <button class="btn btn-outline-secondary" onclick="loadEntries()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Performance Lineup</span>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-trash"></i> Clear
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="clearUnfinished(); return false;">
                                        <i class="bi bi-circle"></i> Clear Unfinished
                                    </a></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="clearAll(); return false;">
                                        <i class="bi bi-trash"></i> Clear All Entries
                                    </a></li>
                                </ul>
                            </div>
                        </div>
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
                                <button class="btn btn-outline-secondary" type="button" onclick="openSongPicker()">
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
                    <button type="button" class="btn-close" onclick="closeSongPicker()"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" id="songSearchInput" placeholder="Search songs...">
                    <div id="songPickerList" class="list-group" style="max-height: 400px; overflow-y: auto;">
                        <div class="list-group-item text-center">Loading songs...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeSongPicker()">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
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
        let isModalOpen = false;
        let pollInterval = null;
        const POLL_INTERVAL_MS = 5000; // Poll every 5 seconds

        document.addEventListener('DOMContentLoaded', function() {
            editEntryModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
            songPickerModal = new bootstrap.Modal(document.getElementById('songPickerModal'));

            loadEntries();
            loadSongs();

            document.getElementById('songSearchInput').addEventListener('input', filterSongs);

            // Track modal open/close state
            document.getElementById('editEntryModal').addEventListener('shown.bs.modal', () => isModalOpen = true);
            document.getElementById('editEntryModal').addEventListener('hidden.bs.modal', () => isModalOpen = false);
            document.getElementById('songPickerModal').addEventListener('shown.bs.modal', () => isModalOpen = true);
            document.getElementById('songPickerModal').addEventListener('hidden.bs.modal', () => isModalOpen = false);

            // Start background polling
            startPolling();
        });

        function startPolling() {
            pollInterval = setInterval(pollForUpdates, POLL_INTERVAL_MS);
        }

        async function pollForUpdates() {
            // Don't update if modal is open
            if (isModalOpen) return;

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

                if (data.error) return; // Silently fail on poll errors

                // Check if data actually changed before re-rendering
                const newEntriesJson = JSON.stringify(data.entries || []);
                const oldEntriesJson = JSON.stringify(entries);

                if (newEntriesJson !== oldEntriesJson) {
                    // Save scroll position
                    const scrollPos = window.scrollY;

                    event = data.event;
                    entries = data.entries;
                    updateEventInfo();
                    renderEntries();

                    // Restore scroll position
                    window.scrollTo(0, scrollPos);
                }
            } catch (err) {
                // Silently fail on poll errors
                console.debug('Poll failed:', err);
            }
        }

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
                console.log('Loaded songs:', songs.length);
            } catch (err) {
                console.error('Failed to load songs:', err);
                document.getElementById('songPickerList').innerHTML =
                    '<div class="list-group-item text-danger">Failed to load songs: ' + escapeHtml(err.message) + '</div>';
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
                // Entry is filled if it has a performer name OR a song
                const isEmpty = !entry || (!entry.performer_name && !entry.song_id);

                const isFinished = entry?.finished || false;
                const canMoveUp = !isEmpty && !isFinished && pos > 1;
                const canMoveDown = !isEmpty && !isFinished && pos < totalSlots;
                html += `
                    <div class="list-group-item slot-card ${isEmpty ? 'empty' : ''} ${isFinished ? 'finished' : ''}"
                         data-position="${pos}"
                         data-entry-id="${entry?.entry_id || ''}">
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
                                ${!isFinished ? `
                                <div class="move-btns">
                                    <button class="btn btn-outline-secondary" onclick="moveEntry(${entry.entry_id}, ${pos}, -1)" ${canMoveUp ? '' : 'disabled'} title="Move up">
                                        <i class="bi bi-chevron-up"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="moveEntry(${entry.entry_id}, ${pos}, 1)" ${canMoveDown ? '' : 'disabled'} title="Move down">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                                ` : ''}
                                <i class="bi ${isFinished ? 'bi-check-circle-fill checked' : 'bi-circle'} finished-check me-3"
                                   data-entry-id="${entry.entry_id}"
                                   onclick="event.stopPropagation(); toggleFinished(this, ${entry.entry_id}, ${!isFinished})"
                                   title="${isFinished ? 'Mark as not finished' : 'Mark as finished'}"></i>
                                ${entry.album_art
                                    ? `<img src="data:image/jpeg;base64,${entry.album_art}" class="album-art-small rounded me-3">`
                                    : '<div class="album-art-small bg-secondary rounded me-3 d-flex align-items-center justify-content-center"><i class="bi bi-music-note text-white"></i></div>'}
                                <div class="flex-grow-1">
                                    <div class="fw-bold">${entry.performer_name ? escapeHtml(entry.performer_name) : '<em class="text-muted">No name</em>'}</div>
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

        async function openSongPicker() {
            // Hide edit modal first, then show song picker
            editEntryModal.hide();
            document.getElementById('songSearchInput').value = '';

            // If songs weren't loaded, try again
            if (songs.length === 0) {
                document.getElementById('songPickerList').innerHTML =
                    '<div class="list-group-item text-center"><div class="spinner-border spinner-border-sm"></div> Loading songs...</div>';
                await loadSongs();
            }

            renderSongPicker(songs);
            setTimeout(() => songPickerModal.show(), 150);
        }

        function closeSongPicker() {
            // Go back to edit modal
            songPickerModal.hide();
            setTimeout(() => editEntryModal.show(), 150);
        }

        function selectSong(songId) {
            const song = songs.find(s => s.song_id === songId);
            if (!song) return;

            document.getElementById('editSongId').value = songId;
            document.getElementById('editSongDisplay').value = `${song.title} - ${song.artist}`;

            // Hide song picker and show edit modal again
            songPickerModal.hide();
            setTimeout(() => editEntryModal.show(), 150);
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
            const performerName = document.getElementById('editPerformerName').value.trim();
            const songId = document.getElementById('editSongId').value;

            // Require at least a name or a song to create an entry
            if (!performerName && !songId) {
                alert('Please enter a performer name or select a song');
                return;
            }

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

        // Move entry up or down
        async function moveEntry(entryId, currentPos, direction) {
            const newPos = currentPos + direction;
            if (newPos < 1 || newPos > event.num_entries) return;

            // Show busy overlay
            document.getElementById('busyOverlay').classList.add('show');

            // Find if there's an entry at the target position
            const entryMap = {};
            entries.forEach(e => entryMap[e.position] = e);
            const targetEntry = entryMap[newPos];

            const orderUpdates = [
                { entry_id: entryId, position: newPos }
            ];

            // If there's an entry at target, swap positions
            if (targetEntry) {
                orderUpdates.push({ entry_id: targetEntry.entry_id, position: currentPos });
            }

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

                await loadEntries();
            } catch (err) {
                alert('Failed to move: ' + err.message);
                await loadEntries();
            } finally {
                document.getElementById('busyOverlay').classList.remove('show');
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

        function formatDateTime(dateStr) {
            return new Date(dateStr).toLocaleString(undefined, {
                weekday: 'short', month: 'short', day: 'numeric',
                hour: 'numeric', minute: '2-digit'
            });
        }

        async function clearUnfinished() {
            const unfinishedEntries = entries.filter(e => !e.finished);
            if (unfinishedEntries.length === 0) {
                alert('No unfinished entries to clear.');
                return;
            }

            if (!confirm(`Are you sure you want to clear ${unfinishedEntries.length} unfinished entries?`)) {
                return;
            }

            try {
                for (const entry of unfinishedEntries) {
                    await fetch(`${API_BASE}/entries.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            admin_token: ADMIN_TOKEN,
                            action: 'delete',
                            entry_id: entry.entry_id
                        })
                    });
                }
                loadEntries();
            } catch (err) {
                console.error('Failed to clear entries:', err);
                alert('Failed to clear some entries. Please try again.');
                loadEntries();
            }
        }

        async function clearAll() {
            if (entries.length === 0) {
                alert('No entries to clear.');
                return;
            }

            if (!confirm(`Are you sure you want to clear ALL ${entries.length} entries? This cannot be undone.`)) {
                return;
            }

            try {
                for (const entry of entries) {
                    await fetch(`${API_BASE}/entries.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            admin_token: ADMIN_TOKEN,
                            action: 'delete',
                            entry_id: entry.entry_id
                        })
                    });
                }
                loadEntries();
            } catch (err) {
                console.error('Failed to clear entries:', err);
                alert('Failed to clear some entries. Please try again.');
                loadEntries();
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
