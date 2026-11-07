#!/usr/bin/env python3
"""Generate indicator-hierarchy-reference.md from v4 master xlsx."""
import json
from collections import defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parent
XLSX = ROOT / "TILKIT_Weather_Indicator_Log_Master_Template_v4_OND2026.xlsx"
OUT = ROOT / "indicator-hierarchy-reference.md"
TAXONOMY = Path(__file__).resolve().parents[1] / "tk" / "config" / "tilkit_indicator_taxonomy.json"

import openpyxl

wb = openpyxl.load_workbook(XLSX, read_only=True, data_only=True)
ws = wb["Indicator Code Legend"]
rows: list[dict] = []
for row in ws.iter_rows(min_row=6, values_only=True):
    if not row or not row[0]:
        continue
    cat, cat_name, typ, typ_name, sub, sub_name = (row + (None,) * 6)[:6]
    if not cat or not typ or not sub:
        continue
    cat_s, typ_s, sub_s = str(cat).strip(), str(typ).strip(), str(sub).strip()
    if not cat_s.isdigit() or len(cat_s) != 2:
        continue
    code = f"{cat_s}-{typ_s}-{sub_s}"
    rows.append(
        {
            "code": code,
            "category": cat_s,
            "category_name": str(cat_name or "").strip(),
            "type": typ_s,
            "type_name": str(typ_name or "").strip(),
            "sub_type": sub_s,
            "label": str(sub_name or "").strip(),
        }
    )

tax = json.loads(TAXONOMY.read_text(encoding="utf-8"))

tree: dict[str, dict[str, dict]] = defaultdict(dict)
cat_names: dict[str, str] = {}
for r in rows:
    c, t = r["category"], r["type"]
    cat_names[c] = r["category_name"]
    if t not in tree[c]:
        tree[c][t] = {"type_name": r["type_name"], "subs": []}
    tree[c][t]["subs"].append(r)

category_order = tax.get("category_order") or sorted(tree.keys())
type_lines = tax.get("types", {})

