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
| `app/Services/Tilkit/*` | Rules engine (gate, weights, tally — phased) |
| `app/Enums/Tilkit/*` | Tercile, ratings, validation status |
| `tests/Fixtures/Tilkit/` | Golden cases ported from the v4 demo workbook |

## Alignment phases

Track detailed status in **[alignment-audit.md](./alignment-audit.md)** (living checklist tied to [GitHub issue epics](../../../.github/tilkit-project/issues.json)).

1. **Phase 0** — Spec baseline, open questions, golden fixtures  
2. **Phase 1** — Schema, reference data, workbook capture UI  
3. **Phase 2** — Forecast engine with Excel parity tests  
4. **Phase 3–6** — Mobile sync, workshop/validation, integration & v4 modules, migration & pilots  

Each **tool** (`dc_projects`) can host TILKIT **sites** and **seasons** for a pilot (e.g. SCLoWFER, Green Schools).
