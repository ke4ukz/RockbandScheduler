<?php
/**
 * Rockband Scheduler - Song Import
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
    <title>Import Songs - Rockband Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .import-row {
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 0;
        }
        .import-row:last-child {
            border-bottom: none;
        }
        .import-row.success { background-color: #d1e7dd; }
        .import-row.warning { background-color: #fff3cd; }
        .import-row.error { background-color: #f8d7da; }
        .import-row.pending { background-color: #f8f9fa; }
        .import-row.skipped { background-color: #e9ecef; opacity: 0.7; }
        .deezer-option {
            cursor: pointer;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
        }
        .deezer-option:hover {
            background-color: #f8f9fa;
        }
        .deezer-option.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .deezer-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
        }
        .progress-section {
            position: sticky;
            top: 0;
            background: white;
            z-index: 100;
            padding: 1rem 0;
            border-bottom: 1px solid #dee2e6;
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
            <h1><i class="bi bi-upload"></i> Import Songs</h1>
            <a href="songs.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Songs
            </a>
        </div>

        <!-- Step 1: Upload -->
        <div id="uploadSection" class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Step 1: Upload CSV File</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Upload a CSV file with columns: <code>Song,Artist</code> (or <code>Title,Artist</code>)</p>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="file" class="form-control" id="csvFile" accept=".csv">
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">Limit rows</span>
                            <input type="number" class="form-control" id="rowLimit" min="0" value="0" placeholder="0 = all">
                            <span class="input-group-text text-muted" style="font-size: 0.85rem;">0 = all</span>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="parseCSV()">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Parse CSV
                </button>
            </div>
        </div>

        <!-- Step 2: Review & Search -->
        <div id="reviewSection" class="card mb-4" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0">Step 2: Review & Match Songs</h5>
            </div>
            <div class="card-body">
                <div class="progress-section mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="badge bg-success" id="matchedCount">0</span> matched
                            <span class="badge bg-warning text-dark" id="ambiguousCount">0</span> need review
                            <span class="badge bg-danger" id="notFoundCount">0</span> not found
                            <span class="badge bg-secondary" id="skippedCount">0</span> skipped
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary me-2" onclick="searchAll()">
                                <i class="bi bi-search"></i> Search All
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="searchUnmatched()">
                                <i class="bi bi-arrow-repeat"></i> Retry Unmatched
                            </button>
                        </div>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" id="progressMatched" style="width: 0%"></div>
                        <div class="progress-bar bg-warning" id="progressAmbiguous" style="width: 0%"></div>
                        <div class="progress-bar bg-danger" id="progressNotFound" style="width: 0%"></div>
                        <div class="progress-bar bg-secondary" id="progressSkipped" style="width: 0%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="filterView" id="filterAll" checked onchange="filterView('all')">
                        <label class="btn btn-outline-secondary" for="filterAll">All</label>

                        <input type="radio" class="btn-check" name="filterView" id="filterAmbiguous" onchange="filterView('ambiguous')">
                        <label class="btn btn-outline-warning" for="filterAmbiguous">Need Review</label>

                        <input type="radio" class="btn-check" name="filterView" id="filterNotFound" onchange="filterView('notfound')">
                        <label class="btn btn-outline-danger" for="filterNotFound">Not Found</label>

                        <input type="radio" class="btn-check" name="filterView" id="filterMatched" onchange="filterView('matched')">
                        <label class="btn btn-outline-success" for="filterMatched">Matched</label>
                    </div>
                </div>

                <div id="importList"></div>
            </div>
        </div>

        <!-- Step 3: Import -->
        <div id="importSection" class="card mb-4" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0">Step 3: Import to Database</h5>
            </div>
            <div class="card-body">
                <p>Ready to import <strong id="readyCount">0</strong> songs.</p>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="skipExisting" checked>
                    <label class="form-check-label" for="skipExisting">
                        Skip songs that already exist
                    </label>
                </div>
                <button class="btn btn-success btn-lg" onclick="importSongs()">
                    <i class="bi bi-database-add"></i> Import Songs
                </button>
                <div id="importProgress" class="mt-3" style="display: none;">
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="importProgressBar" style="width: 0%"></div>
                    </div>
                    <div id="importStatus" class="text-muted"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Entry Modal -->
    <div class="modal fade" id="manualEntryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enter Song Details Manually</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="manualEntryForm">
                        <input type="hidden" id="manualEntryIndex">
                        <div class="mb-3">
                            <label for="manualTitle" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="manualTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="manualArtist" class="form-label">Artist *</label>
                            <input type="text" class="form-control" id="manualArtist" required>
                        </div>
                        <div class="mb-3">
                            <label for="manualAlbum" class="form-label">Album *</label>
                            <input type="text" class="form-control" id="manualAlbum" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="manualYear" class="form-label">Year *</label>
                                <input type="number" class="form-control" id="manualYear" min="1900" max="2100" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="manualDuration" class="form-label">Duration (seconds)</label>
                                <input type="number" class="form-control" id="manualDuration" min="0" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="manualAlbumArt" class="form-label">Album Art (optional)</label>
                            <input type="file" class="form-control" id="manualAlbumArt" accept="image/*">
                            <div class="form-text">Max 64KB. Recommended: square image, ~250x250px</div>
                            <div id="manualAlbumArtPreview" class="mt-2" style="display: none;">
                                <img id="manualAlbumArtImg" class="rounded" style="max-width: 100px; max-height: 100px;">
                                <button type="button" class="btn btn-sm btn-link text-danger" onclick="clearManualAlbumArt()">Remove</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveManualEntry()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
        const API_BASE = '../api';

        let importData = []; // Array of { original: {title, artist}, status, deezerResults, selectedTrack, manualData }
        let currentFilter = 'all';
        let manualEntryModal;

        function parseCSV() {
            const fileInput = document.getElementById('csvFile');
            if (!fileInput.files.length) {
                alert('Please select a CSV file');
                return;
            }

            const file = fileInput.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split(/\r?\n/).filter(line => line.trim());

                if (lines.length < 2) {
                    alert('CSV file appears to be empty');
                    return;
                }

                // Parse header
                const header = parseCSVLine(lines[0]).map(h => h.toLowerCase().trim());
                const titleIdx = header.findIndex(h => h === 'song' || h === 'title');
                const artistIdx = header.findIndex(h => h === 'artist');

                if (titleIdx === -1 || artistIdx === -1) {
                    alert('CSV must have "Song" (or "Title") and "Artist" columns');
                    return;
                }

                // Get row limit (0 = no limit)
                const rowLimit = parseInt(document.getElementById('rowLimit').value) || 0;
                const maxRows = rowLimit > 0 ? rowLimit + 1 : lines.length; // +1 for header

                // Parse data rows
                importData = [];
                for (let i = 1; i < Math.min(lines.length, maxRows); i++) {
                    const cols = parseCSVLine(lines[i]);
                    const title = cols[titleIdx]?.trim();
                    const artist = cols[artistIdx]?.trim();

                    if (title && artist) {
                        importData.push({
                            original: { title, artist },
                            status: 'pending', // pending, searching, matched, ambiguous, notfound, skipped
                            deezerResults: [],
                            selectedTrack: null
                        });
                    }
                }

                if (importData.length === 0) {
                    alert('No valid song entries found in CSV');
                    return;
                }

                document.getElementById('uploadSection').style.display = 'none';
                document.getElementById('reviewSection').style.display = 'block';
                document.getElementById('importSection').style.display = 'block';

                renderImportList();
                updateCounts();

                // Auto-start searching
                searchAll();
            };

            reader.readAsText(file);
        }

        function parseCSVLine(line) {
            const result = [];
            let current = '';
            let inQuotes = false;

            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                if (char === '"') {
                    inQuotes = !inQuotes;
                } else if (char === ',' && !inQuotes) {
                    result.push(current);
                    current = '';
                } else {
                    current += char;
                }
            }
            result.push(current);
            return result;
        }

        async function searchAll() {
            for (let i = 0; i < importData.length; i++) {
                if (importData[i].status === 'pending') {
                    await searchSong(i);
                    // Deezer rate limit: 50 requests per 5 seconds (100ms minimum)
                    // Using 120ms for safety buffer
                    await new Promise(r => setTimeout(r, 120));
                }
            }
        }

        async function searchUnmatched() {
            for (let i = 0; i < importData.length; i++) {
                if (importData[i].status === 'notfound' || importData[i].status === 'ambiguous') {
                    importData[i].status = 'pending';
                    await searchSong(i);
                    await new Promise(r => setTimeout(r, 120));
                }
            }
        }

        async function searchSong(index) {
            const item = importData[index];
            item.status = 'searching';
            renderImportRow(index);

            const query = `${item.original.title} ${item.original.artist}`;

            try {
                const response = await fetch(`${API_BASE}/deezer.php?q=${encodeURIComponent(query)}&limit=10`);
                const data = await response.json();

                if (data.error || !data.data || data.data.length === 0) {
                    item.status = 'notfound';
                    item.deezerResults = [];
                } else {
                    item.deezerResults = data.data;

                    // Check for exact or close match
                    const exactMatch = findBestMatch(item.original, data.data);

                    if (exactMatch.confidence === 'high') {
                        item.status = 'matched';
                        item.selectedTrack = exactMatch.track;
                    } else if (exactMatch.confidence === 'medium') {
                        item.status = 'ambiguous';
                        item.selectedTrack = exactMatch.track; // Pre-select best guess
                    } else {
                        item.status = 'ambiguous';
                    }
                }
            } catch (err) {
                console.error('Search failed:', err);
                item.status = 'notfound';
            }

            renderImportRow(index);
            updateCounts();
        }

        function findBestMatch(original, tracks) {
            const normalizedTitle = normalize(original.title);
            const normalizedArtist = normalize(original.artist);

            for (const track of tracks) {
                const trackTitle = normalize(track.title);
                const trackArtist = normalize(track.artist.name);

                // Exact match
                if (trackTitle === normalizedTitle && trackArtist === normalizedArtist) {
                    return { track, confidence: 'high' };
                }

                // Title contains original (handles "The Wind" vs "The Wind (Greatest Hits Version)")
                if (trackTitle.includes(normalizedTitle) && trackArtist === normalizedArtist) {
                    return { track, confidence: 'high' };
                }

                // Original title contains track title (for shortened titles)
                if (normalizedTitle.includes(trackTitle) && trackArtist === normalizedArtist) {
                    return { track, confidence: 'medium' };
                }
            }

            // Check first result if artist matches
            if (tracks.length > 0) {
                const firstTrack = tracks[0];
                if (normalize(firstTrack.artist.name) === normalizedArtist) {
                    return { track: firstTrack, confidence: 'medium' };
                }
            }

            return { track: tracks[0] || null, confidence: 'low' };
        }

        function normalize(str) {
            return str.toLowerCase()
                .replace(/[^\w\s]/g, '') // Remove punctuation
                .replace(/\s+/g, ' ')    // Normalize whitespace
                .trim();
        }

        function renderImportList() {
            const container = document.getElementById('importList');
            let html = '';

            importData.forEach((item, index) => {
                if (shouldShow(item)) {
                    html += renderImportRowHtml(item, index);
                }
            });

            container.innerHTML = html || '<p class="text-muted">No items to show</p>';
        }

        function shouldShow(item) {
            if (currentFilter === 'all') return true;
            if (currentFilter === 'ambiguous') return item.status === 'ambiguous';
            if (currentFilter === 'notfound') return item.status === 'notfound';
            if (currentFilter === 'matched') return item.status === 'matched';
            return true;
        }

        function renderImportRow(index) {
            const item = importData[index];
            const row = document.getElementById(`import-row-${index}`);
            const shouldDisplay = shouldShow(item);

            if (row && shouldDisplay) {
                // Row exists and should be shown - update it
                row.outerHTML = renderImportRowHtml(item, index);
            } else if (!shouldDisplay && row) {
                // Row exists but shouldn't be shown - remove it
                row.remove();
            } else if (shouldDisplay && !row) {
                // Row doesn't exist but should be shown - add it in the right position
                const container = document.getElementById('importList');
                const newRowHtml = renderImportRowHtml(item, index);

                // Find the right position to insert (maintain order by index)
                const existingRows = container.querySelectorAll('.import-row');
                let inserted = false;

                for (const existingRow of existingRows) {
                    const existingIndex = parseInt(existingRow.id.replace('import-row-', ''));
                    if (existingIndex > index) {
                        existingRow.insertAdjacentHTML('beforebegin', newRowHtml);
                        inserted = true;
                        break;
                    }
                }

                if (!inserted) {
                    // Add at end (or container is empty)
                    if (container.querySelector('p.text-muted')) {
                        container.innerHTML = newRowHtml;
                    } else {
                        container.insertAdjacentHTML('beforeend', newRowHtml);
                    }
                }
            }
        }

        function renderImportRowHtml(item, index) {
            let statusClass = item.status;
            let statusIcon = '';
            let statusText = '';

            switch (item.status) {
                case 'pending':
                    statusIcon = '<i class="bi bi-hourglass text-muted"></i>';
                    statusText = 'Pending';
                    break;
                case 'searching':
                    statusIcon = '<span class="spinner-border spinner-border-sm"></span>';
                    statusText = 'Searching...';
                    break;
                case 'matched':
                    statusIcon = '<i class="bi bi-check-circle-fill text-success"></i>';
                    statusText = 'Matched';
                    break;
                case 'ambiguous':
                    statusIcon = '<i class="bi bi-question-circle-fill text-warning"></i>';
                    statusText = 'Review needed';
                    break;
                case 'notfound':
                    statusIcon = '<i class="bi bi-x-circle-fill text-danger"></i>';
                    statusText = 'Not found';
                    break;
                case 'skipped':
                    statusIcon = '<i class="bi bi-dash-circle text-secondary"></i>';
                    statusText = 'Skipped';
                    break;
                case 'manual':
                    statusIcon = '<i class="bi bi-pencil-square text-info"></i>';
                    statusText = 'Manual entry';
                    break;
            }

            let optionsHtml = '';
            if ((item.status === 'ambiguous' || item.status === 'matched') && item.deezerResults.length > 0) {
                optionsHtml = `
                    <div class="mt-2">
                        <small class="text-muted">Select the correct match:</small>
                        <div class="deezer-options mt-1">
                            ${item.deezerResults.slice(0, 5).map((track, i) => `
                                <div class="deezer-option d-flex align-items-center ${item.selectedTrack?.id === track.id ? 'selected' : ''}"
                                     onclick="selectTrack(${index}, ${i})">
                                    <img src="${track.album.cover_small}" class="deezer-thumb rounded me-2">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small">${escapeHtml(track.title)}</div>
                                        <div class="text-muted small">${escapeHtml(track.artist.name)} - ${escapeHtml(track.album.title)}</div>
                                    </div>
                                    ${item.selectedTrack?.id === track.id ? '<i class="bi bi-check-lg text-primary"></i>' : ''}
                                </div>
                            `).join('')}
                            <div class="deezer-option d-flex align-items-center"
                                 onclick="openManualEntry(${index})">
                                <i class="bi bi-pencil me-2"></i>
                                <span class="small">Enter details manually</span>
                            </div>
                            <div class="deezer-option d-flex align-items-center ${item.status === 'skipped' ? 'selected' : ''}"
                                 onclick="skipSong(${index})">
                                <i class="bi bi-skip-forward me-2"></i>
                                <span class="small">Skip this song</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Or try a different search:</small>
                            <div class="input-group input-group-sm mt-1">
                                <input type="text" class="form-control" id="retry-query-${index}"
                                       value="${escapeHtml(item.original.title + ' ' + item.original.artist)}"
                                       placeholder="Try a different search...">
                                <button class="btn btn-outline-secondary" onclick="retrySearch(${index})">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            } else if (item.status === 'notfound') {
                optionsHtml = `
                    <div class="mt-2">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="retry-query-${index}"
                                   value="${escapeHtml(item.original.title + ' ' + item.original.artist)}"
                                   placeholder="Try a different search...">
                            <button class="btn btn-outline-secondary" onclick="retrySearch(${index})">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div class="mt-1">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openManualEntry(${index})">
                                <i class="bi bi-pencil"></i> Enter Manually
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="skipSong(${index})">
                                <i class="bi bi-skip-forward"></i> Skip
                            </button>
                        </div>
                    </div>
                `;
            } else if (item.status === 'manual') {
                optionsHtml = `
                    <div class="mt-2">
                        <div class="alert alert-info py-2 mb-0 small">
                            <i class="bi bi-pencil-square"></i>
                            <strong>${escapeHtml(item.manualData.title)}</strong> by ${escapeHtml(item.manualData.artist)}
                            <br><small class="text-muted">${escapeHtml(item.manualData.album)} (${item.manualData.year})</small>
                            <button class="btn btn-sm btn-link p-0 ms-2" onclick="openManualEntry(${index})">Edit</button>
                        </div>
                    </div>
                `;
            }

            return `
                <div class="import-row ${statusClass}" id="import-row-${index}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>${escapeHtml(item.original.title)}</strong>
                            <span class="text-muted">by ${escapeHtml(item.original.artist)}</span>
                        </div>
                        <div class="text-end">
                            ${statusIcon} <span class="small">${statusText}</span>
                        </div>
                    </div>
                    ${optionsHtml}
                </div>
            `;
        }

        function selectTrack(index, trackIndex) {
            const item = importData[index];
            item.selectedTrack = item.deezerResults[trackIndex];
            item.status = 'matched';
            renderImportRow(index);
            updateCounts();
        }

        function skipSong(index) {
            importData[index].status = 'skipped';
            importData[index].selectedTrack = null;
            renderImportRow(index);
            updateCounts();
        }

        function openManualEntry(index) {
            if (!manualEntryModal) {
                manualEntryModal = new bootstrap.Modal(document.getElementById('manualEntryModal'));
            }

            const item = importData[index];
            document.getElementById('manualEntryIndex').value = index;

            // Pre-fill with existing data if available, otherwise use original
            if (item.manualData) {
                document.getElementById('manualTitle').value = item.manualData.title;
                document.getElementById('manualArtist').value = item.manualData.artist;
                document.getElementById('manualAlbum').value = item.manualData.album;
                document.getElementById('manualYear').value = item.manualData.year;
                document.getElementById('manualDuration').value = item.manualData.duration || 0;
                // Show existing album art if present
                if (item.manualData.albumArtBase64) {
                    document.getElementById('manualAlbumArtImg').src = 'data:image/jpeg;base64,' + item.manualData.albumArtBase64;
                    document.getElementById('manualAlbumArtPreview').style.display = 'block';
                } else {
                    clearManualAlbumArt();
                }
            } else {
                document.getElementById('manualTitle').value = item.original.title;
                document.getElementById('manualArtist').value = item.original.artist;
                document.getElementById('manualAlbum').value = '';
                document.getElementById('manualYear').value = new Date().getFullYear();
                document.getElementById('manualDuration').value = 0;
                clearManualAlbumArt();
            }

            // Clear file input (can't set value programmatically)
            document.getElementById('manualAlbumArt').value = '';

            manualEntryModal.show();
        }

        function clearManualAlbumArt() {
            document.getElementById('manualAlbumArt').value = '';
            document.getElementById('manualAlbumArtPreview').style.display = 'none';
            document.getElementById('manualAlbumArtImg').src = '';
        }

        // Set up album art preview when file is selected
        document.getElementById('manualAlbumArt').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) {
                clearManualAlbumArt();
                return;
            }

            // Check file size (64KB max for database BLOB)
            if (file.size > 65535) {
                alert('Image is too large. Maximum size is 64KB. Please resize the image or use a more compressed format.');
                clearManualAlbumArt();
                return;
            }

            // Preview the image
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('manualAlbumArtImg').src = e.target.result;
                document.getElementById('manualAlbumArtPreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        });

        async function saveManualEntry() {
            const form = document.getElementById('manualEntryForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const index = parseInt(document.getElementById('manualEntryIndex').value);
            const item = importData[index];

            // Get album art as base64 if file was selected
            let albumArtBase64 = null;
            const fileInput = document.getElementById('manualAlbumArt');
            if (fileInput.files.length > 0) {
                albumArtBase64 = await readFileAsBase64(fileInput.files[0]);
            } else if (item.manualData?.albumArtBase64) {
                // Keep existing album art if no new file selected
                albumArtBase64 = item.manualData.albumArtBase64;
            }

            item.manualData = {
                title: document.getElementById('manualTitle').value.trim(),
                artist: document.getElementById('manualArtist').value.trim(),
                album: document.getElementById('manualAlbum').value.trim(),
                year: parseInt(document.getElementById('manualYear').value),
                duration: parseInt(document.getElementById('manualDuration').value) || 0,
                albumArtBase64: albumArtBase64
            };
            item.status = 'manual';
            item.selectedTrack = null;

            manualEntryModal.hide();
            renderImportRow(index);
            updateCounts();
        }

        function readFileAsBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    // Extract just the base64 part (remove data:image/...;base64, prefix)
                    const base64 = reader.result.split(',')[1];
                    resolve(base64);
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        async function retrySearch(index) {
            const query = document.getElementById(`retry-query-${index}`).value;
            if (!query) return;

            const item = importData[index];
            item.status = 'searching';
            renderImportRow(index);

            try {
                const response = await fetch(`${API_BASE}/deezer.php?q=${encodeURIComponent(query)}&limit=10`);
                const data = await response.json();

                if (data.error || !data.data || data.data.length === 0) {
                    item.status = 'notfound';
                    item.deezerResults = [];
                } else {
                    item.deezerResults = data.data;
                    item.status = 'ambiguous';
                }
            } catch (err) {
                item.status = 'notfound';
            }

            renderImportRow(index);
            updateCounts();
        }

        function filterView(filter) {
            currentFilter = filter;
            renderImportList();
        }

        function updateCounts() {
            const counts = {
                matched: 0,
                ambiguous: 0,
                notfound: 0,
                skipped: 0,
                manual: 0
            };

            importData.forEach(item => {
                if (counts[item.status] !== undefined) {
                    counts[item.status]++;
                }
            });

            document.getElementById('matchedCount').textContent = counts.matched + counts.manual;
            document.getElementById('ambiguousCount').textContent = counts.ambiguous;
            document.getElementById('notFoundCount').textContent = counts.notfound;
            document.getElementById('skippedCount').textContent = counts.skipped;
            document.getElementById('readyCount').textContent = counts.matched + counts.manual;

            const total = importData.length;
            // Manual entries show in the "matched" (green) portion of the progress bar
            document.getElementById('progressMatched').style.width = ((counts.matched + counts.manual) / total * 100) + '%';
            document.getElementById('progressAmbiguous').style.width = (counts.ambiguous / total * 100) + '%';
            document.getElementById('progressNotFound').style.width = (counts.notfound / total * 100) + '%';
            document.getElementById('progressSkipped').style.width = (counts.skipped / total * 100) + '%';
        }

        async function importSongs() {
            // Include both Deezer-matched and manual entries
            const songsToImport = importData.filter(item =>
                (item.status === 'matched' && item.selectedTrack) ||
                (item.status === 'manual' && item.manualData)
            );

            if (songsToImport.length === 0) {
                alert('No songs ready to import');
                return;
            }

            const skipExisting = document.getElementById('skipExisting').checked;
            const progressDiv = document.getElementById('importProgress');
            const progressBar = document.getElementById('importProgressBar');
            const statusDiv = document.getElementById('importStatus');

            progressDiv.style.display = 'block';

            let imported = 0;
            let skipped = 0;
            let failed = 0;
            const failures = []; // Track failed songs with details

            for (let i = 0; i < songsToImport.length; i++) {
                const item = songsToImport[i];
                const isManual = item.status === 'manual';
                const songTitle = isManual ? item.manualData.title : item.selectedTrack.title;
                const songArtist = isManual ? item.manualData.artist : item.selectedTrack.artist.name;

                statusDiv.textContent = `Importing ${i + 1} of ${songsToImport.length}: ${songTitle}...`;
                progressBar.style.width = ((i + 1) / songsToImport.length * 100) + '%';

                try {
                    let songData;

                    if (isManual) {
                        // Manual entry - no Deezer data
                        songData = {
                            csrf_token: CSRF_TOKEN,
                            action: 'create',
                            title: item.manualData.title,
                            artist: item.manualData.artist,
                            album: item.manualData.album,
                            year: item.manualData.year,
                            duration: item.manualData.duration || 0,
                            deezer_id: null,
                            album_art_url: null,
                            album_art_base64: item.manualData.albumArtBase64 || null,
                            skip_existing: skipExisting
                        };
                    } else {
                        // Deezer track - fetch album details for year
                        const track = item.selectedTrack;
                        const albumResponse = await fetch(`${API_BASE}/deezer.php?album_id=${track.album.id}`);
                        const albumData = await albumResponse.json();
                        const year = albumData.year || new Date().getFullYear();

                        // Use cover_small (56x56) to avoid 64KB limit, or cover_medium (250x250) with compression
                        // cover_small is typically 2-5KB, cover_medium is typically 15-30KB
                        const albumArtUrl = track.album.cover_medium || track.album.cover_small;

                        songData = {
                            csrf_token: CSRF_TOKEN,
                            action: 'create',
                            title: track.title,
                            artist: track.artist.name,
                            album: track.album.title,
                            year: year,
                            duration: track.duration || 0,
                            deezer_id: track.id,
                            album_art_url: albumArtUrl,
                            skip_existing: skipExisting
                        };
                    }

                    const response = await fetch(`${API_BASE}/songs.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(songData)
                    });

                    const result = await response.json();

                    if (result.error) {
                        if (result.error.includes('already exists') || result.error.includes('Duplicate')) {
                            skipped++;
                        } else {
                            console.error('Import error:', result.error);
                            failed++;
                            failures.push({
                                title: songTitle,
                                artist: songArtist,
                                error: result.error
                            });
                        }
                    } else {
                        imported++;
                    }
                } catch (err) {
                    console.error('Import failed:', err);
                    failed++;
                    failures.push({
                        title: songTitle,
                        artist: songArtist,
                        error: err.message || 'Network error'
                    });
                }

                // Deezer rate limit: 50 requests per 5 seconds
                // Only delay for Deezer tracks (manual entries don't make Deezer API calls)
                if (!isManual) {
                    await new Promise(r => setTimeout(r, 120));
                }
            }

            progressBar.classList.remove('progress-bar-animated');

            let failureHtml = '';
            if (failures.length > 0) {
                failureHtml = `
                    <div class="alert alert-warning mt-3">
                        <strong>Failed imports:</strong>
                        <ul class="mb-0 mt-2">
                            ${failures.map(f => `<li><strong>${escapeHtml(f.title)}</strong> by ${escapeHtml(f.artist)}: ${escapeHtml(f.error)}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }

            statusDiv.innerHTML = `
                <div class="alert ${failed > 0 ? 'alert-warning' : 'alert-success'}">
                    Import complete!
                    <strong>${imported}</strong> imported,
                    <strong>${skipped}</strong> skipped (already existed),
                    <strong>${failed}</strong> failed.
                </div>
                ${failureHtml}
                <a href="songs.php" class="btn btn-primary">View Song Library</a>
            `;
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
