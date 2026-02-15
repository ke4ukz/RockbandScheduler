# Rockband Scheduler

A web application for managing Rock Band performance sign-ups at events. Users scan a QR code to access an event page where they can claim performance slots and select songs from a pre-populated library.

## Features

### Event Management
- Create events with name, location, start/end times, and configurable number of performance slots (1-255)
- Automatic QR code generation for easy mobile access
- Event theming with 16 pre-configured color schemes (8 dark, 8 light)
- Event status tracking (active, upcoming, past)
- Configurable default event duration
- Adjustable slot count (increasing adds available slots; decreasing deletes entries beyond the new limit)

### Song Library
- Searchable song database with artist, album, title, year, and duration
- Deezer integration for automatic metadata lookup and album art
- 30-second audio previews from Deezer
- Song popularity tracking (selection count and last selected date)
- CSV bulk import (Song/Title and Artist columns, Deezer fetches remaining metadata)
- Album art stored locally (up to 64KB per image)

### Public Signup
- Mobile-first responsive design with two-step signup flow
- QR code scan to access event signup page
- Step 1: Search and select a song from the library
- Step 2: Enter performer name and confirm signup
- Automatic slot assignment (next available slot)
- Real-time slot availability with automatic updates
- Slots counter shows "X of Y spots filled"
- Automatic recovery when slots become available

### Admin Panel
- Dashboard with quick stats and song analytics
- Event management (create, edit, delete, view details)
- Song library management with Deezer search
- Entry management with reorder, mark finished, and bulk clear
- Settings configuration for default duration and default theme
- Dark/light mode toggle with per-user preference (saved in browser localStorage)

### Display Modes
- **Signup Display**: Full-screen QR code for TV/projector display (hidden access via 3-second press on event title), responsive to screen orientation and size
- **Signage**: Performance queue display showing current and upcoming performers

## User Guide: Public Signup

The public signup page is the attendee-facing side of the application. Users reach it by scanning a QR code displayed at the event or by following a direct link.

### Signing Up

The signup process has two steps:

1. **Choose a song** - Users search or scroll through the song library and tap a song to select it. Songs with Deezer data include a play button for a 30-second audio preview. After selecting a song, tap "Next" to continue.
2. **Enter your name** - Users type their performer name and tap "Sign Up" to confirm.

After signing up, a confirmation screen shows the assigned slot number (e.g., "#3"). Users can then sign up another person if needed.

### Slot Availability

The signup page shows a live counter (e.g., "3 of 10 spots filled") that updates automatically every few seconds. When all slots are full, the signup form is replaced with an "All spots are filled!" message. If a slot opens up (because an admin cleared an entry or increased the slot count), the signup form reappears automatically without the user needing to refresh.

### Hidden Shortcut: Signup Display

Pressing and holding the event title at the top of the signup page for 3 seconds opens the full-screen QR code display. This provides a quick way to switch to the TV-friendly view without a visible link that might confuse attendees.

## Admin Guide

The admin panel is accessed at `/admin/` and is protected by HTTP Basic Auth. After logging in, you'll see a navigation bar with links to the Dashboard, Songs, Events, and Settings pages.

### Getting Started

A typical setup workflow is:

1. **Build your song library** - Add the songs available in your Rock Band game
2. **Create an event** - Set the name, time, location, and number of performance slots
3. **Display the QR code** - Show the event's QR code on a TV or monitor so attendees can scan and sign up
4. **Manage the lineup** - Use the entries page to track performances, reorder the queue, and mark slots as finished

### Dashboard

The dashboard shows at-a-glance stats: total songs in the library, number of upcoming events, and any currently active events. Active events link directly to their entries page for quick access. The dashboard also displays song statistics including most/least popular songs, duration extremes, and selection activity.

### Managing the Song Library

The **Songs** page is where you maintain the list of songs users can choose from during signup.

**Adding songs via Deezer search (recommended):**
Click "Add Song", then use the Deezer search box at the top of the modal. Search for a song, click a result, and all fields (title, artist, album, year, duration, album art) are filled in automatically. Click "Save Song" to add it to the library.

**Adding songs manually:**
If a song isn't available on Deezer, fill in the form fields directly without using the Deezer search. Title, artist, album, and year are required.

**Bulk import from CSV:**
Click "Import CSV" to upload a CSV file with `Song` (or `Title`) and `Artist` columns. The import tool automatically searches Deezer for each song:
- **Matched** songs (green) are ready to import with full metadata.
- **Need review** songs (yellow) found multiple possible matches - select the correct one from the options shown.
- **Not found** songs (red) can be retried with a different search query, entered manually, or skipped.

