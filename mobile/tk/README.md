# TILKIT Field (Flutter)

Offline-first Android field app for **Community Data List section B - With every sighting**, aligned with `tilkit_docs/` (Developer Brief §8, sighting-first Phase 3) and `tk/docs/tilkit/alignment-audit.md`.

## What it does

- Sign in to the Tilkit Laravel platform (session cookie).
- Download indicator **records** (one indicator × site × season) via `GET /api/tilkit/mobile/bootstrap`.
- Cache catalog in **SQLite** for offline use.
- Capture sightings locally (date, place, landmark, observer, GPS, photos on device).
- **Sync queue** uploads pending rows with `POST /api/tilkit/sightings` (idempotent client UUIDs).

Photos are stored locally only until a media upload API exists (Brief: Wi‑Fi-only media sync later).

## Requirements

- Flutter SDK 3.12+ (`flutter doctor`)
- Tilkit backend running (`php artisan serve` from `tk/`)
- User account with **verified email** and access to the weather tool

## Server URL

| Environment | URL |
|-------------|-----|
| Android emulator → host machine | `http://10.0.2.2:8000` |
| Physical device (same LAN) | `http://<your-pc-ip>:8000` |
| Production | `https://your-host` |

Cleartext HTTP is enabled in debug Android builds for local dev only.

## Run

```bash
cd mobile/tk
flutter pub get
flutter run
```

## API (backend)

| Method | Path | Notes |
|--------|------|--------|
| POST | `/api/tilkit/mobile/login` | JSON `{ email, password }`, sets session |
| GET | `/api/tilkit/mobile/bootstrap` | Programs, sites, seasons, records (auth) |
| POST | `/api/tilkit/sightings` | Sync one sighting (auth) |

## Offline behaviour

1. Bootstrap when online → SQLite `catalog_cache`.
2. New sighting → `sighting_drafts` with `sync_status=pending`.
3. Tap cloud icon when online → retries failed rows; stops on first error.

## Not in v1

- Workshop views (Community Data List A/C/D)
- Audio/video upload, SMS fallback
- Multi-program UI (uses server active program; pass `?program_id=` on bootstrap when extended)

## Docs

- `tilkit_docs/_extracted.txt` - mobile/offline/sighting-first requirements
- `tk/docs/tilkit/alignment-audit.md` - Phase 3 mobile scope
