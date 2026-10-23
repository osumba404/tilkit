# TILKIT v4 alignment audit (living checklist)

Cross-check between **spec documents** (`tilkit_docs/`, Developer Brief in `_extracted.txt`) and the **Laravel platform** (`tk/`). Update this file when behaviour or scope changes; tie completion to GitHub issues created from [`.github/tilkit-project/issues.json`](../../../.github/tilkit-project/issues.json).

**Related docs:** [README](./README.md) · [Open questions](./open-questions.md) · [Fixtures README](../../tests/Fixtures/Tilkit/README.md)

**Last reviewed:** 2026-10-24 (personas, climate review, xlsx import/export, integration fields)

---

## v4 workbook — 12 sheets ↔ platform

Source files: `TILKIT_Weather_Indicator_Log_Master_Template_v4_OND2026.xlsx` and `TILKIT_Weather_Indicator_Log_v4_DEMO_hypothetical_data.xlsx`. Catalog: `config/tilkit_workbook_sheets.json`.

| Sheet | Platform |
|-------|----------|
| How to Use | `tools/{tool}/tilkit/workbook` — workbook guides view |
| Community Data List | `config/tilkit_community_data_list.json` + guides + A–E wizard (`record-wizard.blade.php`) |
| Sheet 1 — Indicator Log | 124-column register, `WorkbookRecordService`, `ToolTilkitWeatherController`, derived via `TilkitRecordEngine` |
| Seasonal Tercile Tally | `TallyEngine`, `tools/{tool}/tilkit/tally`, `tilkit:tally`, optional snapshot publish |
| Seasonal Indicator Calendar | `tools/{tool}/tilkit/calendar` (CI–CP columns) |
| Indicator Code Legend | `tilkit_indicator_codes`; `php artisan tilkit:import-legend` from master xlsx |
| Prior Multipliers | `tilkit_indicator_codes.prior_multiplier`; partial vs full matrix sheet |
| Weighting Options | `TilkitSeasonSettings`, `ConfidenceScoreCalculator`, season settings UI |
| Barriers Addressed | Reference only in guides (no separate module) |
| Framework Crosswalk | Reference only in guides |
| References | Reference only in guides |
| Change Log v4 | Spec traceability in `tilkit_docs/`; row-level xlsx import via `tilkit:import-workbook`; full v3→v4 transform rules not automated |

**Demo data:** `tests/Fixtures/Tilkit/demo_workbook_full.json` + `php artisan tilkit:import-demo {tool}`; parity test Mutirithia OND 2026.

---

## How to use this checklist

| Symbol | Meaning |
|--------|---------|
| `[x]` | Done and matches spec intent (may still need parity tests) |
| `[~]` | Partial — implemented but incomplete, drift risk, or untested vs workbook |
| `[ ]` | Not started or not acceptable for pilot cut-over |

**Epic column:** matches issue title prefix in `issues.json` (run `create-github-project.ps1` or create issues manually).

**When to update:** after migrations, engine changes, new UI flows, fixture ports, or decisions in `open-questions.md`.

---

## Cross-cutting risks (track until closed)

| Risk | Status | Mitigation | Epic / issue |
|------|--------|------------|----------------|
| **Two capture paths** — native TILKIT module vs generic form builder | `[x]` | Legacy `WeatherIndicatorLogToolSeeder` / field catalog removed (2026-10-23); use `tools/{tool}/tilkit` only | — |
| **Data saved without certified tally** — full workbook capture without Phase 2 engine | `[~]` | Engine + demo parity test green; extend golden suite (6-sign, full exclusion matrix) before pilot sign-off | Phase 2, Phase 5 |
| **Canonical vs workbook drift** — `tilkit_indicator_records` vs `tilkit_workbook_values` | `[~]` | Single write path via `WorkbookRecordService`; expand sync or drop duplicate columns | Phase 1 |
| **Open product decisions** change engine math | `[ ]` | Resolve `open-questions.md` before tally finalisation | Phase 0 |

