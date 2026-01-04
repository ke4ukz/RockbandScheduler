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
                        <a class="nav-link" href="songs.php">Songs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">Settings</a>
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
                        <h5 class="mb-0"><i class="bi bi-person-plus"></i> User Signup Requirements</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Control what information users must provide when signing up for a slot.</p>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="requireName" checked>
                            <label class="form-check-label" for="requireName">
                                Require performer name
                            </label>
                            <div class="form-text">If unchecked, users can sign up with just a song selection.</div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="requireSong" checked>
                            <label class="form-check-label" for="requireSong">
                                Require song selection
                            </label>
                            <div class="form-text">If unchecked, users can sign up with just their name.</div>
                        </div>

                        <div class="alert alert-info small mb-3">
                            <i class="bi bi-info-circle"></i>
                            At least one option must be required. Users must provide either a name, a song, or both.
                        </div>

                        <button class="btn btn-primary" id="saveSettings" onclick="saveSettings()">
                            <i class="bi bi-check-lg"></i> Save Settings
                        </button>
                        <span id="saveStatus" class="ms-2"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ADMIN_TOKEN = <?= json_encode($adminToken) ?>;
        const API_BASE = '../api';

        document.addEventListener('DOMContentLoaded', loadSettings);

        async function loadSettings() {
            try {
                const response = await fetch(`${API_BASE}/settings.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_token: ADMIN_TOKEN, action: 'get' })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                const settings = data.settings;
                document.getElementById('requireName').checked = settings.signup?.require_name ?? true;
                document.getElementById('requireSong').checked = settings.signup?.require_song ?? true;
            } catch (err) {
                console.error('Failed to load settings:', err);
                showStatus('Failed to load settings', 'danger');
            }
        }

        async function saveSettings() {
            const requireName = document.getElementById('requireName').checked;
            const requireSong = document.getElementById('requireSong').checked;

            // At least one must be required
            if (!requireName && !requireSong) {
                showStatus('At least one requirement must be enabled', 'warning');
                return;
            }

            const btn = document.getElementById('saveSettings');
            btn.disabled = true;

            try {
                const response = await fetch(`${API_BASE}/settings.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_token: ADMIN_TOKEN,
                        action: 'update',
                        settings: {
                            signup: {
                                require_name: requireName,
                                require_song: requireSong
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