The import page lets you resolve all issues before committing anything to the database.

**Editing and deleting songs:**
Use the pencil icon to edit a song's details or the trash icon to delete it. Deleting a song that is referenced by existing event entries will clear the song reference from those entries but preserve the performer name and slot.

**Audio previews:**
Songs added via Deezer have a play button for 30-second audio previews. This is available on both the Songs page and the Entries page.

### Managing Events

The **Events** page displays events organized by status: Active (happening now), Upcoming, and Past. Past events are hidden by default and can be loaded on demand.

**Creating an event:**
Click "Create Event" and fill in:
- **Event Name** - A descriptive name (e.g., "Friday Night Rock Band")
- **Location** - Optional venue name or room number
- **Start/End Time** - Enter times as they would appear on a clock at the venue (no timezone conversion is applied)
- **Performance Slots** - How many signups to allow (1-255)
- **Color Theme** - Choose from 16 color schemes (8 dark, 8 light) that style the public signup page

A QR code is generated automatically when the event is created. You can view it from the event detail modal, and the signup URL can be copied to share directly.

**Editing an event:**
Click the "Edit" button on any event card to change its details. Adjusting the slot count has immediate effects:
- **Increasing slots** makes new slots available for signups right away.
- **Decreasing slots** permanently deletes any entries in positions beyond the new limit (e.g., reducing from 20 to 15 deletes entries in slots 16-20).

**Deleting an event:**
Deleting an event permanently removes it and all of its entries. A confirmation dialog warns about this before proceeding.

**Event timing:**
Events are accessible anytime someone has the QR code or URL - there is no strict time-based lockout. The start/end times are primarily for your organizational reference and for sorting events by status on the admin page.

### Managing Entries (The Performance Lineup)

Click "Manage" on any event card to open the entries page, which shows all performance slots for that event.

**Viewing the lineup:**
Each slot shows its number, the performer's name, their selected song with album art, and action buttons. Empty slots display an "Add" button. The page automatically refreshes every 5 seconds to show new signups as they come in; you can also click the "Refresh" button manually.

**Adding an entry manually:**
Click "Add" on any empty slot to open the entry editor. Enter a performer name, optionally select a song from the library, and save.

**Editing an entry:**
Click the pencil icon on a filled slot to change the performer name or song selection.

**Reordering entries:**
Use the up/down arrow buttons on each entry to swap it with the adjacent slot. This is useful for accommodating schedule changes.

**Marking entries as finished:**
Click the circle icon next to an entry to mark it as finished. Finished entries are visually grayed out with a strikethrough on the performer name, and their reorder/edit buttons are hidden. Click the check icon again to unmark.

**Clearing entries:**
The "Clear" dropdown in the card header provides two options:
- **Clear Unfinished** - Removes all entries not yet marked as finished (useful for resetting between rounds)
- **Clear All Entries** - Removes every entry for the event

### Display Modes

Two special pages are designed for TVs and large screens at the event:

**Signup Display** (`signup-display.php?eventid=...`):
A full-screen view showing only the event's QR code with scanning instructions. The layout adapts to the screen's orientation and size. This is ideal for a TV near the entrance so attendees can easily scan and sign up. Access it from the event detail modal in admin, or by pressing and holding the event title on the public signup page for 3 seconds.

**Signage Display** (`signage.php?eventid=...`):
Shows the current performer prominently on the left (with album art, song title, and artist) and an "Up Next" queue of upcoming performers on the right. This page polls for updates every 3 seconds and includes a live clock. When all performances are finished, it displays a "That's a wrap!" message. Ideal for a screen near the performance area.

For the best experience, use a browser in full-screen mode (F11 on most browsers).

### Settings

The **Settings** page provides system-wide configuration:

**Event Defaults:**
- **Default Event Duration** - Sets how many hours after the start time the end time defaults to when creating a new event (1-24 hours).

**Default Theme:**
- **Default Theme** - Which color theme is pre-selected when creating new events. A preview swatch shows the selected theme's gradient and accent color.

**Name Content Filter (optional):**
If Sightengine API credentials are configured, this section appears with controls for filtering inappropriate performer names during public signup. Without credentials, instructions for setting up Sightengine are shown instead.

Profanity filters have four sensitivity levels (Off, Low, Medium, High) across five categories: sexual language, discriminatory language, insults, other inappropriate language, and symbol substitution. Additional on/off toggles are available for extremism, violence/self-harm, and drug references.

The content filter uses a fail-open design: if the Sightengine API is unavailable, signups proceed without filtering rather than blocking legitimate users.