---

## Phase 0 — Foundation & spec baseline

**Epic:** `[Epic] Phase 0 — Foundation & spec baseline` · Milestone: *Phase 0 - Foundation*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| v4 protocol constant | Developer Brief §11.1 | `config/tilkit.php` → `weather.protocol_version` | `[x]` |
| Module layout in `tk/` | Brief build plan | `app/Models/Tilkit/*`, `app/Services/Tilkit/*`, `docs/tilkit/` | `[x]` |
| 124-column register (90 raw + 34 derived) | Raw vs Derived Register | `config/tilkit_weather_column_register.json`; `TilkitWorkbookCaptureTest` | `[x]` |
| Open questions documented | Brief §11.4 | `docs/tilkit/open-questions.md` | `[~]` doc exists; decisions mostly open |
| Pilot priority (SCLoWFER vs Green Schools) | Brief §11.4 #9 | — | `[ ]` |
| Signed-off spec version (xlsx + Change Log) | Phase 0 epic | Docs in `tilkit_docs/`; no formal sign-off recorded | `[ ]` |
| Golden fixtures from demo workbook | Brief §10.2 | `gate_eligible_frog.json`, `demo_workbook_full.json`, `TilkitDemoTallyParityTest` | `[~]` |

**Child issues**

| Issue title | Checklist hook |
|-------------|----------------|
| Phase 0: Document open questions and pilot priority | Resolve rows in `open-questions.md`; mark pilot in this file |
| Phase 0: Extract PHPUnit golden fixtures from v4 demo workbook | Frog trace, 11-row demo, 6-sign OND 2026 under `tests/Fixtures/Tilkit/` |

---

## Phase 1 — Reference data & schema

**Epic:** `[Epic] Phase 1 — Reference data & schema` · Milestone: *Phase 1 - Schema*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| Season, Site, IndicatorCode, Indicator, IndicatorRecord | Brief §2, §4.2 | Migrations `2026_10_23_120000_*`; models under `app/Models/Tilkit/` | `[x]` |
| Sighting as child of record | Brief §4 updated entity model | `tilkit_sightings` + `TilkitSighting` | `[x]` schema only |
| Evidence media (photos) | Column Y | `tilkit_workbook_media`; upload in `ToolTilkitWeatherController` | `[~]` |
| Season settings (Weighting Options) | Brief §6.6, v4 tab | `TilkitSeasonSettings` + admin season settings UI | `[~]` hazard-by-BU not fully wired |
| Unique `(indicator_id, site_id, season_id)` | Brief §6 data rules | Migration unique index; `TilkitWeatherSchemaTest` | `[x]` |
| Workbook cell storage (not 124 columns on one row) | Brief §4.2 | `tilkit_workbook_values` + `WorkbookRecordService` | `[x]` |
| Raw capture UI (all 90 raw fields) | Register stages | `record-form.blade.php`, wizard A–E, `partials/sighting-form.blade.php` (section B) | `[~]` sighting-first partial; not full mobile |
| Derived columns not client-writable | Brief §5 personas | `assertRawColumn()`; read-only section on edit | `[x]` |
| Reference columns AH–AM from relations | Indicator Log | `storeReferenceColumns()` (AH–AI, AJ–AL ward/county/country, AM) | `[x]` |
| Seed code legend + prior multipliers from v4 xlsx | Phase 1 epic | `tilkit:import-legend` (157 codes from master) | `[~]` prior matrix import partial |
| RBAC by provider (observer, ITK, data desk, …) | Brief §5, Register | `TilkitPersona` + `TilkitPersonaService`; guards on data desk, sightings, publish, AC/AD/AE | `[~]` persona column on `tool_members`; no invite UI |
| Admin CRUD: sites, seasons, codes | Phase 1 epic | `ToolTilkitAdminController` setup + data desk | `[~]` codes via import, not full CRUD |
| Organisation entity | Phase 1 epic deliverable | Uses `dc_projects` as tool host | `[~]` intentional platform pattern |

