# Rockband Scheduler

A web application for managing Rock Band performance sign-ups at events. Users scan a QR code to access an event page where they can claim performance slots and select songs from a pre-populated library.

## Features

- **Event Management**: Create events with start/end times and configurable number of performance slots
- **QR Code Access**: Generate QR codes for easy event access via mobile devices
- **Song Library**: Manage songs with Deezer integration for metadata and audio previews
- **Mobile-First Design**: Optimized for phones and tablets (primary access method)
- **Admin Panel**: Protected admin interface for managing events, songs, and entries
- **Time-Restricted Access**: Events are only accessible during their scheduled window

## Requirements

- PHP 7.4+
- MySQL 5.7+ (with UUID support)
- Web server (Apache with mod_rewrite, or nginx)

## Installation

1. Clone/upload files to your web server
2. Create a MySQL database and import the schema
3. Copy the config template and configure:
   ```
   cp config/rockband_scheduler_config.ini.example config/rockband_scheduler_config.ini
   ```
4. Edit the config file with your database credentials and admin token
5. Set up HTTP Basic Auth on the `/admin` directory (via .htaccess or server config)

## Configuration

The config file should be placed outside the web root at `../../config/rockband_scheduler_config.ini` relative to the project root:

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
```

Generate a secure admin token:
```bash
openssl rand -hex 32
```

## External Dependencies

This application relies on two external services. Understanding these dependencies is important for production deployment.

### 1. Deezer API

**Used for**: Song search, metadata auto-fill, album art URLs, audio preview URLs

**Endpoints used**:
- `https://api.deezer.com/search/track` - Search for songs
- `https://api.deezer.com/album/{id}` - Get album details (release year)

**If Deezer becomes unavailable**:
- Song search in the admin panel will fail
- Auto-fill of song metadata will not work
- **Workaround**: Songs can still be added manually by filling in all fields
- Existing songs in the database are unaffected
- Audio previews for existing songs will stop working (preview_url points to Deezer CDN)
- Album art for existing songs will stop displaying (album_art_url points to Deezer CDN)

**If Deezer API changes**:
- The proxy at `/api/deezer.php` would need to be updated to match new response formats
- Fields used: `id`, `title`, `duration`, `preview`, `artist.name`, `album.id`, `album.title`, `album.cover_medium`, `release_date`

**Rate limits**: Deezer allows 50 requests per 5 seconds. The app makes ~2 API calls per song added (search + album lookup). Normal usage should stay well under limits.

**Mitigation options**:
- Store album art as binary blobs in the database (currently supported via `album_art` column)
- Download and store preview MP3s locally (would require additional storage)
- Use alternative music APIs (MusicBrainz, Last.fm, Spotify)

### 2. QR Server API

**Used for**: Generating QR code images for events

**Endpoint used**:
- `https://api.qrserver.com/v1/create-qr-code/`

**If QR Server becomes unavailable**:
- "Generate QR Code" button will fail with a 500 error
- Existing QR codes stored in the database will continue to work
- Events remain fully functional; only QR generation is affected

**If QR Server API changes**:
- The `generateQrCode()` function in `/api/events.php` would need updating
- Current parameters: `size=300x300&data={url}`

**Mitigation options**:
- Install a PHP QR code library locally:
  - `chillerlan/php-qrcode` (lightweight, SVG/PNG output, no external dependencies)
  - `endroid/qr-code` (full-featured, requires GD or Imagick)
  - Install via Composer locally, upload `vendor/` folder to host
- Use alternative QR APIs (Google Charts is deprecated, but others exist)
- Pre-generate QR codes and store as static images

## File Structure

```
/
├── admin/                  # Admin panel (requires HTTP Basic Auth)
│   ├── default.php         # Dashboard
│   ├── events.php          # Event management
│   ├── songs.php           # Song library management
│   └── entries.php         # Per-event entry management
├── api/                    # JSON API endpoints
│   ├── events.php          # Events CRUD (admin only)
│   ├── songs.php           # Songs CRUD (admin only)
│   ├── entries.php         # Entries (mixed: public read/create, admin edit)
│   └── deezer.php          # Deezer search proxy (public)
├── includes/
│   └── helpers.php         # Shared utility functions
├── config.php              # Configuration loader
├── db.php                  # Database connection
├── default.php             # User-facing event page
└── .htaccess               # URL rewriting rules
```

## API Authentication

Admin API endpoints require authentication via one of:
- `X-Admin-Token` header
- `admin_token` query parameter

The token must match the value in your config file.

## Database Schema

The application uses three main tables:
- `events` - Event definitions with UUID primary keys
- `songs` - Song library with Deezer metadata
- `entries` - Performance slot assignments linking events to songs

UUIDs are stored as binary(16) and converted using MySQL's `UUID_TO_BIN()` and `BIN_TO_UUID()` functions.

## License

MIT