**Admin Theme Toggle:**
The sun/moon icon in the navigation bar toggles between dark and light mode for the admin panel. This preference is saved per-browser in localStorage and is independent of the public-facing event themes.

### Tips for Running an Event

**Before the event:**
- Build your song library ahead of time - it's easier without the pressure of a live event.
- Create the event and test the QR code from a phone to make sure everything works.
- Set up your display screen with the signup display page in full-screen mode.

**During the event:**
- Keep the entries page open on a phone or tablet to manage the lineup on the go.
- Mark performances as finished as they complete to track progress through the queue.
- Use reordering if someone needs to go earlier or later in the lineup.
- If you run out of slots, edit the event to increase the slot count - new slots become available immediately.
- Use "Clear Unfinished" between rounds to reset for a new set of signups.

## Requirements

- PHP 7.4+
- MySQL 5.7+ (with UUID support)
- Web server (Apache with mod_rewrite, or nginx)

## Installation

1. Clone/upload files to your web server
2. Create a MySQL database and import `schema.sql`
3. Create the config file at `../../config/rockband_scheduler_config.ini` (relative to project root):

```ini
[database]
host = localhost
name = your_database
user = your_username
pass = your_password

[admin]
token = your_secure_random_token

[site]
base_url = https://yourdomain.com/rockband

[event]
default_duration_hours = 4

[theme]
default_theme_id = 1
```

4. Generate a secure admin token:
```bash
openssl rand -hex 32
```

5. Set up HTTP Basic Auth on the `/admin` directory (see below)

### Admin Directory Protection

The `/admin` directory must be protected with HTTP Basic Auth. In cPanel, use **Directory Privacy** to password-protect the admin folder. This will auto-generate an `.htaccess` file with the appropriate settings.

The `admin/.htaccess` file is excluded from version control (in `.gitignore`) because it contains server-specific paths.

## Configuration

### Config File Location

The config file should be placed outside the web root for security. Example directory structure:
```
/home/username/
├── config/
│   └── rockband_scheduler_config.ini    # Config file (outside web root)
└── public_html/
    └── rockband/                         # Project root (web accessible)
```

### Configuration Sections

**[database]** - Database connection settings
- `host` - MySQL server hostname
- `name` - Database name
- `user` - Database username
- `pass` - Database password

**[admin]** - Admin authentication
- `token` - Secure token for API authentication (use `openssl rand -hex 32`)

**[site]** - Site settings
- `base_url` - Full URL to the application (used for QR code generation)

**[event]** - Event defaults (configurable via admin settings page)
- `default_duration_hours` - Default event duration in hours (1-24)

**[theme]** - Theme settings (configurable via admin settings page)
- `default_theme_id` - Default theme for new events

**[sightengine]** - Optional content filtering (requires free account at sightengine.com)
- `api_user` - Sightengine API user ID
- `api_secret` - Sightengine API secret

**[content_filter]** - Content filter settings (requires Sightengine credentials; also configurable via admin settings page)
- `profanity_sexual` - Sexual language filter level (0=off, 1=low, 2=medium, 3=high)
- `profanity_discriminatory` - Discriminatory language filter level (0-3)
- `profanity_insult` - Insults filter level (0-3)
- `profanity_inappropriate` - Other inappropriate language filter level (0-3)
- `profanity_grawlix` - Symbol substitution filter level (0-3)
- `block_extremism` - Block extremism content (0=off, 1=on)
- `block_violence` - Block violence/self-harm content (0=off, 1=on)
- `block_drugs` - Block drug/medicine references (0=off, 1=on)

## Database Schema

Four main tables:

- **events** - Event definitions with UUID primary keys, QR codes stored as BLOBs
- **songs** - Song library with Deezer metadata and album art BLOBs
- **entries** - Performance slot assignments linking events to songs
- **themes** - 16 pre-configured color themes

UUIDs are stored as binary(16) and converted using MySQL's `UUID_TO_BIN()` and `BIN_TO_UUID()` functions.

### Foreign Key Constraints

The database enforces referential integrity with the following cascade rules:

- **Deleting an event**: All entries for that event are automatically deleted (`ON DELETE CASCADE`)
- **Deleting a song**: Entries referencing that song have their `song_id` set to NULL (`ON DELETE SET NULL`), preserving the performer name and slot
- **Deleting a theme**: Blocked if any events use that theme (`ON DELETE RESTRICT`); reassign events first
- **Updating event/song IDs**: Changes cascade to related entries automatically

## File Structure