**Child issues**

| Issue title | Checklist hook |
|-------------|----------------|
| Phase 1: Core TILKIT migrations and models | Rows above through unique constraint |
| Phase 1: Seed indicator code legend and prior multipliers | Seed from master template xlsx |
| Phase 1: Admin UI for sites, seasons, and code legend | Admin CRUD rows |

---

## Phase 2 — Forecast rules engine (Excel parity)

**Epic:** `[Epic] Phase 2 — Forecast rules engine (Excel parity)` · Milestone: *Phase 2 - Engine*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| Gate: UID, sighted, confirmed, approved | Brief §4.3 gate | `EligibilityGate` | `[x]` |
| Gate: contested excluded; tercile 1/2/3 | Brief §4.3 | `ValidationStatus::excludedFromTally()`; `TercileForecast::isForecast()` | `[x]` |
| Gate: climate weight review pending (CP STRONG) | Brief §6.5 | `ClimateWeightReviewService` + `EligibilityGate`; data desk sign-off | `[x]` |
| Gate: one exclusion reason per row | Brief §10.3 | `GateResult` → **CE** on recalc | `[x]` |
| Gate: duplicate / first-record (**DT**) | v4 Change Log | Derived on recalc; DB unique on insert | `[~]` |
| Gate: site/season scope in tally context | Brief §4.3 | `TilkitEngineService::runTally` scoped | `[x]` |
| Persist **CE** tally status (derived) | v4 single source of truth | `TilkitRecordEngine` + `storeDerivedValues` | `[x]` |
| Weighting phase switch (≥3 scored seasons) | Brief §4.3 | `WeightingService` + `IndicatorHistoryService` | `[~]` needs scored-season fixtures |
| Corroboration cap (base weight max 2) | Brief §4.3 | `WeightingService` | `[x]` |
| Final weight = base × decision relevance | Brief §4.3 | `WeightingService` / record engine | `[x]` |
| Optional literature prior (rating phase only) | Brief §4.3 | Season setting flag + code prior | `[~]` |
| Simple / weighted / priority-adjusted tallies | Brief §10.2 table | `TallyEngine` | `[x]` |
| Reconciliation: counted rows = tally total | Brief §10.3 | `TallyResult::reconciliationOk` | `[x]` |
| Ties reported, not silently resolved | Brief §4.3 | Tally exposes tie flag | `[~]` |
| Post-season Hit / Miss / False Start | Brief lifecycle | `SeasonOutcome` enum; no scoring loop | `[ ]` |
| CLI `tilkit:tally {site} {season}` | Phase 2 epic | `TilkitTallyCommand` | `[x]` |
| Frog trace fixture parity | Brief §10.2 | `TilkitEngineTest` final weight 6 | `[x]` |
| 11-row demo (duplicate, contested, …) | Brief §10.2 | `TilkitDemoTallyParityTest` (8 counted after climate review sign-off) | `[x]` |
| 6-sign OND 2026 (50/17/33 vs 55/14/31 vs 58/10/32) | Brief §10.2 | `TilkitSixSignParityTest` + `six_sign_ond2026.json` | `[x]` |
| Confidence score **not** in tally weight | Brief §10.3, §6.6 | **DQ** separate from tally weights | `[x]` |
| Rating 0 excluded from averages / stability | Brief §10.3 | WeightingService rules | `[~]` |

**Child issues**

| Issue title | Checklist hook |
|-------------|----------------|
| Phase 2: Implement eligibility gate service | CE/DT persistence + full reason set |
| Phase 2: Implement weighting and phase switch (3 scored seasons) | Weighting rows |
| Phase 2: Implement tally engine (simple / weighted / priority-adjusted) | Tally + reconciliation rows |
| Phase 2: Golden tests — Excel parity suite | All fixture rows |

---

## Phase 3 — Sighting-first mobile + sync

