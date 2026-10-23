# TILKIT v4 alignment audit (living checklist)

Cross-check between **spec documents** (`tilkit_docs/`, Developer Brief in `_extracted.txt`) and the **Laravel platform** (`tk/`). Update this file when behaviour or scope changes; tie completion to GitHub issues created from [`.github/tilkit-project/issues.json`](../../../.github/tilkit-project/issues.json).

**Related docs:** [README](./README.md) · [Open questions](./open-questions.md) · [Fixtures README](../../tests/Fixtures/Tilkit/README.md)

**Last reviewed:** 2026-10-23

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
| **Two capture paths** — native TILKIT module vs generic `WeatherIndicatorLogToolSeeder` / `WeatherIndicatorLogFieldCatalog` | `[~]` | Pick one pilot path; deprecate or gate the other | Phase 1, Phase 4 |
| **Data saved without certified tally** — full workbook capture without Phase 2 engine | `[~]` | UI label “capture only”; block forecast publication until golden tests green | Phase 2, Phase 5 |
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
| Golden fixtures from demo workbook | Brief §10.2 | `tests/Fixtures/Tilkit/gate_eligible_frog.json` only | `[~]` |

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
| Season settings (Weighting Options) | Brief §6.6, v4 tab | `tilkit_season_settings` table | `[ ]` no model/UI |
| Unique `(indicator_id, site_id, season_id)` | Brief §6 data rules | Migration unique index; `TilkitWeatherSchemaTest` | `[x]` |
| Workbook cell storage (not 124 columns on one row) | Brief §4.2 | `tilkit_workbook_values` + `WorkbookRecordService` | `[x]` |
| Raw capture UI (all 90 raw fields) | Register stages | `record-form.blade.php` + `WorkbookColumnRegistry::rawColumnsByStage()` | `[~]` grid form; not sighting-first |
| Derived columns not client-writable | Brief §5 personas | `assertRawColumn()`; read-only section on edit | `[x]` |
| Reference columns AH / AI / AM from relations | Indicator Log | `storeReferenceColumns()` | `[x]` |
| Seed code legend + prior multipliers from v4 xlsx | Phase 1 epic | `tilkit_indicator_codes` empty unless manual | `[ ]` |
| RBAC by provider (observer, ITK, data desk, …) | Brief §5, Register | TILKIT routes use `assertCanView` only | `[ ]` |
| Admin CRUD: sites, seasons, codes | Phase 1 epic | No TILKIT admin UI | `[ ]` |
| Organisation entity | Phase 1 epic deliverable | Uses `dc_projects` as tool host | `[~]` intentional platform pattern |

**Child issues**

| Issue title | Checklist hook |
|-------------|----------------|
| Phase 1: Core TILKIT migrations and models | Rows above through unique constraint |
| Phase 1: Seed indicator code legend and prior multipliers | Seed from master template xlsx |
| Phase 1: Admin UI for sites, seasons, and code legend | Admin CRUD rows |

**Extra alignment note:** `database/seeders/WeatherIndicatorLogToolSeeder.php` builds **generic forms**, not the 124-column contract — treat as legacy/conceptual unless explicitly retired.

---

## Phase 2 — Forecast rules engine (Excel parity)

**Epic:** `[Epic] Phase 2 — Forecast rules engine (Excel parity)` · Milestone: *Phase 2 - Engine*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| Gate: UID, sighted, confirmed, approved | Brief §4.3 gate | `EligibilityGate` | `[x]` |
| Gate: contested excluded; tercile 1/2/3 | Brief §4.3 | `ValidationStatus::excludedFromTally()`; `TercileForecast::isForecast()` | `[x]` |
| Gate: one exclusion reason per row | Brief §10.3 | `GateResult` reason codes | `[~]` not persisted to column **CE** |
| Gate: duplicate / first-record (**DT**) | v4 Change Log | DB unique prevents 2 rows; no DT derivation | `[ ]` |
| Gate: site/season scope in tally context | Brief §4.3 | Enforced by query scope (when tally exists) | `[ ]` |
| Persist **CE** tally status (derived) | v4 single source of truth | `storeDerivedValues` exists; not wired on recalc | `[ ]` |
| Weighting phase switch (≥3 scored seasons) | Brief §4.3 | `config` `scored_seasons_for_hit_rate` | `[ ]` no engine |
| Corroboration cap (base weight max 2) | Brief §4.3 | — | `[ ]` |
| Final weight = base × decision relevance | Brief §4.3 | — | `[ ]` |
| Optional literature prior (rating phase only) | Brief §4.3 | `tilkit_season_settings.use_prior_multiplier` unused | `[ ]` |
| Simple / weighted / priority-adjusted tallies | Brief §10.2 table | — | `[ ]` |
| Reconciliation: counted rows = tally total | Brief §10.3 | — | `[ ]` |
| Ties reported, not silently resolved | Brief §4.3 | — | `[ ]` |
| Post-season Hit / Miss / False Start | Brief lifecycle | `SeasonOutcome` enum; no scoring loop | `[ ]` |
| CLI `tilkit:tally {site} {season}` | Phase 2 epic | — | `[ ]` |
| Frog trace fixture parity | Brief §10.2 | Partial gate fixture only | `[ ]` |
| 11-row demo (duplicate, contested, …) | Brief §10.2 | — | `[ ]` |
| 6-sign OND 2026 (50/17/33 vs 55/14/31 vs 58/10/32) | Brief §10.2 | — | `[ ]` |
| Confidence score **not** in tally weight | Brief §10.3, §6.6 | — | `[ ]` |
| Rating 0 excluded from averages / stability | Brief §10.3 | — | `[ ]` |

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
| 7 sighting columns as async child events (N, O, CI, Y, Z, AA, AB) | Brief §5, §6 | Flat form fields; `TilkitSighting` unused in UI | `[ ]` |
| Update record, never insert duplicate row for new sighting | Demo duplicate frog | Unique constraint only | `[~]` |
| Offline-first mobile (Android) | Brief §8 | — | `[ ]` |
| Sync API (UUID, idempotent, media) | Phase 3 epic | — | `[ ]` |
| Conflict queue (approvals / consent) | Phase 3 epic | — | `[ ]` |
| Optional SMS `SIGHT` adapter | Brief narrative | — | `[ ]` |