```
/
├── admin/                      # Admin panel (HTTP Basic Auth protected)
│   ├── .htaccess               # Auth config (gitignored, created by cPanel)
│   ├── admin-theme.js          # Dark/light mode toggle script
│   ├── default.php             # Dashboard with stats
│   ├── entries.php             # Per-event entry management
│   ├── events.php              # Event management
│   ├── help.php                # Help documentation
│   ├── import.php              # CSV bulk song import
│   ├── settings.php            # Settings configuration
│   └── songs.php               # Song library management
├── api/                        # JSON REST API endpoints
│   ├── deezer.php              # Deezer search proxy (public)
│   ├── entries.php             # Entries (mixed public/admin)
│   ├── events.php              # Event CRUD (admin)
│   ├── settings.php            # Settings API (admin)
│   ├── songs.php               # Song CRUD (admin)
│   └── themes.php              # Theme listing
├── docs/                       # Documentation (blocked by .htaccess)
│   ├── .htaccess               # Denies all web access
│   ├── CLAUDE.md               # AI development notes
│   ├── README.md               # Project documentation
│   ├── config.sample.ini       # Sample config file template
│   ├── deezer notes.txt        # Deezer API notes
│   └── schema.sql              # Database schema
├── images/
│   └── Deezer logos            # Deezer branding assets
├── includes/
│   ├── config.php              # Configuration loader
│   ├── db.php                  # Database connection
│   └── helpers.php             # Shared utilities
├── .htaccess                   # Directory index and security rules
├── copyright.php               # License/attribution page
├── default.php                 # Public event signup page
├── signage.php                 # Performance queue display
└── signup-display.php          # Full-screen QR code display
```

## API Reference

### Authentication

Admin endpoints support two authentication methods:

**Session-based (for browser requests from admin pages):**
- Admin pages establish a PHP session with CSRF token
- Requests include `csrf_token` in JSON body: `{ "csrf_token": "...", "action": "..." }`
- Server validates session authentication and CSRF token

**Token-based (for curl/scripts):**
- JSON body: `{ "admin_token": "..." }` (recommended)
- Header: `X-Admin-Token: ...`
- Query parameter: `?admin_token=...`

### Public Endpoints

**GET /api/entries.php?event_id={uuid}**
- List entries for an event

**POST /api/entries.php?event_id={uuid}**
- Create entry (user signup, auto-assigns next available slot)
- Body: `{ "performer_name": "...", "song_id": 123 }`
- Returns: `{ "success": true, "entry_id": 456, "position": 1 }`

**GET /api/deezer.php?q={query}**
- Search Deezer for songs

**GET /api/themes.php**
- List all available themes

### Admin Endpoints

All admin endpoints use POST with `admin_token` in the request body.

**Events** (`/api/events.php`)
- `{ "action": "list" }` - List events
- `{ "action": "get", "event_id": "uuid" }` - Get event
- `{ "action": "create", "name": "...", "start_time": "...", "end_time": "...", "num_entries": 20 }` - Create event
- `{ "action": "update", "event_id": "uuid", ... }` - Update event
- `{ "action": "delete", "event_id": "uuid" }` - Delete event

**Songs** (`/api/songs.php`)
- `{ "action": "list" }` - List songs (supports `limit`, `offset`, `search`)
- `{ "action": "get", "song_id": 123 }` - Get song
- `{ "action": "create", "title": "...", "artist": "...", "album": "...", "year": 2020 }` - Create song
- `{ "action": "update", "song_id": 123, ... }` - Update song
- `{ "action": "delete", "song_id": 123 }` - Delete song

**Entries** (`/api/entries.php`)
- `{ "action": "list", "event_id": "uuid" }` - List entries
- `{ "action": "create", "event_id": "uuid", "position": 1, ... }` - Create/update entry
- `{ "action": "update", "entry_id": 123, ... }` - Update entry
- `{ "action": "delete", "entry_id": 123 }` - Delete entry
- `{ "action": "reorder", "event_id": "uuid", "order": [...] }` - Reorder entries

**Settings** (`/api/settings.php`)
- `{ "action": "get" }` - Get settings
- `{ "action": "update", "settings": { ... } }` - Update settings

## External Dependencies

### [Bootstrap 5](https://getbootstrap.com/) (CDN)
- **Used for**: UI styling and responsive layout
- **Version**: 5.3.2 (pinned)
- **Fallback**: Download and host locally if CDN unavailable

### [Bootstrap Icons](https://icons.getbootstrap.com/) (CDN)
- **Used for**: All iconography
- **Version**: 1.11.1 (pinned)
- **Fallback**: Self-host or replace with alternative icon library

