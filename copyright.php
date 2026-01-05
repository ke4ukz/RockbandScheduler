<?php
/**
 * Rockband Scheduler - Public Copyright & License Page
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

require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copyright & License - Rockband Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #ffffff;
        }

        .container {
            max-width: 800px;
            padding: 2rem 1rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #ffffff;
        }

        .card-header {
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .card-body {
            color: #ffffff;
        }

        .card-body p, .card-body li {
            color: rgba(255, 255, 255, 0.9);
        }

        a {
            color: #a78bfa;
        }

        a:hover {
            color: #c4b5fd;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            margin-bottom: 1.5rem;
        }

        .back-link:hover {
            color: #ffffff;
        }

        h1 {
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
        }

        h2 {
            font-size: 1.25rem;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .footer {
            text-align: center;
            padding: 2rem 0;
            opacity: 0.6;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-link">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        <h1><i class="bi bi-c-circle"></i> Copyright & License</h1>

        <div class="card mb-4">
            <div class="card-header">
                <strong>Software License</strong>
            </div>
            <div class="card-body">
                <p><strong>Rockband Scheduler</strong><br>
                Copyright &copy; 2026 Jonathan Dean</p>

                <p>This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.</p>

                <p>This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.</p>

                <p>You should have received a copy of the GNU General Public License along with this program. If not, see <a href="https://www.gnu.org/licenses/" target="_blank" rel="noopener noreferrer">https://www.gnu.org/licenses/</a>.</p>

                <p class="mb-0"><i class="bi bi-github"></i> Source code available at <a href="https://github.com/ke4ukz/RockbandScheduler" target="_blank" rel="noopener noreferrer">github.com/ke4ukz/RockbandScheduler</a></p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <strong><i class="bi bi-trademark"></i> Trademark Notice</strong>
            </div>
            <div class="card-body">
                <p>Rock Band&trade; is a registered trademark of Harmonix Music Systems, Inc. This application is not affiliated with, endorsed by, or sponsored by Harmonix Music Systems, Inc. or any of its subsidiaries or affiliates.</p>
                <p class="mb-0">This is an independent, fan-made tool created to help organize Rock Band performance events. All product names, logos, and brands are property of their respective owners.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <strong><i class="bi bi-plug"></i> Third-Party Services</strong>
            </div>
            <div class="card-body">
                <h2><i class="bi bi-music-note-beamed"></i> Deezer</h2>
                <p>This application uses the Deezer API to provide song metadata and audio previews. As of January 2026, Deezer allows 30-second audio previews for non-authenticated users, making this feature free to use. Please see:</p>
                <ul>
                    <li><a href="https://developers.deezer.com/guidelines" target="_blank" rel="noopener noreferrer">Deezer API Guidelines</a></li>
                    <li><a href="https://deezerbrand.com/document/37#/-/overview" target="_blank" rel="noopener noreferrer">Deezer Brand Guidelines</a></li>
                </ul>

                <h2><i class="bi bi-qr-code"></i> QR Code Generation</h2>
                <p>QR codes are generated using the <a href="https://goqr.me/api/" target="_blank" rel="noopener noreferrer">QR Server API</a>, provided by <a href="https://goqr.me/" target="_blank" rel="noopener noreferrer">goQR.me</a>.</p>

                <h2><i class="bi bi-bootstrap"></i> Frontend Libraries</h2>
                <ul class="mb-0">
                    <li><a href="https://getbootstrap.com/" target="_blank" rel="noopener noreferrer">Bootstrap 5.3</a> - CSS framework</li>
                    <li><a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener noreferrer">Bootstrap Icons</a> - Icon library</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2026 Jonathan Dean &bull; Licensed under the <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank" rel="noopener noreferrer">GNU GPL v3</a></p>
        </div>
    </div>
</body>
</html>
