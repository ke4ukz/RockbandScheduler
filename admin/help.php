<?php
/**
 * Rockband Scheduler - Admin Help
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

$adminToken = $GLOBALS['config']['admin']['token'] ?? '';
$siteBaseUrl = $GLOBALS['config']['site']['base_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help - Rockband Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .toc {
            position: sticky;
            top: 1rem;
        }
        .toc a {
            display: block;
            padding: 0.25rem 0;
            color: inherit;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .toc a:hover {
            color: var(--bs-primary);
        }
        .toc a.active {
            color: var(--bs-primary);
            font-weight: 600;
        }
        .section-anchor {
            scroll-margin-top: 80px;
        }
        .tip-card {
            border-left: 4px solid var(--bs-info);
        }
        .warning-card {
            border-left: 4px solid var(--bs-warning);
        }
        kbd {
            background-color: #eee;
            border-radius: 3px;
            border: 1px solid #b4b4b4;
            color: #333;
            display: inline-block;
            font-size: 0.85em;
            font-weight: 700;
            line-height: 1;
            padding: 2px 4px;
            white-space: nowrap;
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
                        <a class="nav-link active" href="help.php"><i class="bi bi-question-circle"></i> Help</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Table of Contents Sidebar -->
            <div class="col-lg-3 d-none d-lg-block">
                <div class="toc card">
                    <div class="card-header">
                        <strong><i class="bi bi-list-ul"></i> Contents</strong>
                    </div>
                    <div class="card-body">
                        <a href="#overview">Overview</a>
                        <a href="#getting-started">Getting Started</a>
                        <a href="#managing-songs">Managing Songs</a>
                        <a href="#managing-events">Managing Events</a>
                        <a href="#managing-entries">Managing Entries</a>
                        <a href="#display-modes">Display Modes</a>
                        <a href="#settings">Settings</a>
                        <a href="#tips">Tips & Best Practices</a>
                        <a href="#third-party">Third-Party APIs</a>
                        <a href="#troubleshooting">Troubleshooting</a>
                        <a href="#copyright">Copyright & License</a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <h1 class="mb-4"><i class="bi bi-question-circle"></i> Help & Documentation</h1>

                <!-- Overview -->
                <section id="overview" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-info-circle"></i> Overview</h4>
                        </div>
                        <div class="card-body">
                            <p>Rockband Scheduler is a web application for managing Rock Band performance sign-ups at events. The system has two main interfaces:</p>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card h-100 border-primary">
                                        <div class="card-body">
                                            <h5><i class="bi bi-phone"></i> Public Signup Page</h5>
                                            <p class="text-muted mb-0">Users scan a QR code or visit a link to access the event signup page. They choose a song, enter their name, and are automatically assigned to the next available slot.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-success">
                                        <div class="card-body">
                                            <h5><i class="bi bi-gear"></i> Admin Panel</h5>
                                            <p class="text-muted mb-0">You're here! The admin panel lets you manage songs, create events, view and edit entries, and configure system settings.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4">How It Works</h5>
                            <ol>
                                <li><strong>Build your song library</strong> - Add songs that players can choose from</li>
                                <li><strong>Create an event</strong> - Set up an event with a name, time, and number of performance slots</li>
                                <li><strong>Share the QR code</strong> - Display the event's QR code so attendees can sign up</li>
                                <li><strong>Manage the lineup</strong> - View, reorder, and mark performances as finished</li>
                            </ol>

                            <h5 class="mt-4">User Signup Flow</h5>
                            <p>When users access the signup page, they follow a simple two-step process:</p>
                            <ol>
                                <li><strong>Step 1: Choose a song</strong> - Search or scroll through the song list and select one</li>
                                <li><strong>Step 2: Enter name</strong> - Provide their name and confirm the signup</li>
                            </ol>
                            <p>The system automatically assigns them to the next available slot. Users can see how many spots are filled (e.g., "3 of 10 spots filled") and the page automatically updates when slots fill up or become available.</p>
                        </div>
                    </div>
                </section>

                <!-- Getting Started -->
                <section id="getting-started" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-rocket-takeoff"></i> Getting Started</h4>
                        </div>
                        <div class="card-body">
                            <h5>Quick Setup Checklist</h5>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-1-circle-fill text-primary me-3 fs-4"></i>
                                    <div>
                                        <strong>Add songs to your library</strong>
                                        <div class="text-muted small">Go to <a href="songs.php">Songs</a> and add the songs available in your Rock Band game. Use Deezer search to quickly find and add songs with album art.</div>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-2-circle-fill text-primary me-3 fs-4"></i>
                                    <div>
                                        <strong>Create your first event</strong>
                                        <div class="text-muted small">Go to <a href="events.php">Events</a> and click "Create Event". Set the event name, location, times, and number of performance slots.</div>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-3-circle-fill text-primary me-3 fs-4"></i>
                                    <div>
                                        <strong>Display the QR code</strong>
                                        <div class="text-muted small">From the event details, open the signup display to show a full-screen QR code on a TV or monitor.</div>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-4-circle-fill text-primary me-3 fs-4"></i>
                                    <div>
                                        <strong>Manage the performance queue</strong>
                                        <div class="text-muted small">Click "Manage Entries" on any event to see who's signed up, reorder performers, and mark songs as finished.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Managing Songs -->
                <section id="managing-songs" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-music-note-list"></i> Managing Songs</h4>
                        </div>
                        <div class="card-body">
                            <p>The <a href="songs.php">Songs</a> page is where you manage your song library. Users will choose from these songs when signing up for a slot.</p>

                            <h5 class="mt-4"><i class="bi bi-plus-circle"></i> Adding Songs</h5>
                            <p>There are two ways to add songs:</p>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6><i class="bi bi-search"></i> Deezer Search (Recommended)</h6>
                                            <p class="small text-muted">Click "Add Song", then use the Deezer search to find songs. This automatically fills in the title, artist, album, year, duration, and album art.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6><i class="bi bi-pencil"></i> Manual Entry</h6>
                                            <p class="small text-muted">If a song isn't on Deezer, you can manually enter all the details. Just fill in the form without using Deezer search.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4"><i class="bi bi-upload"></i> CSV Import</h5>
                            <p>You can bulk import songs from a CSV file. The CSV should have columns for: title, artist, album, year. The import will search Deezer for each song to get album art and duration.</p>

                            <h5 class="mt-4"><i class="bi bi-play-circle"></i> Audio Previews</h5>
                            <p>Songs added via Deezer include 30-second audio previews. Click the play button next to any song to hear a preview. This helps verify you have the right version of a song.</p>

                            <div class="card tip-card mt-4">
                                <div class="card-body">
                                    <h6><i class="bi bi-lightbulb"></i> Tip</h6>
                                    <p class="mb-0">Build your song library before your first event. It's easier to add songs when you're not also managing signups!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Managing Events -->
                <section id="managing-events" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-calendar-event"></i> Managing Events</h4>
                        </div>
                        <div class="card-body">
                            <p>The <a href="events.php">Events</a> page shows all your events organized by status: Active (happening now), Upcoming, and Past.</p>

                            <h5 class="mt-4"><i class="bi bi-plus-circle"></i> Creating an Event</h5>
                            <p>Click "Create Event" and fill in:</p>
                            <ul>
                                <li><strong>Event Name</strong> - A descriptive name (e.g., "Friday Night Rock Band")</li>
                                <li><strong>Location</strong> - Optional venue name or address</li>
                                <li><strong>Start/End Time</strong> - When the event runs (enter times as they appear at the venue)</li>
                                <li><strong>Performance Slots</strong> - How many performances you want to allow</li>
                                <li><strong>Theme</strong> - Color scheme for the signup page</li>
                            </ul>

                            <h5 class="mt-4"><i class="bi bi-qr-code"></i> QR Codes</h5>
                            <p>When you create an event, a QR code is automatically generated. This QR code links directly to the event's signup page. You can:</p>
                            <ul>
                                <li>View the QR code in the event details</li>
                                <li>Open the "Signup Display" for a full-screen QR code perfect for TVs</li>
                                <li>Copy the signup URL to share via text or email</li>
                            </ul>

                            <h5 class="mt-4"><i class="bi bi-palette"></i> Event Themes</h5>
                            <p>Each event can have its own color theme. There are 16 themes available: 8 dark themes and 8 light themes. The theme affects the public signup page appearance.</p>

                            <h5 class="mt-4"><i class="bi bi-clock"></i> Event Timing</h5>
                            <p>Events are accessible anytime someone has the QR code or URL - there's no strict time restriction. The start/end times are primarily for your reference and organizing events.</p>

                            <div class="card warning-card mt-4">
                                <div class="card-body">
                                    <h6><i class="bi bi-exclamation-triangle"></i> Important</h6>
                                    <p class="mb-0">Deleting an event will also delete all entries (signups) for that event. This cannot be undone!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Managing Entries -->
                <section id="managing-entries" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-list-ol"></i> Managing Entries</h4>
                        </div>
                        <div class="card-body">
                            <p>Click "Manage Entries" on any event to see and control the performance lineup.</p>

                            <h5 class="mt-4"><i class="bi bi-eye"></i> Viewing the Lineup</h5>
                            <p>The entries page shows all performance slots with:</p>
                            <ul>
                                <li>Slot number</li>
                                <li>Performer name (if provided)</li>
                                <li>Song selection with album art</li>
                                <li>Audio preview button (for songs with Deezer data)</li>
                            </ul>

                            <h5 class="mt-4"><i class="bi bi-arrows-move"></i> Reordering</h5>
                            <p>Use the up/down arrow buttons to reorder performers. This is useful when someone needs to go earlier or later, or if you want to rearrange for variety.</p>

                            <h5 class="mt-4"><i class="bi bi-check-circle"></i> Marking as Finished</h5>
                            <p>Click the circle icon next to any entry to mark it as finished. Finished entries are grayed out and can't be reordered. This helps you track progress through the lineup.</p>

                            <h5 class="mt-4"><i class="bi bi-pencil"></i> Editing Entries</h5>
                            <p>Click the pencil button to edit an entry. You can change the performer name or select a different song.</p>

                            <h5 class="mt-4"><i class="bi bi-trash"></i> Clearing Entries</h5>
                            <p>The "Clear" dropdown offers two options:</p>
                            <ul>
                                <li><strong>Clear Unfinished</strong> - Removes all entries that haven't been marked as finished</li>
                                <li><strong>Clear All</strong> - Removes all entries (useful for resetting between event sessions)</li>
                            </ul>

                            <div class="card tip-card mt-4">
                                <div class="card-body">
                                    <h6><i class="bi bi-lightbulb"></i> Tip</h6>
                                    <p class="mb-0">The entries page auto-refreshes every 5 seconds, so you'll see new signups without manually refreshing.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Display Modes -->
                <section id="display-modes" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-tv"></i> Display Modes</h4>
                        </div>
                        <div class="card-body">
                            <p>There are special display pages designed for TVs and large screens:</p>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5><i class="bi bi-qr-code"></i> Signup Display</h5>
                                            <p class="text-muted">A full-screen page showing just the QR code with instructions. Perfect for displaying on a TV so attendees can easily scan and sign up.</p>
                                            <p class="small">Access: Use <code>signup-display.php?eventid=...</code> or press and hold the event title on the public signup page for 3 seconds.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5><i class="bi bi-display"></i> Signage Display</h5>
                                            <p class="text-muted">Shows the current performer and upcoming queue with album art. Great for displaying near the performance area so everyone can see who's up next.</p>
                                            <p class="small">Access: Use <code>signage.php?eventid=...</code></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card tip-card mt-4">
                                <div class="card-body">
                                    <h6><i class="bi bi-lightbulb"></i> Hidden Access to Signup Display</h6>
                                    <p class="mb-0">On the public signup page, press and hold the event title (the large text at the top) for 3 seconds to open the signup display. This provides quick access without a visible link that might confuse attendees.</p>
                                </div>
                            </div>

                            <div class="card tip-card mt-4">
                                <div class="card-body">
                                    <h6><i class="bi bi-fullscreen"></i> Full-Screen Tip</h6>
                                    <p class="mb-0">For the best display experience, use a browser in full-screen mode (press <kbd>F11</kbd> on most browsers).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Settings -->
                <section id="settings" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-gear"></i> Settings</h4>
                        </div>
                        <div class="card-body">
                            <p>The <a href="settings.php">Settings</a> page lets you configure system-wide options:</p>

                            <h5 class="mt-4"><i class="bi bi-calendar-event"></i> Event Defaults</h5>
                            <p>Set the default duration for new events. When creating an event, the end time will automatically be set this many hours after the start time.</p>

                            <h5 class="mt-4"><i class="bi bi-palette"></i> Default Theme</h5>
                            <p>Choose which color theme is pre-selected when creating new events.</p>
                        </div>
                    </div>
                </section>

                <!-- Tips & Best Practices -->
                <section id="tips" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-lightbulb"></i> Tips & Best Practices</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Before the Event</h5>
                                    <ul>
                                        <li>Build your song library ahead of time</li>
                                        <li>Create the event and test the QR code</li>
                                        <li>Set up your display screen with the signup display</li>
                                        <li>Consider how many slots you need (more slots = longer event)</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>During the Event</h5>
                                    <ul>
                                        <li>Keep the entries page open on your phone/tablet</li>
                                        <li>Mark performances as finished as they complete</li>
                                        <li>Use reordering if someone needs to go earlier/later</li>
                                        <li>The page auto-refreshes, but you can manually refresh too</li>
                                    </ul>
                                </div>
                            </div>

                            <h5 class="mt-4">Slot Management Tips</h5>
                            <ul>
                                <li><strong>Overbook slightly</strong> - Some people sign up but don't show. Having a few extra slots helps.</li>
                                <li><strong>Use "Clear Unfinished" between sessions</strong> - If you're running multiple rounds, clear unfinished slots to start fresh.</li>
                                <li><strong>Preview songs</strong> - Use the audio preview to check song lengths when planning your lineup.</li>
                            </ul>

                            <h5 class="mt-4">Song Library Tips</h5>
                            <ul>
                                <li><strong>Match your game library</strong> - Only add songs you actually have in Rock Band.</li>
                                <li><strong>Check album art</strong> - Deezer sometimes returns wrong versions. Preview and verify.</li>
                                <li><strong>Track popularity</strong> - The dashboard shows most/least selected songs. Use this to curate your library.</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Third-Party APIs -->
                <section id="third-party" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-plug"></i> Third-Party APIs & Libraries</h4>
                        </div>
                        <div class="card-body">
                            <p>Rockband Scheduler uses the following third-party services and libraries:</p>

                            <h5 class="mt-4"><i class="bi bi-music-note-beamed"></i> Deezer API</h5>
                            <p>The Deezer API is used to search for songs and retrieve metadata including:</p>
                            <ul>
                                <li>Song title, artist, and album information</li>
                                <li>Album artwork</li>
                                <li>Song duration</li>
                                <li>30-second audio previews</li>
                            </ul>
                            <p>When using this application, please ensure you comply with Deezer's terms of service:</p>
                            <ul>
                                <li><a href="https://developers.deezer.com/guidelines" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i> Deezer API Guidelines</a></li>
                                <li><a href="https://deezerbrand.com/document/37#/-/overview" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i> Deezer Brand Guidelines</a></li>
                            </ul>
                            <div class="card tip-card mt-3">
                                <div class="card-body">
                                    <h6><i class="bi bi-info-circle"></i> Note</h6>
                                    <p class="mb-0">The Deezer API has rate limits (50 requests per 5 seconds). The import feature includes automatic throttling to respect these limits.</p>
                                </div>
                            </div>
                            <div class="card tip-card mt-3">
                                <div class="card-body">
                                    <h6><i class="bi bi-music-note"></i> Audio Previews</h6>
                                    <p class="mb-0">As of January 2026, Deezer allows 30-second audio previews for non-authenticated users, making this feature free to use without requiring a Deezer account or API key.</p>
                                </div>
                            </div>

                            <h5 class="mt-4"><i class="bi bi-qr-code"></i> QR Code Generation</h5>
                            <p>QR codes are generated using the <a href="https://goqr.me/api/" target="_blank" rel="noopener noreferrer">QR Server API</a>, a free QR code generation service provided by <a href="https://goqr.me/" target="_blank" rel="noopener noreferrer">goQR.me</a>.</p>

                            <h5 class="mt-4"><i class="bi bi-bootstrap"></i> Frontend Libraries</h5>
                            <ul>
                                <li><strong><a href="https://getbootstrap.com/" target="_blank" rel="noopener noreferrer">Bootstrap 5.3</a></strong> - CSS framework for responsive design</li>
                                <li><strong><a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener noreferrer">Bootstrap Icons</a></strong> - Icon library</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Troubleshooting -->
                <section id="troubleshooting" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-wrench"></i> Troubleshooting</h4>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="troubleshootingAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble1">
                                            QR code won't scan
                                        </button>
                                    </h2>
                                    <div id="trouble1" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Make sure the screen brightness is high enough</li>
                                                <li>Try moving the phone closer or further from the screen</li>
                                                <li>Check that the QR code is fully visible and not cut off</li>
                                                <li>Try copying the signup URL instead and sharing it directly</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble2">
                                            Deezer search isn't finding songs
                                        </button>
                                    </h2>
                                    <div id="trouble2" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Try simpler search terms (just song title or artist name)</li>
                                                <li>Some songs may not be in Deezer's catalog - add them manually</li>
                                                <li>Check for spelling variations (especially for older songs)</li>
                                                <li>The Deezer API has rate limits; wait a moment if you've done many searches</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble3">
                                            Audio preview won't play
                                        </button>
                                    </h2>
                                    <div id="trouble3" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Not all songs have audio previews available</li>
                                                <li>Check your device volume and that it's not muted</li>
                                                <li>Some browsers block autoplay; try clicking the play button again</li>
                                                <li>Preview URLs from Deezer can expire; the system fetches fresh URLs automatically</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble4">
                                            Users can't sign up
                                        </button>
                                    </h2>
                                    <div id="trouble4" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Check that there are available slots remaining (page shows "X of Y spots filled")</li>
                                                <li>Make sure your song library has songs for them to choose from</li>
                                                <li>Both a song selection and performer name are required</li>
                                                <li>If they see "All spots are filled!", the event is full - delete entries or increase the slot count</li>
                                                <li>Check the browser console for any error messages</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble5">
                                            Album art is missing or wrong
                                        </button>
                                    </h2>
                                    <div id="trouble5" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Album art is fetched from Deezer when adding songs</li>
                                                <li>Try editing the song and re-searching on Deezer to update the art</li>
                                                <li>Some songs may have multiple versions with different album art</li>
                                                <li>Album art must be less than 64kb, if the file is larger than this it will not be added to the database</li>
                                                <li>If Deezer does not have artwork for the album or the song is not available on Deezer, artwork may be uploaded manually</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble6">
                                            Changes aren't showing up
                                        </button>
                                    </h2>
                                    <div id="trouble6" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>The entries page auto-refreshes every 5 seconds</li>
                                                <li>Try clicking the Refresh button manually</li>
                                                <li>Check your internet connection</li>
                                                <li>Hard refresh the page (<kbd>Ctrl</kbd>+<kbd>F5</kbd> or <kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>R</kbd>)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble7">
                                            How do users know when slots open up?
                                        </button>
                                    </h2>
                                    <div id="trouble7" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>The public signup page polls for updates every 5 seconds</li>
                                                <li>If slots were full and one becomes available, the signup form automatically reappears</li>
                                                <li>If you edit the event and increase the slot count, users will see the updated total</li>
                                                <li>The "X of Y spots filled" counter updates automatically</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Copyright & License -->
                <section id="copyright" class="section-anchor mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="bi bi-c-circle"></i> Copyright & License</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Rockband Scheduler</strong><br>
                            Copyright &copy; 2026 Jonathan Dean</p>

                            <p>This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.</p>

                            <p>This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.</p>

                            <p>You should have received a copy of the GNU General Public License along with this program. If not, see <a href="https://www.gnu.org/licenses/" target="_blank" rel="noopener noreferrer">https://www.gnu.org/licenses/</a>.</p>

                            <p><i class="bi bi-github"></i> Source code available at <a href="https://github.com/ke4ukz/RockbandScheduler" target="_blank" rel="noopener noreferrer">github.com/ke4ukz/RockbandScheduler</a></p>

                            <h5 class="mt-4"><i class="bi bi-trademark"></i> Trademark Notice</h5>
                            <p>Rock Band&trade; is a registered trademark of Harmonix Music Systems, Inc. This application is not affiliated with, endorsed by, or sponsored by Harmonix Music Systems, Inc. or any of its subsidiaries or affiliates.</p>
                            <p>This is an independent, fan-made tool created to help organize Rock Band performance events. All product names, logos, and brands are property of their respective owners.</p>
                        </div>
                    </div>
                </section>

                <!-- Footer -->
                <div class="text-muted text-center mb-4">
                    <p><i class="bi bi-code-slash"></i> Rockband Scheduler - Built with Bootstrap 5 and PHP</p>
                    <p class="small">&copy; 2026 &bull; Licensed under the <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank" rel="noopener noreferrer">GNU GPL v3</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Highlight current section in TOC based on scroll position
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.section-anchor');
            const tocLinks = document.querySelectorAll('.toc a');

            function updateTOC() {
                let current = '';
                sections.forEach(section => {
                    const sectionTop = section.offsetTop - 100;
                    if (window.scrollY >= sectionTop) {
                        current = section.getAttribute('id');
                    }
                });

                tocLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + current) {
                        link.classList.add('active');
                    }
                });
            }

            window.addEventListener('scroll', updateTOC);
            updateTOC();
        });
    </script>
</body>
</html>
