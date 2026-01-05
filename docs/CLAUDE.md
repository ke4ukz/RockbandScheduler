# Claude Code Development Notes

This file provides context for AI-assisted development of Rockband Scheduler.

## Project Overview

A PHP/MySQL web application for managing Rock Band performance sign-ups at events. Users scan a QR code to sign up for performance slots.

## Architecture

### Backend
- **PHP 7.4+** with PDO for database access
- **MySQL 5.7+** with UUIDs stored as `binary(16)` using `UUID_TO_BIN()`/`BIN_TO_UUID()`
- Config file stored outside web root at `../../config/rockband_scheduler_config.ini`
- Token-based admin authentication with timing-safe comparison

### Frontend
- Bootstrap 5.3.2 (CDN) for styling
- Bootstrap Icons 1.11.1 (CDN) for iconography
- Vanilla JavaScript (no framework)
- Mobile-first responsive design

### External APIs
- **Deezer API** - Song search, metadata, album art, 30-second previews (rate limit: 50 req/5 sec)
- **QR Server API** - QR code generation

## File Structure

```
/                           # Public web root
├── admin/                  # Admin panel (HTTP Basic Auth protected)
│   ├── default.php         # Dashboard
│   ├── events.php          # Event CRUD
│   ├── songs.php           # Song library management
│   ├── entries.php         # Per-event entry management
│   ├── import.php          # CSV song import
│   ├── settings.php        # System settings
│   └── help.php            # Documentation
├── api/                    # JSON REST endpoints
│   ├── entries.php         # Entry CRUD (public + admin)
│   ├── events.php          # Event CRUD (admin only)
│   ├── songs.php           # Song CRUD (admin only)
│   ├── deezer.php          # Deezer search proxy
│   ├── themes.php          # Theme listing
│   └── settings.php        # Settings API
├── docs/                   # Documentation (blocked by .htaccess)
│   ├── CLAUDE.md           # AI development notes
│   ├── README.md           # Project documentation
│   ├── config.sample.ini   # Sample config file template
│   ├── deezer notes.txt    # Deezer API notes
│   └── schema.sql          # Database schema
├── includes/
│   └── helpers.php         # Shared utilities (h(), jsonResponse(), etc.)
├── images/                 # Deezer logo assets
├── config.php              # Config loader
├── db.php                  # Database connection
├── default.php             # Public signup page
├── signage.php             # Queue display for TVs
├── signup-display.php      # Full-screen QR display
└── copyright.php           # License/attribution page
```

## Key Patterns

### API Authentication
Admin endpoints accept token via:
1. JSON body: `{ "admin_token": "..." }` (preferred)
2. Header: `X-Admin-Token: ...`
3. Query param: `?admin_token=...`

### Public Signup Flow
1. User lands on `default.php?eventid=UUID`
2. Step 1: Select song from searchable list
3. Step 2: Enter performer name
4. Server auto-assigns next available slot position
5. Success confirmation shows assigned slot number

### Polling
- Public signup page polls `/api/entries.php` every 5 seconds
- Detects entry count changes AND slot count changes (if admin edits event)
- Auto-transitions between "full" and "signup" states

### Slot Management
- Increasing `num_entries` makes new slots available immediately
- Decreasing `num_entries` deletes entries beyond new limit (via trigger or API)

## Database Tables

- **events** - UUID PK, name, location, times, num_entries, theme_id, qr_code (BLOB)
- **songs** - Auto-increment PK, title, artist, album, year, duration, deezer_id, album_art (BLOB)
- **entries** - Auto-increment PK, event_id (FK), song_id (FK), position, performer_name, finished
- **themes** - 16 pre-configured color schemes (8 dark, 8 light)

## Common Tasks

### Adding a new admin page
1. Create PHP file in `/admin/`
2. Include config, db, helpers
3. Add nav link to all admin pages
4. Use Bootstrap 5 classes for layout

### Adding a new API endpoint
1. Create PHP file in `/api/`
2. Follow pattern: check method → validate auth → route action → call function
3. Use `jsonResponse()` and `jsonError()` from helpers
4. Document in README.md API Reference section

### Modifying public signup behavior
- Main logic in `default.php` JavaScript section
- API interaction with `/api/entries.php`
- State managed via `showStep()` function

## Testing Notes

- No automated test suite currently
- Manual testing via browser and curl for API
- Check browser console for JavaScript errors
- PHP errors logged to server error log

## Pending Tasks

None currently.

## UI Conventions

- Busy overlay during async operations (`.busy-overlay` + `.show`/`.visible`)
- Bootstrap toasts or alerts for user feedback
- Icons from Bootstrap Icons library
- Responsive breakpoints via Bootstrap grid

## License

GPL v3 - All source files have license header comments
