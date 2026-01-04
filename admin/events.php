<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/helpers.php';

$adminToken = $GLOBALS['config']['admin']['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Rockband Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .event-card {
            transition: box-shadow 0.2s;
        }
        .event-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .event-active {
            border-left: 4px solid #198754;
        }
        .event-upcoming {
            border-left: 4px solid #0d6efd;
        }
        .event-past {
            border-left: 4px solid #6c757d;
            opacity: 0.7;
        }
        .qr-preview {
            max-width: 200px;
            max-height: 200px;
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
                        <a class="nav-link active" href="events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">Settings</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Events</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal" onclick="openAddModal()">
                <i class="bi bi-plus-lg"></i> Create Event
            </button>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <select class="form-select" id="filterStatus">
                    <option value="all">All Events</option>
                    <option value="active">Active Now</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="past">Past</option>
                </select>
            </div>
        </div>

        <div class="row" id="eventsGrid">
            <div class="col-12 text-center py-5">
                <div class="spinner-border"></div>
                <p class="mt-2">Loading events...</p>
            </div>
        </div>
    </div>

    <!-- Add/Edit Event Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Create Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="eventForm">
                        <input type="hidden" id="eventId" name="event_id">
                        <div class="mb-3">
                            <label for="eventName" class="form-label">Event Name *</label>
                            <input type="text" class="form-control" id="eventName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="eventLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="eventLocation" name="location" placeholder="e.g. Room 204, Student Center">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="startTime" class="form-label">Start Time *</label>
                                <input type="datetime-local" class="form-control" id="startTime" name="start_time" required>
                            </div>
                            <div class="col-md-6">
                                <label for="endTime" class="form-label">End Time *</label>
                                <input type="datetime-local" class="form-control" id="endTime" name="end_time" required>
                            </div>
                            <div class="form-text">Enter times as they appear at the event venue (no timezone conversion)</div>
                        </div>
                        <div class="mb-3">
                            <label for="numEntries" class="form-label">Number of Performance Slots *</label>
                            <input type="number" class="form-control" id="numEntries" name="num_entries" min="1" max="255" value="10" required>
                            <div class="form-text">How many performers can sign up for this event</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveEvent()">Save Event</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Event Modal -->
    <div class="modal fade" id="viewEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewEventTitle">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <dl class="row">
                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8"><span id="viewEventStatus" class="badge"></span></dd>
                                <dt class="col-sm-4">Location</dt>
                                <dd class="col-sm-8" id="viewEventLocation"><span class="text-muted">Not specified</span></dd>
                                <dt class="col-sm-4">Start Time</dt>
                                <dd class="col-sm-8" id="viewEventStart"></dd>
                                <dt class="col-sm-4">End Time</dt>
                                <dd class="col-sm-8" id="viewEventEnd"></dd>
                                <dt class="col-sm-4">Performance Slots</dt>
                                <dd class="col-sm-8" id="viewEventSlots"></dd>
                                <dt class="col-sm-4">Event URL</dt>
                                <dd class="col-sm-8">
                                    <code id="viewEventUrl" class="user-select-all"></code>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyEventUrl()">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-4 text-center">
                            <div id="qrCodeContainer">
                                <p class="text-muted">QR Code</p>
                                <img id="viewEventQr" class="qr-preview img-fluid border" alt="QR Code" style="display:none;">
                                <p id="noQrMessage" class="text-muted small">QR code unavailable</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="manageEntriesLink" class="btn btn-info">
                        <i class="bi bi-list-ol"></i> Manage Entries
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                    <p>Are you sure you want to delete "<span id="deleteEventName"></span>"?</p>
                    <p class="text-danger"><strong>Warning:</strong> This will also delete all entries/sign-ups for this event.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Event</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ADMIN_TOKEN = <?= json_encode($adminToken) ?>;
        const API_BASE = '../api';
        const SITE_BASE = window.location.origin + window.location.pathname.replace(/\/admin\/.*$/, '');

        let events = [];
        let deleteEventId = null;
        let currentViewEventId = null;
        let eventModal, viewEventModal, deleteModal;

        document.addEventListener('DOMContentLoaded', function() {
            eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
            viewEventModal = new bootstrap.Modal(document.getElementById('viewEventModal'));
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            loadEvents();

            document.getElementById('filterStatus').addEventListener('change', renderEvents);

            // Check if we should auto-open the add modal
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'add') {
                openAddModal();
                eventModal.show();
                // Clean up URL
                window.history.replaceState({}, '', window.location.pathname);
            }
        });

        async function loadEvents() {
            try {
                const response = await fetch(`${API_BASE}/events.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_token: ADMIN_TOKEN, action: 'list' })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                events = data.events || [];
                renderEvents();
            } catch (err) {
                console.error('Failed to load events:', err);
                document.getElementById('eventsGrid').innerHTML =
                    '<div class="col-12 text-center text-danger">Failed to load events</div>';
            }
        }

        function getEventStatus(event) {
            const now = new Date();
            const start = new Date(event.start_time);
            const end = new Date(event.end_time);

            if (now >= start && now <= end) return 'active';
            if (now < start) return 'upcoming';
            return 'past';
        }

        function renderEvents() {
            const filter = document.getElementById('filterStatus').value;
            const grid = document.getElementById('eventsGrid');

            let filtered = events;
            if (filter !== 'all') {
                filtered = events.filter(e => getEventStatus(e) === filter);
            }

            // Sort: active first, then upcoming by start time, then past by end time desc
            filtered.sort((a, b) => {
                const statusOrder = { active: 0, upcoming: 1, past: 2 };
                const statusA = getEventStatus(a);
                const statusB = getEventStatus(b);
                if (statusOrder[statusA] !== statusOrder[statusB]) {
                    return statusOrder[statusA] - statusOrder[statusB];
                }
                if (statusA === 'past') {
                    return new Date(b.end_time) - new Date(a.end_time);
                }
                return new Date(a.start_time) - new Date(b.start_time);
            });

            if (filtered.length === 0) {
                grid.innerHTML = '<div class="col-12 text-center text-muted py-5">No events found</div>';
                return;
            }

            grid.innerHTML = filtered.map(event => {
                const status = getEventStatus(event);
                const statusClass = `event-${status}`;
                const statusBadge = {
                    active: '<span class="badge bg-success">Active</span>',
                    upcoming: '<span class="badge bg-primary">Upcoming</span>',
                    past: '<span class="badge bg-secondary">Past</span>'
                }[status];

                return `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card event-card ${statusClass}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0">${escapeHtml(event.name)}</h5>
                                    ${statusBadge}
                                </div>
                                ${event.location ? `<p class="card-text text-muted small mb-1"><i class="bi bi-geo-alt"></i> ${escapeHtml(event.location)}</p>` : ''}
                                <p class="card-text text-muted small mb-2">
                                    <i class="bi bi-calendar"></i> ${formatDateTime(event.start_time)}<br>
                                    <i class="bi bi-clock"></i> to ${formatDateTime(event.end_time)}
                                </p>
                                <p class="card-text">
                                    <i class="bi bi-people"></i> ${event.num_entries} slots
                                </p>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="entries.php?eventid=${event.event_id}" class="btn btn-sm btn-info">
                                        <i class="bi bi-list-ol"></i> Manage
                                    </a>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewEvent('${event.event_id}')">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editEvent('${event.event_id}')">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteEvent('${event.event_id}', '${escapeHtml(event.name)}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function formatDateTime(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleString(undefined, {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function formatDateTimeLocal(dateStr) {
            const date = new Date(dateStr);
            // Format for datetime-local input
            const pad = n => n.toString().padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
        }

        function openAddModal() {
            document.getElementById('eventModalTitle').textContent = 'Create Event';
            document.getElementById('eventForm').reset();
            document.getElementById('eventId').value = '';

            // Set default times (start now, end in 4 hours)
            const now = new Date();
            const later = new Date(now.getTime() + 4 * 60 * 60 * 1000);
            document.getElementById('startTime').value = formatDateTimeLocal(now.toISOString());
            document.getElementById('endTime').value = formatDateTimeLocal(later.toISOString());
        }

        function editEvent(eventId) {
            const event = events.find(e => e.event_id === eventId);
            if (!event) return;

            document.getElementById('eventModalTitle').textContent = 'Edit Event';
            document.getElementById('eventId').value = event.event_id;
            document.getElementById('eventName').value = event.name;
            document.getElementById('eventLocation').value = event.location || '';
            document.getElementById('startTime').value = formatDateTimeLocal(event.start_time);
            document.getElementById('endTime').value = formatDateTimeLocal(event.end_time);
            document.getElementById('numEntries').value = event.num_entries;

            eventModal.show();
        }

        function viewEvent(eventId) {
            const event = events.find(e => e.event_id === eventId);
            if (!event) return;

            currentViewEventId = eventId;
            const status = getEventStatus(event);

            document.getElementById('viewEventTitle').textContent = event.name;

            const statusEl = document.getElementById('viewEventStatus');
            statusEl.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusEl.className = 'badge bg-' + { active: 'success', upcoming: 'primary', past: 'secondary' }[status];

            document.getElementById('viewEventLocation').innerHTML = event.location
                ? escapeHtml(event.location)
                : '<span class="text-muted">Not specified</span>';

            document.getElementById('viewEventStart').textContent = formatDateTime(event.start_time);
            document.getElementById('viewEventEnd').textContent = formatDateTime(event.end_time);
            document.getElementById('viewEventSlots').textContent = event.num_entries;

            const eventUrl = `${SITE_BASE}/?eventid=${eventId}`;
            document.getElementById('viewEventUrl').textContent = eventUrl;
            document.getElementById('manageEntriesLink').href = `entries.php?eventid=${eventId}`;

            // Show QR if exists
            const qrImg = document.getElementById('viewEventQr');
            const noQrMsg = document.getElementById('noQrMessage');
            if (event.qr_image) {
                qrImg.src = `data:image/png;base64,${event.qr_image}`;
                qrImg.style.display = 'block';
                noQrMsg.style.display = 'none';
            } else {
                qrImg.style.display = 'none';
                noQrMsg.style.display = 'block';
            }

            viewEventModal.show();
        }

        async function saveEvent() {
            const form = document.getElementById('eventForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const eventId = document.getElementById('eventId').value;
            const data = {
                name: document.getElementById('eventName').value,
                location: document.getElementById('eventLocation').value || null,
                start_time: document.getElementById('startTime').value,
                end_time: document.getElementById('endTime').value,
                num_entries: parseInt(document.getElementById('numEntries').value)
            };

            // Validate times
            if (new Date(data.end_time) <= new Date(data.start_time)) {
                alert('End time must be after start time');
                return;
            }

            try {
                const requestBody = {
                    admin_token: ADMIN_TOKEN,
                    action: eventId ? 'update' : 'create',
                    ...data
                };
                if (eventId) {
                    requestBody.event_id = eventId;
                }

                const response = await fetch(`${API_BASE}/events.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestBody)
                });

                const result = await response.json();
                if (result.error) throw new Error(result.error);

                eventModal.hide();
                loadEvents();
            } catch (err) {
                alert('Failed to save event: ' + err.message);
            }
        }

        function deleteEvent(eventId, name) {
            deleteEventId = eventId;
            document.getElementById('deleteEventName').textContent = name;
            deleteModal.show();
        }

        async function confirmDelete() {
            if (!deleteEventId) return;

            try {
                const response = await fetch(`${API_BASE}/events.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_token: ADMIN_TOKEN,
                        action: 'delete',
                        event_id: deleteEventId
                    })
                });
                const result = await response.json();
                if (result.error) throw new Error(result.error);

                deleteModal.hide();
                deleteEventId = null;
                loadEvents();
            } catch (err) {
                alert('Failed to delete event: ' + err.message);
            }
        }

        function copyEventUrl() {
            const url = document.getElementById('viewEventUrl').textContent;
            navigator.clipboard.writeText(url).then(() => {
                // Brief feedback
                const btn = document.querySelector('#viewEventUrl + button');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check"></i>';
                setTimeout(() => btn.innerHTML = originalHtml, 1500);
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