lines: list[str] = []
lines += [
    "# TILKIT indicator hierarchy — reference compendium",
    "",
    "**Version:** TILKIT Weather Indicator Log Master Template v4 (OND 2026)  ",
    f"**Legend rows:** {len(rows)} standard sub-type codes on the *Indicator Code Legend* sheet.",
    "**Canonical type lines:** 7 (`01-PLT`, `01-ANI`, `02-ATM`, `03-AST`, `04-HYD`, `05-RIT`, `06-SEN`).",
    "",
    "---",
    "",
    "## Table of contents",
    "",
    "1. [Purpose and conceptual frame](#1-purpose-and-conceptual-frame)",
    "2. [How codes are built](#2-how-codes-are-built)",
    "3. [Top-level hierarchy (six categories)](#3-top-level-hierarchy-six-categories)",
    "4. [Seven canonical type lines](#4-seven-canonical-type-lines)",
    "5. [Workbook capture levels](#5-workbook-capture-levels)",
    "6. [Complete code catalogue by category](#6-complete-code-catalogue-by-category)",
    "6A. [Hierarchy levels vs workbook columns (all stages)](#6a-hierarchy-levels-vs-workbook-columns-all-stages)",
    "7. [Indigenous knowledge context (literature)](#7-indigenous-knowledge-context-literature)",
    "8. [Prior multipliers and weighting (summary)](#8-prior-multipliers-and-weighting-summary)",
    "9. [Sources and further reading](#9-sources-and-further-reading)",
    "10. [Appendix — alphabetical code index](#10-appendix--alphabetical-code-index)",
    "",
    "",
    "---",
    "",
    "## 1. Purpose and conceptual frame",
    "",
    "TILKIT (Traditional Indigenous and Local Knowledge tool) systematises **consensus-based ethnographic weather indicators** "
    "for community climate monitoring and seasonal rainfall forecasting. The tool was developed and validated through "
    "participatory training across East Africa (Kenya, Tanzania, Uganda) and is designed to complement—not replace—national "
    "meteorological outlooks (e.g. Kenya Meteorological Service Authority) via **knowledge integration** and impact-based advisories.",
    "",
    "Each **indicator** is a culturally situated environmental sign whose local meaning is negotiated in workshop and plenary: "
    "what rainfall outcome it signals (amount tercile, onset/cessation, distribution), **lead time** after observation, "
    "and community confidence (above / normal / below normal rainfall for the season).",
    "",
    "This document describes the **classification hierarchy** that standardises how signs are catalogued before community "
    "members attach local names and behaviours:",
    "",
    "| Analytical level | Example | Role |",
    "|------------------|---------|------|",
    "| Category `01`–`06` | `01` Biological | Broad domain of the phenomenon. |",
    "| Type line | `01-ANI` | One of seven workbook families spanning categories. |",
    "| Type segment (workbook B) | `ANI` | Plants vs animals within category 01, etc. |",
    "| Sub-type | `FRO` | Phenomenological subclass (frog activity). |",
    "| Legend code | `01-ANI-FRO` | Controlled vocabulary row on the legend sheet. |",
    "| Unique Indicator ID | `01-ANI-FRO-001` | Stable instance ID for a registered sign (column AH). |",
    "| Registered sign | Local name + behaviour (C, D) | Site-specific manifestation of the legend code. |",
    "",
    "---",
    "",
    "## 2. How codes are built",
    "",
    "### 2.1 Syntax",
    "",
    "```text",
    "CC-TTT-SSS",
    "│  │   └── Sub-type (3 letters) — phenomenological class",
    "│  └────── Type segment (3 letters) — PLT, ANI, ATM, AST, HYD, RIT, SEN",
    "└───────── Category (2 digits) — 01 … 06",
    "```",
    "",
    "The **type line** used in analysis and priors is `CC-TTT` (category + type segment), e.g. `03-AST`.",
    "",
    "### 2.2 Relationship to Unique Indicator ID",
    "",
    "Workshop participants register **specific signs** under a legend code. The master template assigns a running suffix:",
    "",
    "```text",
    "01-ANI-FRO-001   →   category 01, type ANI, sub-type FRO, instance 001",
    "```",
    "",
    "The suffix (`-001`) is **per sign**, not per season or site. Sites and seasons are separate dimensions on the Indicator Log row.",
    "",
    "### 2.3 v4 taxonomy realignment (October 2026)",
    "",
    "Version 4 realigned legacy category/type codes into **six categories** and **seven type lines**, with ~160 legend rows "
    "and literature-derived **prior multipliers** (0.55–1.35). Historical tallies must remain reproducible under the "
    "protocol version that generated them.",
    "",
    "---",
    "",
    "## 3. Top-level hierarchy (six categories)",
    "",
    "These are the v4 **Category** codes (`01`–`06`) from `tilkit_indicator_taxonomy.json` and the master legend:",
    "",
]

for cat_key in category_order:
    label = tax["categories"].get(cat_key, cat_names.get(cat_key, f"Category {cat_key}"))
    n_codes = sum(len(tree[cat_key][t]["subs"]) for t in tree.get(cat_key, {}))
    lines.append(f"### Category `{cat_key}` — {label}")
    lines.append("")
    lines.append(f"- **Legend sub-type codes in this category:** {n_codes}")
    if cat_key in tree:
        type_keys = sorted(tree[cat_key].keys())
        lines.append(f"- **Type segments present:** {', '.join(type_keys)}")
    lines.append("")

lines += [
    "---",
    "",
    "## 4. Seven canonical type lines",
    "",
    "TILKIT v4 analysis and mind-map views use exactly **seven type lines** (one per category except `01`, which splits into plants and animals):",
    "",
    "| Type line | Category | Short label |",
    "|-----------|----------|-------------|",
]

for tl, short in type_lines.items():
    cat = tl[:2]
    cat_label = tax["categories"].get(cat, "")
    lines.append(f"| `{tl}` | `{cat}` {cat_label} | {short} |")

lines += [
    "",
    "**Note:** Category `01` is the only category with **two** type lines (`01-PLT` and `01-ANI`). All other categories map one type line each.",
    "",
    "### 4.1 Code counts by type line",
    "",
    "| Type line | Sub-type codes | Share of legend |",
    "|-----------|----------------|-----------------|",
]

