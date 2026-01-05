<?php
/**
 * Rockband Scheduler - Admin Songs Management
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

startAdminSession();
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Song Library - Rockband Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .album-art-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
        .preview-btn {
            cursor: pointer;
        }
        .preview-btn.playing {
            color: #198754;
        }
        .preview-btn.error {
            color: #dc3545;
        }
        .preview-btn.loading {
            color: #6c757d;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .preview-btn.loading {
            animation: spin 1s linear infinite;
        }
        #deezerResults {
            max-height: 400px;
            overflow-y: auto;
        }
        .deezer-result {
            cursor: pointer;
        }
        .deezer-result:hover {
            background-color: #f8f9fa;
        }
        .deezer-thumb {
            width: 56px;
            height: 56px;
            object-fit: cover;
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
                        <a class="nav-link active" href="songs.php">Songs</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Song Library</h1>
            <div>
                <a href="import.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-upload"></i> Import CSV
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#songModal" onclick="openAddModal()">
                    <i class="bi bi-plus-lg"></i> Add Song
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search songs...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="songsTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;"></th>
                                <th>Title</th>
                                <th>Artist</th>
                                <th>Album</th>
                                <th>Year</th>
                                <th>Duration</th>
                                <th style="width: 50px;">Preview</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="songsTableBody">
                            <tr>
                                <td colspan="8" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="loadMoreSection" class="text-center py-3" style="display: none;">
                    <span class="text-muted" id="loadedCount"></span>
                </div>
                <div id="loadingMore" class="text-center py-3" style="display: none;">
                    <span class="spinner-border spinner-border-sm"></span> Loading more...
                </div>
                <!-- Sentinel element for intersection observer -->
                <div id="loadMoreSentinel" style="height: 1px;"></div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Song Modal -->
    <div class="modal fade" id="songModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="songModalTitle">Add Song</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Deezer Search Section -->
                    <div class="card mb-3 bg-light">
                        <div class="card-body">
                            <h6 class="card-title"><i class="bi bi-search"></i> Search Deezer</h6>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="deezerSearch" placeholder="Search for a song on Deezer...">
                                <button class="btn btn-outline-secondary" type="button" onclick="searchDeezer()">Search</button>
                            </div>
                            <div id="deezerResults" class="list-group"></div>
                        </div>
                    </div>

                    <!-- Manual Entry Form -->
                    <form id="songForm">
                        <input type="hidden" id="songId" name="song_id">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="songTitle" class="form-label">Title *</label>
                                <input type="text" class="form-control" id="songTitle" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label for="songArtist" class="form-label">Artist *</label>
                                <input type="text" class="form-control" id="songArtist" name="artist" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="songAlbum" class="form-label">Album *</label>
                                <input type="text" class="form-control" id="songAlbum" name="album" required>
                            </div>
                            <div class="col-md-3">
                                <label for="songYear" class="form-label">Year *</label>
                                <input type="number" class="form-control" id="songYear" name="year" min="1900" max="2100" required>
                            </div>
                            <div class="col-md-3">
                                <label for="songDuration" class="form-label">Duration (sec)</label>
                                <input type="number" class="form-control" id="songDuration" name="duration" min="0" value="0">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="deezerId" class="form-label">Deezer ID</label>
                                <input type="number" class="form-control" id="deezerId" name="deezer_id">
                            </div>
                            <div class="col-md-6">
                                <label for="previewUrl" class="form-label">Preview URL</label>
                                <input type="url" class="form-control" id="previewUrl" name="preview_url">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="albumArtUrl" class="form-label">Album Art URL (will be fetched and stored)</label>
                            <input type="url" class="form-control" id="albumArtUrl" name="album_art_url" placeholder="https://...">
                            <div class="form-text">Leave empty to keep existing art when editing</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSong()">Save Song</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="deleteSongTitle"></span>"?</p>
                    <p class="text-muted small">This cannot be undone. Any event entries using this song will have their song reference cleared.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
        const API_BASE = '../api';
        const PAGE_SIZE = 50;

        let songs = [];
        let totalSongs = 0;
        let currentOffset = 0;
        let currentSearch = '';
        let isLoading = false;
        let searchTimeout = null;
        let currentAudio = null;
        let deleteSongId = null;
        let songModal, deleteModal;

        document.addEventListener('DOMContentLoaded', function() {
            songModal = new bootstrap.Modal(document.getElementById('songModal'));
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            loadSongs();

            // Debounced search
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentSearch = this.value.trim();
                    currentOffset = 0;
                    songs = [];
                    loadSongs();
                }, 300);
            });

            document.getElementById('deezerSearch').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchDeezer();
                }
            });

            // Check if we should auto-open the add modal
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'add') {
                openAddModal();
                songModal.show();
                // Clean up URL
                window.history.replaceState({}, '', window.location.pathname);
            }

            // Set up intersection observer for infinite scroll
            const sentinel = document.getElementById('loadMoreSentinel');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !isLoading && songs.length < totalSongs) {
                        loadMoreSongs();
                    }
                });
            }, {
                rootMargin: '100px' // Start loading 100px before sentinel is visible
            });
            observer.observe(sentinel);
        });

        async function loadSongs(append = false) {
            if (isLoading) return;
            isLoading = true;

            if (!append) {
                document.getElementById('songsTableBody').innerHTML =
                    '<tr><td colspan="8" class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';
                document.getElementById('loadMoreSection').style.display = 'none';
            } else {
                document.getElementById('loadingMore').style.display = 'block';
            }

            try {
                const response = await fetch(`${API_BASE}/songs.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: CSRF_TOKEN,
                        action: 'list',
                        limit: PAGE_SIZE,
                        offset: currentOffset,
                        search: currentSearch
                    })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                totalSongs = data.total;
                const newSongs = data.songs || [];

                if (append) {
                    songs = songs.concat(newSongs);
                    appendSongs(newSongs);
                } else {
                    songs = newSongs;
                    renderSongs(songs);
                }

                currentOffset += newSongs.length;
                updateLoadMoreButton();

            } catch (err) {
                console.error('Failed to load songs:', err);
                if (!append) {
                    document.getElementById('songsTableBody').innerHTML =
                        '<tr><td colspan="8" class="text-center text-danger">Failed to load songs</td></tr>';
                }
            } finally {
                isLoading = false;
                document.getElementById('loadingMore').style.display = 'none';
            }
        }

        function loadMoreSongs() {
            loadSongs(true);
        }

        function updateLoadMoreButton() {
            const loadMoreSection = document.getElementById('loadMoreSection');
            const loadedCount = document.getElementById('loadedCount');

            if (songs.length < totalSongs) {
                loadMoreSection.style.display = 'block';
                loadedCount.textContent = `Showing ${songs.length} of ${totalSongs} songs`;
            } else {
                loadMoreSection.style.display = 'none';
            }
        }

        function renderSongs(songsToRender) {
            const tbody = document.getElementById('songsTableBody');
            if (songsToRender.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No songs found</td></tr>';
                return;
            }

            tbody.innerHTML = songsToRender.map(song => renderSongRow(song)).join('');
        }

        function appendSongs(newSongs) {
            const tbody = document.getElementById('songsTableBody');
            tbody.insertAdjacentHTML('beforeend', newSongs.map(song => renderSongRow(song)).join(''));
        }

        function renderSongRow(song) {
            return `
                <tr data-song-id="${song.song_id}">
                    <td>
                        ${song.album_art
                            ? `<img src="data:image/jpeg;base64,${song.album_art}" class="album-art-thumb rounded" alt="">`
                            : '<div class="album-art-thumb bg-secondary rounded d-flex align-items-center justify-content-center"><i class="bi bi-music-note text-white"></i></div>'}
                    </td>
                    <td>${escapeHtml(song.title)}</td>
                    <td>${escapeHtml(song.artist)}</td>
                    <td>${escapeHtml(song.album)}</td>
                    <td>${song.year}</td>
                    <td>${formatDuration(song.duration)}</td>
                    <td>
                        ${song.deezer_id
                            ? `<i class="bi bi-play-circle preview-btn fs-4" data-deezer-id="${song.deezer_id}" onclick="togglePreview(this)"></i>`
                            : '-'}
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editSong(${song.song_id})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSong(${song.song_id}, '${escapeHtml(song.title).replace(/'/g, "\\'")}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }

        function formatDuration(seconds) {
            if (!seconds) return '-';
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        let currentDeezerId = null;

        async function togglePreview(el) {
            const deezerId = el.dataset.deezerId;

            // Stop any playing audio
            if (currentAudio) {
                currentAudio.pause();
                document.querySelectorAll('.preview-btn').forEach(btn => {
                    btn.classList.remove('playing', 'error', 'loading');
                    btn.classList.replace('bi-stop-circle', 'bi-play-circle');
                    btn.classList.replace('bi-exclamation-triangle', 'bi-play-circle');
                    btn.classList.replace('bi-arrow-repeat', 'bi-play-circle');
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
            el.classList.replace('bi-stop-circle', 'bi-exclamation-triangle');
            el.classList.replace('bi-arrow-repeat', 'bi-exclamation-triangle');
            currentAudio = null;
            currentDeezerId = null;
            setTimeout(() => {
                el.classList.remove('error');
                el.classList.replace('bi-exclamation-triangle', 'bi-play-circle');
            }, 2000);
        }

        function openAddModal() {
            document.getElementById('songModalTitle').textContent = 'Add Song';
            document.getElementById('songForm').reset();
            document.getElementById('songId').value = '';
            document.getElementById('deezerResults').innerHTML = '';
        }

        function editSong(songId) {
            const song = songs.find(s => s.song_id === songId);
            if (!song) return;

            document.getElementById('songModalTitle').textContent = 'Edit Song';
            document.getElementById('songId').value = song.song_id;
            document.getElementById('songTitle').value = song.title;
            document.getElementById('songArtist').value = song.artist;
            document.getElementById('songAlbum').value = song.album;
            document.getElementById('songYear').value = song.year;
            document.getElementById('songDuration').value = song.duration || 0;
            document.getElementById('deezerId').value = song.deezer_id || '';
            document.getElementById('previewUrl').value = song.preview_url || '';
            document.getElementById('albumArtUrl').value = '';
            document.getElementById('deezerResults').innerHTML = '';

            songModal.show();
        }

        async function saveSong() {
            const form = document.getElementById('songForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const songId = document.getElementById('songId').value;
            const data = {
                title: document.getElementById('songTitle').value,
                artist: document.getElementById('songArtist').value,
                album: document.getElementById('songAlbum').value,
                year: parseInt(document.getElementById('songYear').value),
                duration: parseInt(document.getElementById('songDuration').value) || 0,
                deezer_id: document.getElementById('deezerId').value ? parseInt(document.getElementById('deezerId').value) : null,
                preview_url: document.getElementById('previewUrl').value || null,
                album_art_url: document.getElementById('albumArtUrl').value || null
            };

            try {
                const requestBody = {
                    csrf_token: CSRF_TOKEN,
                    action: songId ? 'update' : 'create',
                    ...data
                };
                if (songId) {
                    requestBody.song_id = parseInt(songId);
                }

                const response = await fetch(`${API_BASE}/songs.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestBody)
                });

                const result = await response.json();
                if (result.error) throw new Error(result.error);

                songModal.hide();
                // Reset and reload from beginning
                currentOffset = 0;
                songs = [];
                loadSongs();
            } catch (err) {
                alert('Failed to save song: ' + err.message);
            }
        }

        function deleteSong(songId, title) {
            deleteSongId = songId;
            document.getElementById('deleteSongTitle').textContent = title;
            deleteModal.show();
        }

        async function confirmDelete() {
            if (!deleteSongId) return;

            try {
                const response = await fetch(`${API_BASE}/songs.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: CSRF_TOKEN,
                        action: 'delete',
                        song_id: deleteSongId
                    })
                });
                const result = await response.json();
                if (result.error) throw new Error(result.error);

                deleteModal.hide();
                deleteSongId = null;
                // Reset and reload from beginning
                currentOffset = 0;
                songs = [];
                loadSongs();
            } catch (err) {
                alert('Failed to delete song: ' + err.message);
            }
        }

        async function searchDeezer() {
            const query = document.getElementById('deezerSearch').value.trim();
            if (!query) return;

            const resultsDiv = document.getElementById('deezerResults');
            resultsDiv.innerHTML = '<div class="list-group-item text-center"><div class="spinner-border spinner-border-sm"></div> Searching...</div>';

            try {
                const response = await fetch(`${API_BASE}/deezer.php?q=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.error) throw new Error(data.error);
                if (!data.data || data.data.length === 0) {
                    resultsDiv.innerHTML = '<div class="list-group-item text-muted">No results found</div>';
                    return;
                }

                resultsDiv.innerHTML = data.data.map(track => `
                    <div class="list-group-item list-group-item-action deezer-result d-flex align-items-center" onclick="selectDeezerTrack(${track.id})">
                        <img src="${track.album.cover_small}" class="deezer-thumb rounded me-3" alt="">
                        <div class="flex-grow-1">
                            <div class="fw-bold">${escapeHtml(track.title)}</div>
                            <div class="text-muted small">${escapeHtml(track.artist.name)} &bull; ${escapeHtml(track.album.title)}</div>
                        </div>
                        ${track.preview ? `<i class="bi bi-play-circle fs-4 text-muted me-2" data-preview="${escapeHtml(track.preview)}" onclick="event.stopPropagation(); togglePreview(this)"></i>` : ''}
                    </div>
                `).join('');

                // Store track data for selection
                resultsDiv.dataset.tracks = JSON.stringify(data.data);
            } catch (err) {
                resultsDiv.innerHTML = `<div class="list-group-item text-danger">Search failed: ${escapeHtml(err.message)}</div>`;
            }
        }

        async function selectDeezerTrack(trackId) {
            const tracks = JSON.parse(document.getElementById('deezerResults').dataset.tracks || '[]');
            const track = tracks.find(t => t.id === trackId);
            if (!track) return;

            document.getElementById('songTitle').value = track.title;
            document.getElementById('songArtist').value = track.artist.name;
            document.getElementById('songAlbum').value = track.album.title;
            document.getElementById('songDuration').value = track.duration || 0;
            document.getElementById('deezerId').value = track.id;
            document.getElementById('previewUrl').value = track.preview || '';
            document.getElementById('albumArtUrl').value = track.album.cover_medium || '';

            // Fetch album details to get the release year
            document.getElementById('deezerResults').innerHTML =
                '<div class="list-group-item text-info"><i class="bi bi-hourglass-split"></i> Fetching album details...</div>';

            try {
                const albumResponse = await fetch(`${API_BASE}/deezer.php?album_id=${track.album.id}`);
                const albumData = await albumResponse.json();

                if (albumData.year) {
                    document.getElementById('songYear').value = albumData.year;
                    document.getElementById('deezerResults').innerHTML =
                        '<div class="list-group-item text-success"><i class="bi bi-check-circle"></i> Track selected! All fields filled.</div>';
                } else {
                    document.getElementById('deezerResults').innerHTML =
                        '<div class="list-group-item text-warning"><i class="bi bi-exclamation-triangle"></i> Track selected, but year not available. Please fill it in manually.</div>';
                }
            } catch (err) {
                console.error('Failed to fetch album details:', err);
                document.getElementById('deezerResults').innerHTML =
                    '<div class="list-group-item text-warning"><i class="bi bi-exclamation-triangle"></i> Track selected, but couldn\'t fetch year. Please fill it in manually.</div>';
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
