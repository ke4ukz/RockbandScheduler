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

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
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
    <script src="admin-theme.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
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
                    <li class="nav-item">
                        <button class="btn nav-link" onclick="toggleAdminTheme()" title="Toggle theme"><i id="themeToggleIcon" class="bi bi-moon-fill"></i><span class="d-lg-none ms-2">Theme</span></button>
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

            <div class="col-lg-6">
                <div class="card" id="contentFilterCard" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-shield-check"></i> Name Content Filter</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Filter inappropriate performer names during public signup. Powered by Sightengine.</p>

                        <h6 class="mt-3">Profanity Filters</h6>

                        <div class="mb-3">
                            <label for="profanitySexual" class="form-label">Sexual language</label>
                            <select class="form-select" id="profanitySexual">
                                <option value="0">Off</option>
                                <option value="1">Low (block severe only)</option>
                                <option value="2">Medium (block moderate+)</option>
                                <option value="3">High (block all)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="profanityDiscriminatory" class="form-label">Discriminatory language (slurs, hate speech)</label>
                            <select class="form-select" id="profanityDiscriminatory">
                                <option value="0">Off</option>
                                <option value="1">Low (block severe only)</option>
                                <option value="2">Medium (block moderate+)</option>
                                <option value="3">High (block all)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="profanityInsult" class="form-label">Insults</label>
                            <select class="form-select" id="profanityInsult">
                                <option value="0">Off</option>
                                <option value="1">Low (block severe only)</option>
                                <option value="2">Medium (block moderate+)</option>
                                <option value="3">High (block all)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="profanityInappropriate" class="form-label">Other inappropriate language</label>
                            <select class="form-select" id="profanityInappropriate">
                                <option value="0">Off</option>
                                <option value="1">Low (block severe only)</option>
                                <option value="2">Medium (block moderate+)</option>
                                <option value="3">High (block all)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="profanityGrawlix" class="form-label">Symbol substitution (@#$%!)</label>
                            <select class="form-select" id="profanityGrawlix">
                                <option value="0">Off</option>
                                <option value="1">Low (block severe only)</option>
                                <option value="2">Medium (block moderate+)</option>
                                <option value="3">High (block all)</option>
                            </select>
                        </div>

                        <h6 class="mt-4">Other Content Blocks</h6>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="blockExtremism">
                            <label class="form-check-label" for="blockExtremism">
                                Extremism content
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="blockViolence">
                            <label class="form-check-label" for="blockViolence">
                                Violence / self-harm
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="blockDrugs">
                            <label class="form-check-label" for="blockDrugs">
                                Drugs / medicines
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card" id="contentFilterDisabled" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-shield-check"></i> Name Content Filter</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Content filtering is not available. To enable it, add Sightengine API credentials to your config file:</p>
                        <pre class="bg-body-secondary p-2 rounded"><code>[sightengine]
api_user = "your_api_user"
api_secret = "your_api_secret"</code></pre>
                        <p class="small text-muted mb-0">Get free API credentials at <a href="https://sightengine.com" target="_blank">sightengine.com</a></p>
                    </div>
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

                // Content filter settings
                const cf = settings.content_filter || {};
                if (cf.available) {
                    document.getElementById('contentFilterCard').style.display = 'block';
                    document.getElementById('contentFilterDisabled').style.display = 'none';

                    // Set filter values (all profanity filters use levels)
                    document.getElementById('profanitySexual').value = cf.profanity_sexual ?? 0;
                    document.getElementById('profanityDiscriminatory').value = cf.profanity_discriminatory ?? 0;
                    document.getElementById('profanityInsult').value = cf.profanity_insult ?? 0;
                    document.getElementById('profanityInappropriate').value = cf.profanity_inappropriate ?? 0;
                    document.getElementById('profanityGrawlix').value = cf.profanity_grawlix ?? 0;
                    // Other content blocks are toggles
                    document.getElementById('blockExtremism').checked = cf.block_extremism ?? false;
                    document.getElementById('blockViolence').checked = cf.block_violence ?? false;
                    document.getElementById('blockDrugs').checked = cf.block_drugs ?? false;
                } else {
                    document.getElementById('contentFilterCard').style.display = 'none';
                    document.getElementById('contentFilterDisabled').style.display = 'block';
                }
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

            // Build settings object
            const settingsData = {
                event: {
                    default_duration_hours: defaultDuration
                },
                theme: {
                    default_theme_id: defaultThemeId
                }
            };

            // Include content filter settings if available
            if (document.getElementById('contentFilterCard').style.display !== 'none') {
                settingsData.content_filter = {
                    // All profanity filters use levels (0-3)
                    profanity_sexual: parseInt(document.getElementById('profanitySexual').value) || 0,
                    profanity_discriminatory: parseInt(document.getElementById('profanityDiscriminatory').value) || 0,
                    profanity_insult: parseInt(document.getElementById('profanityInsult').value) || 0,
                    profanity_inappropriate: parseInt(document.getElementById('profanityInappropriate').value) || 0,
                    profanity_grawlix: parseInt(document.getElementById('profanityGrawlix').value) || 0,
                    // Other content blocks are toggles
                    block_extremism: document.getElementById('blockExtremism').checked,
                    block_violence: document.getElementById('blockViolence').checked,
                    block_drugs: document.getElementById('blockDrugs').checked
                };
            }

            try {
                const response = await fetch(`${API_BASE}/settings.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: CSRF_TOKEN,
                        action: 'update',
                        settings: settingsData
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