### [Deezer API](https://developers.deezer.com/api)
- **Used for**: Song search, metadata, album art, audio previews
- **Rate limits**: 50 requests per 5 seconds (enforced in bulk import only; not a concern in normal use)
- **Fallback**: Songs can be added manually without Deezer. If Deezer becomes unavailable, existing songs continue to work but lose audio previews; new songs must be entered manually with all metadata.

### [QR Server API](https://goqr.me/api/)
- **Used for**: QR code generation
- **Fallback**: Use local PHP QR library (chillerlan/php-qrcode or endroid/qr-code)

### [Sightengine API](https://sightengine.com/) (Optional)
- **Used for**: Content filtering of performer names during public signup
- **Rate limits**: Free tier allows 2,000 requests/month (max 500/day); paid plans available for higher volume
- **Fallback**: Fail-open design - if Sightengine is unavailable or returns an error, signups proceed without filtering. Content moderation is a convenience feature, not a hard requirement. If the service goes offline or credentials expire, the only impact is that inappropriate names won't be blocked.

## Troubleshooting

### PHP Error Log

The application logs errors via PHP's `error_log()`. A `.user.ini` file in the project root configures PHP to write logs to `~/logs/rockband_error.log` (outside the web root). If this file is missing or the configured path is not writable, PHP falls back to the default location — on shared hosting (e.g., cPanel) this is often the application directory itself (e.g., `api/error_log` or `error_log` in the project root) rather than the standard Apache location.

Log messages are prefixed by subsystem:
- `Entries API error:` - Entry operations
- `Events API error:` - Event operations
- `Songs API error:` - Song operations
- `Database connection error:` - Startup connection failure
- `Sightengine API error:` - Content filter issues

## Timezone Handling

Event times are stored and displayed as **venue local time** (no timezone conversion). Enter times as they would appear on a clock at the event location. This approach works well for in-person events where attendees are at the physical location.

If an admin is managing an event remotely from a different timezone, the event may not appear as "active" in the dashboard since their computer clock differs from the venue's local time. This has no functional impact—events are not locked by time or date, and all management features remain fully accessible regardless of the displayed status.

## Security Features

- **Dual authentication**: Session-based with CSRF protection (browser) + token-based (scripts)
- **CSRF protection**: All admin API requests from browser require valid CSRF token tied to PHP session
- **Secure sessions**: PHP sessions with secure cookie settings (httponly, samesite=strict, secure when HTTPS)
- **HTTP Basic Auth**: Admin directory protected at web server level
- **Timing-safe comparison**: Admin tokens verified using `hash_equals()` to prevent timing attacks
- **SQL injection prevention**: All database queries use PDO prepared statements
- **XSS prevention**: HTML output escaped via `htmlspecialchars()` helper
- **Config isolation**: Sensitive configuration stored outside web root

## Deployment

The site is deployed via GitHub and cPanel's Git Version Control. To push updates to the live site:

1. Commit and push changes to GitHub
2. Log into the hosting control panel
3. Go to **Websites** → click **Settings** for the RockbandScheduler site
4. Go to the **Advanced** tab
5. Click **Manage** under the cPanel section
6. Click **Git Version Control** under Files
7. Click **Manage** for the RockbandScheduler repo
8. Click the **Pull or Deploy** tab
9. Click **Update from Remote**

## Future Ideas

- **Song metadata fields** — Add genre, content advisory, and custom tags to the song library
- **Event-level song filtering** — When creating an event, optionally filter the available song list by criteria such as:
  - Hide songs with content advisories (e.g., explicit lyrics)
  - Restrict to specific genres
  - Filter by custom tags (user-defined labels that can be assigned to songs and selected per event)

- **Persistent CSV import sessions** — Store in-progress CSV imports on the server so the import can be resumed if the browser tab is closed or the connection drops. Currently the entire import state is held client-side and lost if the page is unloaded.

### Known Issues

- **Import CSV button placement** — On large song libraries, the Import CSV button at the bottom of the Songs page requires excessive scrolling to reach. Needs to be relocated to the top of the page, duplicated at the bottom, or made into a floating/sticky button.

## AI Disclosure

This software was developed with assistance from Claude, an AI assistant by Anthropic. AI tools were used for code generation, documentation, and development guidance throughout the project.

## Disclaimer

This software is provided free of charge under the GPL v3 license. It was developed for a specific personal use case and is shared publicly in case others find it useful.

**There is no expectation of support, maintenance, or updates of any kind**, including critical security updates. Use at your own risk. If you choose to deploy this software, you are solely responsible for its security and maintenance.

## License

GNU General Public License v3.0 - See [LICENSE](LICENSE) or <https://www.gnu.org/licenses/gpl-3.0.html>