**Epic:** `[Epic] Phase 3 — Sighting-first mobile + sync` · Milestone: *Phase 3 - Mobile*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| 7 sighting columns as async child events (N, O, CI, Y, Z, AA, AB) | Brief §5, §6 | `TilkitSighting` + sighting form partial; `POST /api/tilkit/sightings` | `[~]` |
| Update record, never insert duplicate row for new sighting | Demo duplicate frog | Unique constraint only | `[~]` |
| Offline-first mobile (Android) | Brief §8 | — | `[ ]` |
| Sync API (UUID, idempotent, media) | Phase 3 epic | `Api\TilkitSyncController` (sightings); full sync/conflicts | `[~]` |
| Conflict queue (approvals / consent) | Phase 3 epic | — | `[ ]` |
| Optional SMS `SIGHT` adapter | Brief narrative | — | `[ ]` |

**Child issues:** Phase 3: TILKIT sync API and media upload · Phase 3: Offline mobile MVP (Android-first)

---

## Phase 4 — Workshop & validation (web)

**Epic:** `[Epic] Phase 4 — Workshop & validation (web)` · Milestone: *Phase 4 - Workshop*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| Community Data List UX (not one Excel grid) | Brief §10, §11.1 | A–E wizard routes + community list registry | `[~]` |
| Workshop mode (facilitator, resumable) | Phase 4 epic | — | `[ ]` |
| Validation chain: confirm / approve / verify | Brief lifecycle | Workbook AC/AD/AE + `TilkitSignOffGuard` | `[~]` |
| Separation confirm vs approve (same person?) | `open-questions.md` #5 | `TILKIT_SEPARATE_CONFIRM_APPROVE` + user id columns | `[~]` |
| Pending-validation queue | Brief §6.5 | `DataDeskService` queues on data desk view | `[~]` |
| Duplicate review queue | Brief §11.3 | — | `[ ]` |
| Controlled dropdowns (AP, U, M, V, …) | xlsx validation lists | AP uses Excel labels via `ValidationStatus::excelLabel()` | `[~]` |
| Demographics BG–BK | Brief equity | Form includes via register | `[~]` |
| Persona-specific views (observer vs data desk) | Register `provided_by` | `TilkitPersonaService` route guards | `[~]` |

**Child issues:** Phase 4: Workshop capture UX · Phase 4: Validation chain and data desk queues

---

## Phase 5 — Integration, v4 modules, exports

**Epic:** `[Epic] Phase 5 — Integration, v4 modules, exports` · Milestone: *Phase 5 - Integration*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| Seasonal Tercile Tally dashboard (site + season) | Brief §7 web app | Admin tally view + CLI | `[~]` |
| Immutable forecast snapshots | Brief §10.3, §11.3 | `tilkit_tally_snapshots` on publish | `[~]` |
| KMSA / KMD outlook ingest + agreement | Columns DJ–DL | Season settings + `V4FieldDeriver` / `TallyIntegrationService` | `[~]` manual settings only |
| NDMA phase / VCI3M | Brief §6.2, §6.5 | `TilkitSeasonSettings` (DL, CR) | `[~]` |
| Calendar & climate-change flag (CI–CP) | Brief §6.1 | `V4FieldDeriver::climateChangeFlag`; calendar UI | `[~]` |
| Vegetation index CQ–CT | Brief §6.2 | `V4FieldDeriver` CS/CT on recalc | `[~]` |
| Anticipatory action triggers CU–CZ | Brief §6.3 | `TallyIntegrationService` trigger hints | `[~]` |
| Dissemination effectiveness DA–DI | Brief §6.4 | — | `[ ]` |
| Residual risk DM–DP | Brief §6.5 | — | `[ ]` |
| Confidence & hazard priority (DQ–DS) | Weighting Options tab | DQ on record; DR partial | `[~]` |
| Consensus forecast / integration session (BF, …) | Brief §7 | `tilkit_consensus_forecasts` + tally publish form | `[~]` |
| Consent BC enforced on every export | Brief §9 | `TilkitConsentGate` on CSV/JSON export | `[~]` |
| Do-no-harm BB blocks dissemination | Brief §9 | Consent gate skips BB &lt; 2 | `[~]` |
| PDF / CSV / JSON exports (column order contractual) | Brief §10.1 | CSV/JSON routes + `WorkbookXlsxExporter` / `tilkit:export-workbook` | `[~]` |

