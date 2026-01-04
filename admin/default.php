<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = $GLOBALS['db'];

// Get quick stats
$songCount = 0;
$eventCount = 0;
$activeEventCount = 0;

if ($db) {
    try {
        $songCount = $db->query('SELECT COUNT(*) FROM songs')->fetchColumn();
        $eventCount = $db->query('SELECT COUNT(*) FROM events')->fetchColumn();
        // Use PHP's current time to match JavaScript's Date() behavior in the events page
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare('SELECT COUNT(*) FROM events WHERE ? BETWEEN start_time AND end_time');
        $stmt->execute([$now]);
        $activeEventCount = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Dashboard stats error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rockband Admin</title>
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Dashboard</h1>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card text-bg-primary">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-music-note-list"></i> Song Library</h5>
                        <p class="card-text display-4"><?= $songCount ?></p>
                        <a href="songs.php" class="btn btn-light">Manage Songs</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card text-bg-info">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-calendar-event"></i> Total Events</h5>
                        <p class="card-text display-4"><?= $eventCount ?></p>
                        <a href="events.php" class="btn btn-light">Manage Events</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card text-bg-success">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-broadcast"></i> Active Now</h5>
                        <p class="card-text display-4"><?= $activeEventCount ?></p>
                        <a href="events.php" class="btn btn-light">View Events</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="songs.php" class="btn btn-outline-primary">
                                <i class="bi bi-plus-lg"></i> Add Song
                            </a>
                            <a href="events.php" class="btn btn-outline-primary">
                                <i class="bi bi-plus-lg"></i> Create Event
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