# stats for type lines
from collections import Counter

tl_counts: Counter[str] = Counter()
for r in rows:
    tl_counts[f"{r['category']}-{r['type']}"] += 1
for tl in [
    "01-PLT",
    "01-ANI",
    "02-ATM",
    "03-AST",
    "04-HYD",
    "05-RIT",
    "06-SEN",
]:
    n = tl_counts.get(tl, 0)
    pct = (100.0 * n / len(rows)) if rows else 0
    lines.append(f"| `{tl}` | {n} | {pct:.1f}% |")

lines += [
    "",
    "### 4.2 Hierarchy diagram (conceptual)",
    "",
    "```mermaid",
    "flowchart LR",
    "  ROOT[TILKIT indicator analysis]",
    "  ROOT --> P1[Code categories pillar]",
    "  ROOT --> P2[UID structure]",
    "  ROOT --> P3[Log fields]",
    "  ROOT --> P4[History and reliability]",
    "  ROOT --> P5[Field sighting]",
    "  ROOT --> P6[Behaviour]",
    "  P1 --> C01[01 Biological]",
    "  P1 --> C02[02 Atmospheric]",
    "  P1 --> C03[03 Astronomical]",
    "  P1 --> C04[04 Hydrological]",
    "  P1 --> C05[05 Ritual]",
    "  P1 --> C06[06 Sensory]",
    "  C01 --> TPLT[01-PLT Plants]",
    "  C01 --> TANI[01-ANI Animals]",
    "  C02 --> TATM[02-ATM Atmosphere]",
    "  C03 --> TAST[03-AST Sky]",
    "  C04 --> THYD[04-HYD Water]",
    "  C05 --> TRIT[05-RIT Ritual]",
    "  C06 --> TSEN[06-SEN Body or senses]",
    "  TANI --> ST[Sub-types e.g. FRO BMG]",
    "  ST --> LC[Legend code 01-ANI-FRO]",
    "  LC --> UID[Unique ID 01-ANI-FRO-001]",
    "```",
    "",
    "---",
    "",
    "## 5. Workbook capture levels",
    "",
    "The Community Data List and Indicator Log align capture fields to the same conceptual stack:",
    "",
    "| Workbook | Field | Meaning |",
    "|----------|-------|---------|",
    "| **A** | Indicator category | Broad group `01`–`06` |",
    "| **B** | Type & sub-type | e.g. type line `01-ANI` and sub-type `BMG` |",
    "| **C** | Registered sign (local name) | Community name for this sign |",
    "| **D** | Behaviour | What the sign looks, sounds, or feels like when active |",
    "| **AH** | Unique Indicator ID | e.g. `01-ANI-FRO-001` |",
    "",
    "### 5.1 Classification label vs column D behaviour",
    "",
    "Do **not** put the legend sub-type description (e.g. \"Flowering/blooming\") directly into column **D**. "
    "That text classifies the *kind* of sign (workbook **B** / legend row). Column **D** is the **observable behaviour this season**, "
    "chosen from domain-consistent options—for example for `01-PLT-FLW`:",
    "",
    "- Heavy blossoming / peak bloom",
    "- Light or scattered flowering",
    "- Flowers shedding / petals dropping",
    "",
    "…plus an **intensity** (High / Medium / Low / Not observed). Stored form: `Behaviour text | Intensity: High`.",
    "",
    "For `01-ANI-BMG`, behaviours include bird migration direction and flock movement—not plant flowering. "
    "The app catalogue `tk/config/tilkit_indicator_behaviour_catalog.json` (regenerate via `tilkit_docs/build_indicator_behaviour_catalog.py`) "
    "enforces this for all 157 legend codes.",
    "",
    "Workshop flow (**A. For every sign / B. With every sighting / C. After the season / D. Data desk**) mirrors these levels.",
    "",
    "**Tercile forecast (column U):** `3` = above normal, `2` = normal, `1` = below normal, `0` = no prediction yet.",
    "",
    "---",
    "",
    "## 6. Complete code catalogue by category",
    "",
    "Full list extracted from the v4 master *Indicator Code Legend* sheet. "
    "Each row is one **legend code** (`CC-TTT-SSS`) with its descriptive label.",
    "",
]

