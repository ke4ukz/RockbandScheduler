<?php
/**
 * Signup Display Page
 *
 * Simple signage showing a QR code for users to scan to sign up.
 * Designed for display on TVs/screens at events.
 */
require_once 'config.php';
require_once 'db.php';
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

// Build the signup URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$signupUrl = $protocol . '://' . $host . $basePath . '/default.php?eventid=' . urlencode($eventId);
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
        }

        .title {
            font-size: 5vw;
            font-weight: 700;
            margin-bottom: 3vw;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .qr-container {
            background: white;
            padding: 2vw;
            border-radius: 2vw;
            display: inline-block;
            margin-bottom: 3vw;
            box-shadow: 0 0 60px rgba(0, 0, 0, 0.3);
        }

        .qr-container canvas {
            display: block;
            width: 35vw !important;
            height: 35vw !important;
            max-width: 400px;
            max-height: 400px;
        }

        .instruction {
            font-size: 3vw;
            opacity: 0.9;
        }

        .error-page {
            text-align: center;
        }

        .error-page h1 {
            font-size: 3vw;
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
            <canvas id="qrcode"></canvas>
        </div>
        <div class="instruction">Point your phone camera at the code</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        const signupUrl = <?= json_encode($signupUrl) ?>;

        QRCode.toCanvas(document.getElementById('qrcode'), signupUrl, {
            width: 400,
            margin: 0,
            color: {
                dark: '#000000',
                light: '#ffffff'
            }
        }, function(error) {
            if (error) console.error(error);
        });
    </script>
<?php endif; ?>
</body>
</html>
