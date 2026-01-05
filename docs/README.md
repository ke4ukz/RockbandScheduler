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

The config file should be placed outside the web root for security:
```
/config/rockband_scheduler_config.ini    # Config file location
/public_html/rockband/                    # Project root
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

## AI Disclosure

This software was developed with assistance from Claude, an AI assistant by Anthropic. AI tools were used for code generation, documentation, and development guidance throughout the project.

## License

GNU General Public License v3.0 - See [LICENSE](LICENSE) or <https://www.gnu.org/licenses/gpl-3.0.html>
