# TILKIT golden fixtures

## v4 xlsx — Sheet 1 - Indicator Log layout

Both master and DEMO workbooks use the same sheet name **`Sheet 1 - Indicator Log`** (not `Sheet1`).

| Row | Content |
|-----|---------|
| 1 | Title / DEMO banner |
| 2 | Excel column letters **A–DT** (map cells by this row; **AH** = Unique Indicator ID) |
| 3–4 | Group labels and long field prompts |
| 5+ | Category scaffolding; first logged indicator row in DEMO is **row 12** (`AH` = `01-ANI-FRO-001`) |

Scan from row 5 upward for `AH` matching `##-XXX-XXX-###` when importing from xlsx.

Port test cases from `tilkit_docs/TILKIT_Weather_Indicator_Log_v4_DEMO_hypothetical_data.xlsx`:

1. **Frog sign weighting trace** — first season cap, second season weight 9  
2. **11-row demo** — duplicate frog row, contested sign, unsighted, zero relevance  
3. **6-sign OND 2026** — simple / weighted / priority-adjusted tallies  

Add JSON or CSV here; Phase 2 engine tests must match workbook outputs to displayed precision.
