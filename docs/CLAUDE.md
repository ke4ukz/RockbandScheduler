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

## Pending Tasks

### Security Improvements (from Jan 2026 audit)

**Completed:**
- [x] **Add CSRF protection** - Implemented session-based authentication with CSRF tokens. Admin pages call `startAdminSession()` to create a secure session, and all API requests include `csrf_token` in the JSON body. Server validates both session auth and CSRF token.
- [x] **Hide admin token from JavaScript** - Admin pages no longer embed `ADMIN_TOKEN` in page source. Instead, they use `CSRF_TOKEN` which is tied to the PHP session. The actual admin token is only used for programmatic access (curl/scripts) and is never exposed to browsers.
- [x] **Sanitize error messages** - All API endpoints now log full error details via `error_log()` but return generic messages to clients. No database structure, file paths, or raw exception messages are exposed. Common errors (data too long, duplicate entry, encoding issues) get helpful but safe messages.
- [x] **Remove debug logging** - Removed `error_log("loading db")` from `db.php`.

**Deferred (Won't Fix):**
- [ ] **Rate limiting** - Public signup and Deezer proxy have no rate limits. Risk is low: human interaction won't hit Deezer's 50/5sec limit, and any issues are temporary/recoverable (slots can be cleared, Deezer bans are brief). Complexity cost outweighs benefit for this use case.
- [ ] **Security headers** - Headers like X-Frame-Options, X-Content-Type-Options, CSP provide minimal value here: admin is already protected by HTTP Basic Auth, CSRF protection is in place, no sensitive data exposed to clickjacking. CSP would be complex to configure with Bootstrap CDN and inline scripts.

### Code Quality

- [x] **Fix db.php config key mismatch** - Updated db.php to use `host`, `name`, `user`, `pass` to match config.sample.ini. Also fixed DSN string that had `host=localhost` hardcoded instead of using the config value.

### Known Issues (from Jan 2026 audit)

**High Priority:**
- [ ] **Race condition in slot assignment** (`api/entries.php:219-248`) - Two users submitting simultaneously could claim the same slot. The code queries for available positions, then inserts, but another request could take that position in between. **Fix**: Add unique constraint `(event_id, position)` to the entries table and handle duplicate key errors with retry logic.
- [ ] **Event UUID retrieval uses name lookup** (`api/events.php:209-219`) - After creating an event, the code retrieves its UUID by matching on name. If two events with identical names are created simultaneously, the wrong UUID could be returned. **Fix**: Generate UUID in PHP before insert, or use `LAST_INSERT_ID()` approach.

**Medium Priority:**
- [ ] **SSRF in album art fetching** (`api/songs.php:297-346`) - `fetchImageAsBlob()` accepts user-provided URLs without blocking internal/private IP ranges. An attacker could probe internal network services. **Fix**: Validate URL scheme is http/https and block private IPs (10.x, 172.16-31.x, 192.168.x, 169.254.x, localhost).
- [ ] **Base64 album art not validated** (`api/songs.php:216-222`) - Manual base64 uploads aren't checked to be actual images before storing. **Fix**: Apply same MIME type validation as `fetchImageAsBlob()`.

**Low Priority:**
- [ ] **Song selection count not decremented** - When an entry's song is changed via admin edit, the new song's `selection_count` is incremented but the old song's count is not decremented. Stats may be slightly inflated over time.
- [ ] **Missing performer_name length validation** - Name is trimmed but not length-checked before insert. Database will error on overflow (150 chars) but explicit validation would give better error messages.
- [ ] **Songs list not paginated in entries API** - `listEntries()` fetches all songs for the selection dropdown. With thousands of songs, this could cause performance issues.
- [ ] **Admin entry creation response inconsistent** - Public signup returns `position` in response; admin creation does not.

## UI Conventions

- Busy overlay during async operations (`.busy-overlay` + `.show`/`.visible`)
- Bootstrap toasts or alerts for user feedback
- Icons from Bootstrap Icons library
- Responsive breakpoints via Bootstrap grid

## License

GPL v3 - All source files have license header comments
