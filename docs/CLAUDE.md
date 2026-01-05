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
- **Sightengine API** - Optional content filtering for performer names (rule-based text moderation)

## File Structure

```
/                           # Public web root
├── admin/                  # Admin panel (HTTP Basic Auth protected)
│   ├── .htaccess           # Auth config (gitignored, created by cPanel)
│   ├── admin-theme.js      # Dark/light mode toggle script
│   ├── default.php         # Dashboard
│   ├── entries.php         # Per-event entry management
│   ├── events.php          # Event CRUD
│   ├── help.php            # Documentation
│   ├── import.php          # CSV song import
│   ├── settings.php        # System settings
│   └── songs.php           # Song library management
├── api/                    # JSON REST endpoints
│   ├── deezer.php          # Deezer search proxy
│   ├── entries.php         # Entry CRUD (public + admin)
│   ├── events.php          # Event CRUD (admin only)
│   ├── settings.php        # Settings API
│   ├── songs.php           # Song CRUD (admin only)
│   └── themes.php          # Theme listing
├── docs/                   # Documentation (blocked by .htaccess)
│   ├── .htaccess           # Denies all web access
│   ├── CLAUDE.md           # AI development notes
│   ├── README.md           # Project documentation
│   ├── config.sample.ini   # Sample config file template
│   ├── deezer notes.txt    # Deezer API notes
│   └── schema.sql          # Database schema
├── images/                 # Deezer logo assets
├── includes/
│   ├── config.php          # Config loader
│   ├── db.php              # Database connection
│   └── helpers.php         # Shared utilities (h(), jsonResponse(), etc.)
├── .htaccess               # Directory index and security rules
├── copyright.php           # License/attribution page
├── default.php             # Public signup page
├── signage.php             # Queue display for TVs
└── signup-display.php      # Full-screen QR display
```

## Key Patterns

### API Authentication
Admin endpoints support two authentication methods:

**Session-based (preferred for browser requests):**
- Admin pages call `startAdminSession()` which starts a PHP session with secure cookie settings
- Session is automatically authenticated because admin directory is protected by HTTP Basic Auth
- CSRF token is generated and stored in session (`$_SESSION['csrf_token']`)
- API requests include `csrf_token` in JSON body: `{ "csrf_token": "...", "action": "..." }`
- Server validates both session authentication and CSRF token match

**Token-based (for curl/scripts):**
1. JSON body: `{ "admin_token": "..." }`
2. Header: `X-Admin-Token: ...`
3. Query param: `?admin_token=...` (legacy)

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

### Content Filtering
Optional performer name filtering powered by Sightengine. Requires API credentials in config:
```ini
[sightengine]
api_user = "your_api_user"
api_secret = "your_api_secret"
```

**Configurable filters** (via Admin Settings):

Profanity filters (4 levels each: off, low, medium, high):
- Sexual language
- Discriminatory language (slurs, hate speech)
- Insults
- Other inappropriate language
- Symbol substitution / grawlix (@#$%!)

Other content blocks (on/off toggles):
- Extremism content
- Violence / self-harm
- Drugs / medicines

**Fail-open design**: If the Sightengine API is unavailable or returns an error, signups proceed without filtering. This prevents API outages from blocking legitimate users.

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

**Troubleshooting**: If the pull fails due to local changes (e.g., cPanel modifying `.htaccess`), you may need to delete the repo in cPanel and re-add it. The cPanel Git interface doesn't provide an easy way to discard local changes.

## Security Features

### Implemented (Jan 2026 audit)
- **CSRF protection** - Session-based authentication with CSRF tokens for admin API requests
- **Admin token hidden from JavaScript** - Browser requests use session auth; token only for programmatic access
- **Sanitized error messages** - Full details logged server-side; generic messages returned to clients
- **Race condition prevention** - Unique constraint `(event_id, position)` on entries table with retry logic
- **UUID generation fix** - UUIDs generated before INSERT to prevent race conditions
- **Image validation** - `validateImageData()` checks MIME type and parseability for album art
- **Input validation** - Performer name length limited to 150 characters
- **Songs pagination** - Entries API limits songs to 500 per request with offset/search support

### Accepted Risks (documented)
- **No rate limiting** - Human interaction patterns don't hit API limits; issues are recoverable
- **No security headers** - Admin protected by HTTP Basic Auth; CSP complex with CDN dependencies
- **SSRF in album art** - Requires admin access, response must be valid image, no content leaked

## UI Conventions

- Busy overlay during async operations (`.busy-overlay` + `.show`/`.visible`)
- Bootstrap toasts or alerts for user feedback
- Icons from Bootstrap Icons library
- Responsive breakpoints via Bootstrap grid

## License

GPL v3 - All source files have license header comments
