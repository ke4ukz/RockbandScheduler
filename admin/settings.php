<?php
/**
 * Rockband Scheduler - Admin Settings
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
    <title>Settings - Rockband Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
                        <a class="nav-link" href="default.php" title="Dashboard"><i class="bi bi-house-door"></i><span class="d-lg-none ms-2">Dashboard</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="songs.php" title="Songs"><i class="bi bi-music-note-list"></i><span class="d-lg-none ms-2">Songs</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php" title="Events"><i class="bi bi-calendar-event"></i><span class="d-lg-none ms-2">Events</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php" title="Settings"><i class="bi bi-gear"></i><span class="d-lg-none ms-2">Settings</span></a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="help.php" title="Help"><i class="bi bi-question-circle"></i><span class="d-lg-none ms-2">Help</span></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Settings</h1>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Event Defaults</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Default settings for new events.</p>

                        <div class="mb-3">
                            <label for="defaultDuration" class="form-label">Default Event Duration</label>
                            <div class="input-group" style="max-width: 200px;">
                                <input type="number" class="form-control" id="defaultDuration" min="1" max="24" value="4">
                                <span class="input-group-text">hours</span>
                            </div>
                            <div class="form-text">When creating a new event, the end time will default to this many hours after the start time.</div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-palette"></i> Default Theme</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Select the default color theme for new events.</p>

                        <div class="mb-3">
                            <label for="defaultTheme" class="form-label">Default Theme</label>
                            <select class="form-select" id="defaultTheme">
                                <option value="">Loading themes...</option>
                            </select>
                        </div>

                        <div id="themePreview" class="p-3 rounded mb-3" style="display: none;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle" id="previewAccent" style="width: 24px; height: 24px;"></div>
                                <span class="small" id="previewText">Sample Text</span>
                                <button type="button" class="btn btn-sm ms-auto" id="previewButton">Sign Up</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary" id="saveSettings" onclick="saveSettings()">
                        <i class="bi bi-check-lg"></i> Save Settings
                    </button>
                    <span id="saveStatus" class="ms-2"></span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
        const API_BASE = '../api';

        let themes = [];

        document.addEventListener('DOMContentLoaded', async () => {
            await loadThemes();
            await loadSettings();
            document.getElementById('defaultTheme').addEventListener('change', updateThemePreview);
        });

        async function loadThemes() {
            try {
                const response = await fetch(`${API_BASE}/themes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: CSRF_TOKEN, action: 'list' })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                themes = data.themes || [];
                populateThemeDropdown();
            } catch (err) {
                console.error('Failed to load themes:', err);
            }
        }

        function populateThemeDropdown() {
            const select = document.getElementById('defaultTheme');
            select.innerHTML = '';
            themes.forEach(theme => {
                const opt = document.createElement('option');
                opt.value = theme.theme_id;
                opt.textContent = theme.name;
                select.appendChild(opt);
            });
        }

        function updateThemePreview() {
            const select = document.getElementById('defaultTheme');
            const preview = document.getElementById('themePreview');
            const accent = document.getElementById('previewAccent');
            const button = document.getElementById('previewButton');
            const text = document.getElementById('previewText');

            const themeId = select.value;
            const theme = themes.find(t => t.theme_id == themeId);

            if (theme) {
                const textColor = theme.text_color || '#ffffff';
                preview.style.display = 'block';
                preview.style.background = `linear-gradient(135deg, ${theme.bg_gradient_start} 0%, ${theme.bg_gradient_end} 100%)`;
                accent.style.backgroundColor = theme.primary_color;
                button.style.backgroundColor = theme.primary_color;
                button.style.borderColor = theme.primary_color;
                button.style.color = '#fff';
                text.style.color = textColor;
            } else {
                preview.style.display = 'none';
            }
        }

        async function loadSettings() {
            try {
                const response = await fetch(`${API_BASE}/settings.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: CSRF_TOKEN, action: 'get' })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                const settings = data.settings;

                // Set default duration
                document.getElementById('defaultDuration').value = settings.event?.default_duration_hours ?? 4;

                // Set default theme
                const defaultThemeId = settings.theme?.default_theme_id;
                if (defaultThemeId) {
                    document.getElementById('defaultTheme').value = defaultThemeId;
                } else if (themes.length > 0) {
                    // Default to first theme if none set
                    document.getElementById('defaultTheme').value = themes[0].theme_id;
                }
                updateThemePreview();
            } catch (err) {
                console.error('Failed to load settings:', err);
                showStatus('Failed to load settings', 'danger');
            }
        }

        async function saveSettings() {
            const defaultDuration = parseInt(document.getElementById('defaultDuration').value) || 4;
            const defaultThemeId = document.getElementById('defaultTheme').value;

            // Validate duration
            if (defaultDuration < 1 || defaultDuration > 24) {
                showStatus('Duration must be between 1 and 24 hours', 'warning');
                return;
            }

            const btn = document.getElementById('saveSettings');
            btn.disabled = true;

            try {
                const response = await fetch(`${API_BASE}/settings.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: CSRF_TOKEN,
                        action: 'update',
                        settings: {
                            event: {
                                default_duration_hours: defaultDuration
                            },
                            theme: {
                                default_theme_id: defaultThemeId
                            }
                        }
                    })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                showStatus('Settings saved!', 'success');
            } catch (err) {
                console.error('Failed to save settings:', err);
                showStatus('Failed to save: ' + err.message, 'danger');
            } finally {
                btn.disabled = false;
            }
        }

        function showStatus(message, type) {
            const status = document.getElementById('saveStatus');
            status.innerHTML = `<span class="text-${type}">${message}</span>`;
            setTimeout(() => status.innerHTML = '', 3000);
        }
    </script>
</body>
</html>