for cat_key in category_order:
    if cat_key not in tree:
        continue
    cat_label = tax["categories"].get(cat_key, cat_names.get(cat_key, ""))
    lines.append(f"### 6.{int(cat_key)} Category `{cat_key}` — {cat_label}")
    lines.append("")

    for typ in sorted(tree[cat_key].keys()):
        block = tree[cat_key][typ]
        type_line = f"{cat_key}-{typ}"
        tlabel = block["type_name"] or type_lines.get(type_line, typ)
        lines.append(f"#### Type line `{type_line}` — {tlabel}")
        lines.append("")
        lines.append("| Code | Sub-type | Description |")
        lines.append("|------|----------|-------------|")
        for sub in sorted(block["subs"], key=lambda x: x["code"]):
            desc = sub["label"].replace("|", "\\|")
            lines.append(f"| `{sub['code']}` | `{sub['sub_type']}` | {desc} |")
        lines.append("")

lines += [
    "---",
    "",
    "## 6A. Hierarchy levels vs workbook columns (all stages)",
    "",
    "Use this matrix to avoid mixing layers (your example: **flowering plant** at classification must not carry **bird movement** at behaviour).",
    "",
    "| Level | Shape | Workbook | Put here | Do **not** put here |",
    "|-------|-------|----------|----------|---------------------|",
    "| Category | `01`–`06` | **A** | Broad domain label (`01 — Biological / ecological`) | Type lines, legend codes, behaviours, tercile `U` |",
    "| Type line | `01-PLT` … `06-SEN` | **B** (part) | One of seven families | Other domains (birds under `01-PLT`) |",
    "| Sub-type | `FLW`, `BMG`, … | **B** (part) | 3-letter phenomenological class | Local sign names, sightings |",
    "| Legend code | `CC-TTT-SSS` | **B** + **AH** prefix | Controlled vocabulary row | Site, season, GPS |",
    "| Unique ID | `…-###` | **AH** | One registered sign | Per-sighting narrative |",
    "| Registered sign | (local) | **C** | Local / English / scientific name | Legend gloss only |",
    "| Season behaviour | (observed) | **D** | Domain-specific observable pattern **this season** | Classification text, other domains |",
    "| Local prediction | (words) | **E** | What rains/season mean locally | Indicator codes, ratings |",
    "| Prediction facets | codes/dates | **H**, **P–U** | Onset, cessation, distribution, tercile | Phenomenon description (→ **D**) |",
    "| Reliability / usefulness | 3/2/1/0 | **L**, **M**, **V** | Trust and decision relevance | Raw phenomenon (→ **D**) |",
    "| Sighting evidence | text/media | **N**, **O**, **Y**, **CI** | Was seen, where/when, photos | Reclassify A–B |",
    "| Season scoring | Hit/Miss | **AZ** (after season) | Outcome vs prediction | Change legend code |",
    "",
    "**Community Data List stages:**",
    "",
    "| Stage | When | Hierarchy rule |",
    "|-------|------|----------------|",
    "| **A. For every sign** | FGD / plenary | Fix **A–B–C**, behaviour **D**, predictions **H/P–U** |",
    "| **B. With every sighting** | Each observation | Keep **AH**; add **N/O/Y** — no new classification |",
    "| **C. After the season** | Review | **AS–AV**, **AZ** — score outcomes, not re-type the sign |",
    "| **D. Data desk** | Entry / tally | **AI/AM**, derived weights — classification must match legend |",
    "",
    "**Intensity:** strength of the sign in **D** (`… | Intensity: High`) is **not** the same as reliability **M** (3/2/1/0) or tercile **U** (above/normal/below).",
    "",
    "App enforcement: `IndicatorHierarchy` (workbook A/B shape, column bleed checks) + behaviour catalog (column **D** options per legend code).",
    "",
    "---",
    "",
    "## 7. Indigenous knowledge context (literature)",
    "",
    "The v4 taxonomy does not invent categories in a vacuum. Decades of ethno-meteorology and TILKIT field work cluster "
    "local signs into families that match the six v4 categories. The following synthesises **public literature** (not exhaustive).",
    "",
    "### 7.1 Early TILKIT / CGIAR framing",
    "",
    "The foundational TILKIT methodology (CGIAR / AICCRA) describes indicators spanning:",
    "",
    "- **Celestial / astrological:** sun, moon, stars, clouds, wind, whirlwinds;",
    "- **Physical / atmospheric:** dew, fog, lightning, materials and objects;",
    "- **Biological:** plants, trees, animals, insects, birds, reptiles, livestock behaviour;",
    "- **Human body / sensory:** temperature, humidity, smell, bodily sensations.",
    "",
    "Workshop tables record, for each sign: local weather meaning, lead time, and plenary rating of seasonal rainfall "
    "(above / normal / below normal). See CGIAR report "
    "[*Towards a Replicable Innovative Tool…*](https://hdl.handle.net/10568/130516).",
    "",
    "### 7.2 East African community practice",
    "",
    "Recent ethno-meteorological research in the Mount Kenya region (PLOS ONE, 2025) documents systematic use of "
    "**atmospheric**, **celestial**, **biological**, and **somatic** indicators, with elders treating the **human body as an "
    "environmental sensor**—aligned with TILKIT category `06-SEN`. Example: "
    "[*When the Emerald wood dove calls, the earth prepares to drink*](https://journals.plos.org/plosone/article?id=10.1371/journal.pone.0355610).",
    "",
    "UNESCO and community documentation on Ogiek forest knowledge highlights **tree flowering**, **bird calls**, **river levels**, "
    "and **wind shifts** as seasonal cues—spanning categories `01`, `03`, `04`, and `02`.",
    "",
    "### 7.3 Sensory and embodied forecasting",
    "",
    "Ethnographic work in Borneo (Kenyah Badeng farmers) shows forecasting built on **smell, touch, sound, and sight** "
    "interpreted through cultural categories—not only declarative proverbs. This supports the sensory / human-body branch "
    "of the taxonomy. See Garay & Puri, *Smelling the monsoon* "
    "[(Kent Academic Repository)](https://kar.kent.ac.uk/31520/1/Garay%20and%20Puri%202011%20Smelling%20the%20Monsoon%20-%20Modified.pdf).",
    "",
    "### 7.4 Ritual and divinatory knowledge",
    "",
    "Category `05-RIT` encodes **restricted** knowledge: dreams, viscera divination, ritual calendars. "
    "Andean *Cabañuelas* (month-by-month observation rituals) illustrate how ritualised calendars link daily observations "
    "to seasonal rainfall expectations—conceptually related though not identical to East African TILKIT practice. "
    "See [JAAR / Oxford Academic](https://doi.org/10.1093/jaarel/lfad030).",
    "",
    "Applications handling category `05` should support **community-private** records and consent levels.",
    "",
    "### 7.5 Pacific and global parallels",
    "",
    "Vanuatu’s national traditional-knowledge indicator booklets catalogue **plants, animals, and astronomical phenomena** "
    "for cyclone and dry-season timing—useful parallel reading for hydrological and celestial branches. "
    "See [Vanuatu National TK Indicators Booklet](https://www.nab.vu/sites/default/files/documents/Vanuatu%20National%20TK%20Indicators%20Booklet.pdf).",
    "",
    "### 7.6 Mapping literature families → v4 categories",
    "",
    "| Literature / workshop family | Typical examples | v4 category | v4 type line(s) |",
    "|------------------------------|------------------|-------------|-----------------|",
    "| Flora / crops / pastures | Acacia flush, baobab bloom | `01` | `01-PLT` |",
    "| Fauna / livestock | Frog chorus, bird migration, termites | `01` | `01-ANI` |",
    "| Atmosphere | Cloud build-up, thunder, dew, fog, wind | `02` | `02-ATM` |",
    "| Sky / calendar | Moon halo, star visibility, sunrise colour | `03` | `03-AST` |",
    "| Water | River rise, spring flow, lake colour | `04` | `04-HYD` |",
    "| Ritual / divination | Dreams, sacrificial signs, ritual calendars | `05` | `05-RIT` |",
    "| Body / senses | Joint pain, smell of rain, humidity felt on skin | `06` | `06-SEN` |",
    "",
    "---",
    "",
    "## 8. Prior multipliers and weighting (summary)",
    "",
    "Each legend code carries a **suggested global prior multiplier** (approximately 0.55–1.35) from a literature-based "
    "Priority Matrix (prevalence, reliability, lead time). **Plants** tend toward the high end (~1.35); **sensory** signs "
    "toward the low end (~0.55); **stars** and other high-reliability celestial markers are also relatively high.",
    "",
    "**Lookup order** for priors: exact 10-character code → type line → category.",
    "",
    "At tally time, community **hit-rate history** (after ≥3 scored seasons) overrides the prior for that Unique Indicator ID. "
    "Priors apply only while the indicator remains in the **rating-based** weighting phase.",
    "",
    "Operational fields on each Indicator Log row (simplified):",
    "",
    "| Concept | Workbook idea |",
    "|---------|----------------|",
    "| Eligibility | Sighted, confirmed, approved, not contested, tercile 1–3 |",
    "| Base weight | Current reliability × phase rules |",
    "| Decision relevance | Multiplier 1–3 |",
    "| Final weight | Base × relevance (× optional prior) |",
    "| Outcome scoring | Hit / Miss / False Start after season |",
    "",
    "---",
    "",
    "## 9. Sources and further reading",
    "",
    "### TILKIT / platform (this repository)",
    "",
    "- Master template: `tilkit_docs/TILKIT_Weather_Indicator_Log_Master_Template_v4_OND2026.xlsx`",
    "- Canonical taxonomy JSON: `tk/config/tilkit_indicator_taxonomy.json`",
    "- Import command: `php artisan tilkit:import-legend`",
    "",
    "### Academic and policy",
    "",
    "- CGIAR / AICCRA TILKIT tool report: https://hdl.handle.net/10568/130516",
    "- AICCRA news: https://aiccra.cgiar.org/news/how-weave-local-and-indigenous-knowledge-climate-monitoring-and-weather-forecasting-africa",
    "- CE MIRIDE policy brief (traditional weather forecasting): https://www.cemiride.org/wp-content/uploads/2023/11/Traditional-Weather-Forecastinhg-Policy-Brief-Cemiride-Nov-2022-Final.pdf",
    "- Mount Kenya ethno-meteorology (PLOS ONE): https://journals.plos.org/plosone/article?id=10.1371/journal.pone.0355610",
    "- UNESCO Ogiek forest indicators: https://www.unesco.org/en/articles/when-forest-speaks-ogiek-indigenous-knowledge-sustaining-her-people-biodiversity-and-climate",
    "- Garay & Puri, sensory forecasting (Sarawak): https://kar.kent.ac.uk/31520/1/Garay%20and%20Puri%202011%20Smelling%20the%20Monsoon%20-%20Modified.pdf",
    "- Cabañuelas ritual forecasting (Andes): https://doi.org/10.1093/jaarel/lfad030",
    "- Vanuatu TK indicators booklet: https://www.nab.vu/sites/default/files/documents/Vanuatu%20National%20TK%20Indicators%20Booklet.pdf",
    "",
    "---",
    "",
    "## 10. Appendix — alphabetical code index",
    "",
    "Quick lookup of all legend codes (sorted by code string).",
    "",
    "| Code | Description |",
    "|------|-------------|",
]

for r in sorted(rows, key=lambda x: x["code"]):
    desc = r["label"].replace("|", "\\|")
    lines.append(f"| `{r['code']}` | {desc} |")

lines += [
    "",
    "---",
    "",
    "*Generated from the v4 Indicator Code Legend sheet. Regenerate with `python tilkit_docs/build_indicator_hierarchy_doc.py` after legend updates.*",
    "",
]

OUT.write_text("\n".join(lines), encoding="utf-8")
print(f"Wrote {OUT} ({len(rows)} codes, {len(lines)} lines)")