**Child issues:** Phase 3: TILKIT sync API and media upload · Phase 3: Offline mobile MVP (Android-first)

---

## Phase 4 — Workshop & validation (web)

**Epic:** `[Epic] Phase 4 — Workshop & validation (web)` · Milestone: *Phase 4 - Workshop*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| Community Data List UX (not one Excel grid) | Brief §10, §11.1 | Stage-grouped `details` form | `[~]` |
| Workshop mode (facilitator, resumable) | Phase 4 epic | — | `[ ]` |
| Validation chain: confirm / approve / verify | Brief lifecycle | Fields in workbook; no role gates | `[ ]` |
| Separation confirm vs approve (same person?) | `open-questions.md` #5 | — | `[ ]` |
| Pending-validation queue | Brief §6.5 | — | `[ ]` |
| Duplicate review queue | Brief §11.3 | — | `[ ]` |
| Controlled dropdowns (AP, U, M, V, …) | xlsx validation lists | Mixed selects + free text | `[~]` |
| Demographics BG–BK | Brief equity | Form includes via register | `[~]` |
| Persona-specific views (observer vs data desk) | Register `provided_by` | — | `[ ]` |

**Child issues:** Phase 4: Workshop capture UX · Phase 4: Validation chain and data desk queues

---

## Phase 5 — Integration, v4 modules, exports

**Epic:** `[Epic] Phase 5 — Integration, v4 modules, exports` · Milestone: *Phase 5 - Integration*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| Seasonal Tercile Tally dashboard (site + season) | Brief §7 web app | — | `[ ]` |
| Immutable forecast snapshots | Brief §10.3, §11.3 | — | `[ ]` |
| KMSA / KMD outlook ingest + agreement | Columns DJ–DL | — | `[ ]` |
| NDMA phase / VCI3M | Brief §6.2, §6.5 | — | `[ ]` |
| Calendar & climate-change flag (CI–CP) | Brief §6.1 | Columns in register only | `[ ]` |
| Vegetation index CQ–CT | Brief §6.2 | — | `[ ]` |
| Anticipatory action triggers CU–CZ | Brief §6.3 | — | `[ ]` |
| Dissemination effectiveness DA–DI | Brief §6.4 | — | `[ ]` |
| Residual risk DM–DP | Brief §6.5 | — | `[ ]` |
| Confidence & hazard priority (DQ–DS) | Weighting Options tab | — | `[ ]` |
| Consensus forecast / integration session (BF, …) | Brief §7 | — | `[ ]` |
| Consent BC enforced on every export | Brief §9 | Partial parse in `WorkbookRecordService` | `[ ]` |
| Do-no-harm BB blocks dissemination | Brief §9 | Stored on record; no gate | `[ ]` |
| PDF / CSV / JSON exports (column order contractual) | Brief §10.1 | — | `[ ]` |

**Child issues:** Phase 5: Official outlook ingest adapter · Phase 5: Tally dashboard and consensus forecast publication · Phase 5: Consent-aware exports (PDF, CSV, JSON)

---

## Phase 6 — Migration, pilots, M&E

**Epic:** `[Epic] Phase 6 — Migration, pilots, M&E` · Milestone: *Phase 6 - Pilots*

| Check | Spec | Platform evidence | Status |
|-------|------|-------------------|--------|
| v3/v4 Excel importers + legacy transforms | Brief §10.1 | — | `[ ]` |
| Import audit vs Change Log (47 entries) | Brief §10.1 | — | `[ ]` |
| Regenerate xlsx export in column order A–DT | Brief §10.1 | — | `[ ]` |
| Engine regression in CI on every change | Phase 6 epic | PHPUnit TILKIT tests; no parity gate | `[~]` |
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
| Eligibility (partial) | `EligibilityGate`, `GateResult` |
| Web entry | `ToolTilkitWeatherController`, `tools/tilkit/*` routes |
| Acceptance fixtures | `tests/Fixtures/Tilkit/` |

---

## Pilot cut-over gate (do not skip)

Before calling a pilot “on TILKIT digital” rather than the xlsx:

1. `[ ]` Phase 0 open questions **#1, #2, #5** decided and encoded in config.
2. `[ ]` Phase 2 golden tests **all green** (frog, 11-row, 6-sign).
3. `[ ]` Single capture path agreed (native module vs generic forms).
4. `[ ]` Code legend seeded from master template for that pilot.
5. `[ ]` Sites, seasons, and season settings configured and versioned.
6. `[ ]` UI states forecasts are **not certified** until (2) passes.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-10-23 | Initial audit from doc vs platform review; tied to `issues.json` epics Phases 0–6 |
