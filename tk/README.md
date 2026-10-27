# Tilkit

**Tilkit** is a web platform for **institutional field data collection and analysis**. It helps teams design collection tools, capture structured answers in the field, validate and govern data, and report with dashboards and exports-aligned with Kenya Data Protection Act (DPA) style consent and role-based access.

The primary product in this repository is the **TILKIT Weather Indicator Log**: a digital implementation of the **v4 community weather workbook** (124 columns, Community Data List sections A–E, seasonal tercile tally, and eligibility/weighting rules).

---

## What the platform does

| Area | Description |
|------|-------------|
| **Weather log home** | Overview of workbook rows, sites, seasons, indicators, and quick links into capture and analysis. |
| **Capture** | Create indicator records (site, season, unique sign ID), full workbook grid, workshop wizard (A–E), field sightings with GPS and photos. |
| **Records** | Browse, search, and edit rows; validation and sign-off columns (confirm, approve, verify). |
| **Analysis** | Volume, validation breakdown, maps, and **Deep Analysis**-custom dashboards, Explore notebook, and slicers by program, site, and season. |
| **Tally** | Seasonal tercile aggregation with v4 weighting and eligibility gates; publish snapshots for reporting. |
| **Data desk** | Queues for validation, climate-weight review, and operational follow-up. |
| **Setup** | Programs, monitoring seasons (month ranges), sites, team roles, and indicator code legend (reference). |
| **Exports** | Governed CSV, JSON, and Excel aligned to workbook column order; consent and do-no-harm rules apply. |

**TILKIT** (Traditional Indigenous and Local Knowledge Tool) turns community-trusted weather signs-tree flowering, wind, animal behaviour, and similar-into a **shared seasonal rainfall forecast** (tercile: above normal, normal, below normal), with traceability from workshop capture through field sighting to official comparison (e.g. meteorological outlook and drought monitoring feeds where configured).

---

## Architecture (this app)

- **Stack:** Laravel, Blade, Vite, Tailwind CSS, Alpine.js; Plotly and simple-statistics for Deep Analysis charts.
- **Domain:** `app/Models/Tilkit/*`, `app/Services/Tilkit/*`, column contract in `config/tilkit_weather_column_register.json`.
- **Single-tool mode (default):** One embedded weather log per deployment (`TILKIT_SINGLE_TOOL=true`); communities are modeled as **programs, sites, and seasons**, not separate tools.
- **Workbook contract:** Field definitions and derived rules live in **config and code** (parity-tested against the v4 master template), not ad-hoc form builder rows.

Developer-oriented module notes, commands, and alignment checklist: **[docs/tilkit/README.md](docs/tilkit/README.md)**.

---

## Local development

Requirements: PHP 8.2+, Composer, Node.js 20+, SQLite or MySQL.

```bash
cd tk
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`. Register a user; in single-tool mode the weather log is provisioned on sign-in.

**Optional demo data (Kenya + Ghana pilot rows, sites, seasons, tallies):**

```bash
php artisan db:seed --class=KenyaGhanaPilotSeeder
```

**Workbook maintenance:**

```bash
php artisan tilkit:import-legend          # indicator code legend from master xlsx (when file is present)
php artisan tilkit:ensure-platform-tool   # ensure embedded weather tool exists after deploy
php artisan tilkit:tally {site_id} {season_id} [--publish]
```

Run tests:

```bash
php artisan test
```

---

## Specification sources (repo)

| Location | Role |
|----------|------|
| `tilkit_docs/` | Developer brief, master template v4 xlsx, raw vs derived register |
| `config/tilkit_weather_column_register.json` | 124-column register (raw + derived) |
| `tests/Fixtures/Tilkit/` | Golden workbook cases for engine parity |

---

## License

Application code in this tree follows the project license. Laravel framework components remain under the [MIT license](https://opensource.org/licenses/MIT) where applicable.
