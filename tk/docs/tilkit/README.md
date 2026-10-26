# TILKIT weather module (in Tilkit platform)

This Laravel app (`tk/`) is being aligned with the **TILKIT Weather Indicator Log v4** specification in `tilkit_docs/`.

## Spec sources (repo root)

| Document | Role |
|----------|------|
| `tilkit_docs/TILKIT - The Concept... Developer Brief.docx` | Domain model, engine rules, build order |
| `tilkit_docs/TILKIT_Weather_Indicator_Log_Master_Template_v4_OND2026.xlsx` | Acceptance tests & column contract |
| `tilkit_docs/TILKIT_v4_Raw_vs_Derived_Data_Register_1.xlsx` | Raw vs derived field inventory |
| `tilkit_docs/_extracted.txt` | Searchable text export of Word docs |

## Code layout

| Path | Purpose |
|------|---------|
| `config/tilkit.php` → `weather` | Protocol version, paths, engine flags |
| `app/Models/Tilkit/*` | Sites, seasons, indicators, records, sightings, workbook values/media |
| `config/tilkit_weather_column_register.json` | 124-column v4 register (90 raw + 34 derived) |
| `app/Services/Tilkit/WorkbookRecordService.php` | Persist raw cells by column letter; sync canonical fields |
| `app/Services/Tilkit/*` | Rules engine (gate, weights, tally - phased) |
| `app/Enums/Tilkit/*` | Tercile, ratings, validation status |
| `tests/Fixtures/Tilkit/` | Golden cases ported from the v4 demo workbook |
| `config/tilkit_workbook_sheets.json` | Titles for all 12 master-template sheets |
| `config/tilkit_community_data_list.json` | Community Data List sections A–E (40 items) |

### Workbook commands

```bash
php artisan tilkit:import-legend   # master template xlsx → indicator codes
php artisan tilkit:import-demo {tool_id}
php artisan tilkit:seed-kenya-ghana --force   # owner demo: Kenya MAM+OND, Ghana north/south seasons
php artisan tilkit:tally {site_id} {season_id} [--publish]
```

**Kenya/Ghana pilot seed** (`KenyaGhanaPilotSeeder`): wipes weather-tool rows (keeps users), then loads **32 records** — Kenya **MAM 2026** + **OND 2026** (10 signs × 2 seasons), Ghana **northern single rainy season**, **southern AMJ major** + **SON minor** (Cape Coast signs in both). Sets season date ranges, per-season integration settings, photos/sightings, derived engine columns, and published tally snapshots for each site·season pair.

Web: `tools/{tool}/tilkit` (records), `.../tilkit/setup`, `.../tilkit/tally`, `.../tilkit/workbook`, `.../tilkit/calendar`.

## Alignment phases

Track detailed status in **[alignment-audit.md](./alignment-audit.md)** (living checklist tied to [GitHub issue epics](../../../.github/tilkit-project/issues.json)).

1. **Phase 0** - Spec baseline, open questions, golden fixtures  
2. **Phase 1** - Schema, reference data, workbook capture UI  
3. **Phase 2** - Forecast engine with Excel parity tests  
4. **Phase 3–6** - Mobile sync, workshop/validation, integration & v4 modules, migration & pilots  

### Single-tool deployment (default)

- **`TILKIT_SINGLE_TOOL=true`** (default) - one embedded `dc_projects` row (`TILKIT_TOOL_CODE`, default `tilkit-weather`) is auto-provisioned on sign-in; users cannot create extra tools. Use **Setup → sites & seasons** for each community or study-not a new tool per location. Run `php artisan tilkit:ensure-platform-tool` after deploy if needed.
- **Workbook “data points”** (124 columns, Community Data List) live in **`config/*.json`**, not as seeded form fields or demo indicator rows.
- **Operational data** (sites, seasons, indicator records) is entered in the app or imported deliberately; **`tilkit:import-demo`** is for PHPUnit and manual parity checks only.
- Optional reference: **`tilkit:import-legend`** loads the code legend from the master xlsx when you need DB-backed UIDs.

Sites and seasons are configured per deployment under that single tool (setup UI).

**In-app flow:** Weather log subnav covers **Capture → Records → Analysis → Tally → Data desk → Setup → Team**. Generic form builder / submission analytics redirect here; workbook CSV/JSON/xlsx exports live on the weather log home.