**Child issues:** Phase 5: Official outlook ingest adapter · Phase 5: Tally dashboard and consensus forecast publication · Phase 5: Consent-aware exports (PDF, CSV, JSON)

---

## Phase 6 — Migration, pilots, M&E

**Epic:** `[Epic] Phase 6 — Migration, pilots, M&E` · Milestone: *Phase 6 - Pilots*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| v3/v4 Excel importers + legacy transforms | Brief §10.1 | `WorkbookXlsxReader` + `tilkit:import-workbook` | `[~]` |
| Import audit vs Change Log (47 entries) | Brief §10.1 | — | `[ ]` |
| Regenerate xlsx export in column order A–DT | Brief §10.1 | `WorkbookXlsxExporter`, web `tilkit/export.xlsx` | `[~]` |
| Engine regression in CI on every change | Phase 6 epic | 18 PHPUnit TILKIT tests (parity, six-sign, climate gate) | `[~]` |
| SCLoWFER Ghana sites configured | Phase 6 issue | — | `[ ]` |
| Green Schools Kenya configured | Phase 6 issue | — | `[ ]` |
| M&E aligned to pilot logframe | Annex 1 proposal | — | `[ ]` |

**Child issues:** Phase 6: Excel workbook importers · Phase 6: Configure SCLoWFER Ghana · Phase 6: Configure Green Schools Kenya

---

## Spec ↔ code quick map

| Spec concept | Primary code |
|--------------|--------------|
| Column contract | `config/tilkit_weather_column_register.json`, `WorkbookColumnRegistry` |
| One row per indicator × site × season | `tilkit_indicator_records`, unique index |
| Raw cell writes | `WorkbookRecordService::storeRawValues` |
| Derived cell writes | `WorkbookRecordService::storeDerivedValues` (engine only) |
| Eligibility + derived row | `EligibilityGate`, `TilkitRecordEngine`, `TilkitEngineService` |
| Tally | `TallyEngine`, `TilkitEngineService::runTally` |
| Admin / tally UI | `ToolTilkitAdminController` |
| Workbook sheets + community list | `WorkbookSheetCatalog`, `CommunityDataListRegistry`, JSON configs |
| Web entry | `ToolTilkitWeatherController`, `tools/tilkit/*` routes |
| Acceptance fixtures | `tests/Fixtures/Tilkit/` |

---

## Pilot cut-over gate (do not skip)

Before calling a pilot “on TILKIT digital” rather than the xlsx:

1. `[ ]` Phase 0 open questions **#1, #2, #5** decided and encoded in config.
2. `[x]` Phase 2 golden tests **all green** (frog, demo tally, six-sign, climate review gate).
3. `[x]` Single capture path — native TILKIT module (`ToolTilkitWeatherController` + workbook values).
4. `[~]` Code legend seeded from master template for that pilot (`tilkit:import-legend`).
5. `[~]` Sites, seasons, and season settings configured and versioned (setup UI exists).
6. `[ ]` UI states forecasts are **not certified** until (2) passes.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-10-23 | Initial audit from doc vs platform review; tied to `issues.json` epics Phases 0–6 |
| 2026-10-23 | Removed legacy generic-form capture (`WeatherIndicatorLogToolSeeder`, `WeatherIndicatorLogFieldCatalog`) |
| 2026-10-23 | Engine, tally, demo parity, 12-sheet config, admin routes; AP Excel labels; AJ–AL reference sync |
| 2026-10-24 | Personas, climate weight reviews, A–E wizard, xlsx import/export, integration enrichments, six-sign + climate gate tests |
