<?php
/**
 * Rockband Scheduler - Signup Display Page
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
 *
 * Simple signage showing a QR code for users to scan to sign up.
 * Designed for display on TVs/screens at events.
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$db = $GLOBALS['db'];
$eventId = $_GET['eventid'] ?? null;
$error = null;
$event = null;

// Get default theme ID from config
$defaultThemeId = $GLOBALS['config']['theme']['default_theme_id'] ?? null;

// Validate event ID
if (!$eventId) {
    $error = 'No event specified.';
} elseif (!isValidUuid($eventId)) {
    $error = 'Invalid event ID.';
} else {
    try {
        $stmt = $db->prepare('
            SELECT BIN_TO_UUID(e.event_id) as event_id, e.name,
                   TO_BASE64(e.qr_image) as qr_image,
                   e.theme_id,
                   t.primary_color, t.bg_gradient_start, t.bg_gradient_end, t.text_color
            FROM events e
            LEFT JOIN themes t ON e.theme_id = t.theme_id
            WHERE e.event_id = UUID_TO_BIN(?)
        ');
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            $error = 'Event not found.';
        } else {
            // If event has no theme, use the default theme from settings
            if (!$event['theme_id'] && $defaultThemeId) {
                $themeStmt = $db->prepare('
                    SELECT primary_color, bg_gradient_start, bg_gradient_end, text_color
                    FROM themes WHERE theme_id = ?
                ');
                $themeStmt->execute([$defaultThemeId]);
                $defaultTheme = $themeStmt->fetch(PDO::FETCH_ASSOC);
                if ($defaultTheme) {
                    $event['primary_color'] = $defaultTheme['primary_color'];
                    $event['bg_gradient_start'] = $defaultTheme['bg_gradient_start'];
                    $event['bg_gradient_end'] = $defaultTheme['bg_gradient_end'];
                    $event['text_color'] = $defaultTheme['text_color'];
                }
            }
        }
    } catch (PDOException $e) {
        error_log('Event lookup error: ' . $e->getMessage());
        $error = 'Unable to load event.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?= $event ? h($event['name']) : 'Event' ?></title>
    <style>
        :root {
            --primary-color: <?= h($event['primary_color'] ?? '#6f42c1') ?>;
            --bg-gradient-start: <?= h($event['bg_gradient_start'] ?? '#1a1a2e') ?>;
            --bg-gradient-end: <?= h($event['bg_gradient_end'] ?? '#16213e') ?>;
            --text-color: <?= h($event['text_color'] ?? '#ffffff') ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            text-align: center;
            padding: 2vw;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .title {
            font-size: 8vw;
            font-weight: 700;
            margin-bottom: 4vw;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .qr-container {
            background: white;
            padding: 3vw;
            border-radius: 2vw;
            display: inline-block;
            margin-bottom: 4vw;
            box-shadow: 0 0 60px rgba(0, 0, 0, 0.3);
        }

        .qr-container img {
            display: block;
            width: 55vw;
            height: 55vw;
            max-width: 70vh;
            max-height: 70vh;
        }

        .instruction {
            font-size: 5vw;
            opacity: 0.9;
        }

        .error-page {
            text-align: center;
        }

        .error-page h1 {
            font-size: 3vw;
        }

        .qr-error {
            padding: 4vw;
            color: #666;
            font-size: 1.5vw;
        }

        /* Landscape orientation adjustments */
        @media (orientation: landscape) {
            .container {
                display: grid;
                grid-template-columns: auto 1fr;
                grid-template-rows: auto auto;
                align-items: center;
                justify-items: start;
                gap: 2vh 5vw;
                height: 100vh;
                padding: 2vh;
            }

            .qr-container {
                grid-row: 1 / 3;
                padding: 2vh;
                border-radius: 1.5vh;
                margin-bottom: 0;
            }

            .qr-container img {
                width: 70vh;
                height: 70vh;
                max-width: 50vw;
                max-height: 80vh;
            }

            .title {
                font-size: 6vh;
                margin-bottom: 0;
                align-self: end;
            }

            .instruction {
                font-size: 4vh;
                align-self: start;
            }
        }
    </style>
</head>
<body>
<?php if ($error): ?>
    <div class="error-page">
        <h1><?= h($error) ?></h1>
    </div>
<?php else: ?>
    <div class="container">
        <div class="title">Scan to Sign Up</div>
        <div class="qr-container">
            <?php if ($event['qr_image']): ?>
                <img src="data:image/png;base64,<?= $event['qr_image'] ?>" alt="QR Code to sign up">
            <?php else: ?>
                <div class="qr-error">QR code not available</div>
            <?php endif; ?>
        </div>
        <div class="instruction">Point your phone camera at the code</div>
    </div>
<?php endif; ?>
</body>
</html>
