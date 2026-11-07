#!/usr/bin/env python3
"""Build tk/config/tilkit_indicator_behaviour_catalog.json from v4 legend + domain rules."""
from __future__ import annotations

import json
import re
from pathlib import Path

import openpyxl

ROOT = Path(__file__).resolve().parent
XLSX = ROOT / "TILKIT_Weather_Indicator_Log_Master_Template_v4_OND2026.xlsx"
OUT = ROOT.parent / "tk" / "config" / "tilkit_indicator_behaviour_catalog.json"

INTENSITY = [
    {"value": "High", "label": "High"},
    {"value": "Medium", "label": "Medium"},
    {"value": "Low", "label": "Low"},
    {"value": "Not observed", "label": "Not observed / absent"},
]

# Tokens that must not appear in behaviours for a type line (cross-domain guard).
FORBIDDEN_BY_TYPE_LINE: dict[str, list[str]] = {
    "01-PLT": ["bird", "flying east", "flying west", "livestock", "thunder", "lightning", "moon halo", "river", "intestine", "joint pain"],
    "01-ANI": ["blossom", "blossoming", "flowering plant", "leaf flush", "sap exudate", "cumulonimbus", "dew on leaves"],
    "02-ATM": ["blossom", "frog croak", "bird migration", "intestine", "liver divination"],
    "03-AST": ["frog", "leaf shedding", "livestock restlessness", "sap"],
    "04-HYD": ["blossom", "bird", "frog", "thunder only"],
    "05-RIT": ["routine cloud cover", "grass greenness"],
    "06-SEN": ["bird flock", "tree flowering", "river level"],
}

# (type_line, sub_type) → behaviour phrases
BEHAVIOUR_TEMPLATES: dict[tuple[str, str], list[str]] = {
    ("01-PLT", "FLW"): [
        "Heavy blossoming / peak bloom",
        "Light or scattered flowering",
        "Flower buds forming, not yet open",
        "Flowers shedding / petals dropping",
        "Flowering earlier than usual",
        "Flowering later than usual",
        "No flowering when normally expected",
    ],
    ("01-PLT", "LFS"): [
        "Leaves yellowing and drying",
        "Partial leaf shed",
        "Heavy leaf fall",
        "Wilting under heat",
        "Minimal leaf shed (unusual)",
    ],
    ("01-PLT", "LSH"): [
        "Leaves yellowing and drying",
        "Partial leaf shed",
        "Heavy leaf fall",
        "Wilting under heat",
    ],
    ("01-PLT", "LAF"): [
        "Strong new leaf flush",
        "Sparse or delayed leaf emergence",
        "Uniform green flush across canopy",
        "Patchy flush (some trees only)",
    ],
    ("01-PLT", "LEF"): [
        "Strong new leaf flush",
        "Sparse or delayed leaf emergence",
        "Uniform green flush",
    ],
    ("01-PLT", "TFL"): [
        "Trees flowering heavily before rains",
        "Scattered tree flowering",
        "Flowering synchronized across species",
        "Delayed tree flowering",
    ],
    ("01-PLT", "FRU"): [
        "Fruit setting abundantly",
        "Poor fruit set",
        "Fruit ripening early",
        "Fruit dropping prematurely",
    ],
    ("01-ANI", "BMG"): [
        "Birds flying eastward in groups",
        "Birds flying westward in groups",
        "Seasonal arrival of migrant species",
        "Seasonal departure / disappearance",
        "Unusual flock size or direction",
        "Low or absent migration activity",
    ],
    ("01-ANI", "BFL"): [
        "Large flock gathering",
        "Flock dispersed / unusually small",
        "Directional movement at dawn or dusk",
        "Restless movement between roosts",
    ],
    ("01-ANI", "BCL"): [
        "Distinct calling before rain",
        "Unusually silent (no calls)",
        "Night-time calling intensity high",
        "Calling earlier/later than usual",
    ],
    ("01-ANI", "FRO"): [
        "Intense frog croaking after dusk",
        "Sparse or absent frog calls",
        "Frogs emerging near water",
        "Frogs quiet when usually vocal",
    ],
    ("01-ANI", "FRG"): [
        "Intense frog croaking after dusk",
        "Sparse or absent frog calls",
        "Frogs emerging near ponds",
    ],
    ("01-ANI", "LIV"): [
        "Livestock restless / moving toward water",
        "Livestock lethargic or clustering",
        "Unusual grazing times",
        "Seeking shelter early",
    ],
    ("02-ATM", "CLD"): [
        "Clouds building vertically (towering)",
        "Flat, featureless overcast",
        "Clearing sky after long overcast",
        "Rapid cloud colour darkening",
    ],
    ("02-ATM", "WND"): [
        "Wind shifting to onshore / moisture-bearing direction",
        "Wind shifting dry / offshore",
        "Gusty pre-rain wind",
        "Unusually calm air",
    ],
    ("02-ATM", "DEW"): [
        "Heavy morning dew",
        "Absent dew on leaves (dry signal)",
        "Dew forming unusually late",
    ],
    ("02-ATM", "LTG"): [
        "Distant lightning with little rain",
        "Frequent nearby lightning",
        "Lightning without thunder (heat signal)",
    ],
    ("03-AST", "MOO"): [
        "Crescent moon orientation as read locally",
        "Moon halo / ring",
        "Moon rising position shifted",
        "Full moon unusually bright",
    ],
    ("03-AST", "STR"): [
        "Key star group visible at season marker",
        "Stars dimmed by haze",
        "Unusual star brightness",
    ],
    ("04-HYD", "LVL"): [
        "River / lake level rising",
        "River / lake level falling",
        "Spring flow increasing",
        "Water source drying faster than usual",
    ],
    ("04-HYD", "FLW"): [
        "Spring discharge increasing",
        "Spring drying up",
        "Unusual current strength",
        "Stagnant flow",
    ],
    ("05-RIT", "DRM"): [
        "Dream interpreted as rain signal (community protocol)",
        "Vision discussed in ritual council",
    ],
    ("06-SEN", "SM L"): [
        "Strong smell of rain in the air",
        "Absent earth/rain smell when expected",
    ],
    ("06-SEN", "PAI"): [
        "Joint aches increasing",
        "Headache / sinus pressure",
        "Body feels unusually heavy or lethargic",
    ],
}
# fix typo key
if ("06-SEN", "SM L") in BEHAVIOUR_TEMPLATES:
    BEHAVIOUR_TEMPLATES[("06-SEN", "SML")] = BEHAVIOUR_TEMPLATES.pop(("06-SEN", "SM L"))

TYPE_LINE_GENERIC: dict[str, list[str]] = {
    "01-PLT": [
        "Sign more pronounced than usual (high vigour)",
        "Sign weaker than usual",
        "Sign observed earlier than normal calendar",
        "Sign observed later than normal calendar",
        "Sign absent when normally expected",
    ],
    "01-ANI": [
        "Behaviour more intense than usual",
        "Behaviour weaker or absent",
        "Activity earlier than normal calendar",
        "Activity later than normal calendar",
        "Unusual direction or location of activity",
    ],
    "02-ATM": [
        "Phenomenon intense / widespread",
        "Phenomenon weak or localized",
        "Earlier than usual timing",
        "Later than usual timing",
        "Absent when normally expected",
    ],
    "03-AST": [
        "Celestial sign clear and pronounced",
        "Sign faint or obscured",
        "Earlier than usual appearance",
        "Later than usual appearance",
        "Sign not seen when expected",
    ],
    "04-HYD": [
        "Water signal stronger than usual",
        "Water signal weaker than usual",
        "Change earlier than seasonal norm",
        "Change later than seasonal norm",
        "No change when change is usual",
    ],
    "05-RIT": [
        "Ritual sign affirmed by knowledgeable holders",
        "Sign contested or not interpreted",
        "Timing earlier than ritual calendar",
        "Timing later than ritual calendar",
    ],
    "06-SEN": [
        "Sensation intense among several observers",
        "Sensation mild or isolated",
        "Felt earlier than usual",
        "Felt later than usual",
        "Not felt when usually present",
    ],
}


def type_line(cat: str, typ: str) -> str:
    return f"{cat}-{typ}"


def label_phrase(label: str) -> str:
    t = re.sub(r"\s+", " ", label.strip())
    if len(t) > 80:
        t = t[:77] + "..."
    return t


def generate(code: str, cat: str, typ: str, sub: str, label: str) -> list[str]:
    tl = type_line(cat, typ)
    key = (tl, sub)
    if key in BEHAVIOUR_TEMPLATES:
        base = list(BEHAVIOUR_TEMPLATES[key])
    else:
        topic = label_phrase(label) if label else sub
        base = [f"{g} - {topic}" for g in TYPE_LINE_GENERIC.get(tl, TYPE_LINE_GENERIC["02-ATM"])]
        if label:
            base.insert(0, f"Observable pattern: {topic} (pronounced)")
            base.insert(1, f"Observable pattern: {topic} (reduced or absent)")

    forbidden = FORBIDDEN_BY_TYPE_LINE.get(tl, [])
    out: list[str] = []
    for b in base:
        low = b.lower()
        if any(f in low for f in forbidden):
            continue
        out.append(b)
    return out or base[:5]


def main() -> None:
    wb = openpyxl.load_workbook(XLSX, read_only=True, data_only=True)
    ws = wb["Indicator Code Legend"]
    by_code: dict = {}
    violations: list[str] = []

    for row in ws.iter_rows(min_row=6, values_only=True):
        if not row or not row[0]:
            continue
        cat, _, typ, _, sub, sub_name = (row + (None,) * 6)[:6]
        if not cat or not typ or not sub:
            continue
        cat_s = str(cat).strip().zfill(2)
        typ_s = str(typ).strip().upper()
        sub_s = str(sub).strip().upper()
        if not cat_s.isdigit():
            continue
        code = f"{cat_s}-{typ_s}-{sub_s}"
        label = str(sub_name or "").strip()
        tl = type_line(cat_s, typ_s)
        behaviours = generate(code, cat_s, typ_s, sub_s, label)
        forbidden = FORBIDDEN_BY_TYPE_LINE.get(tl, [])
        for b in behaviours:
            low = b.lower()
            for f in forbidden:
                if f in low:
                    violations.append(f"{code}: forbidden '{f}' in '{b}'")

        by_code[code] = {
            "type_line": tl,
            "sub_type": sub_s,
            "classification_label": label,
            "behaviours": behaviours,
        }

    payload = {
        "version": "v4-ond2026",
        "intensity_options": INTENSITY,
        "forbidden_cross_domain": FORBIDDEN_BY_TYPE_LINE,
        "by_code": by_code,
    }
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"Wrote {OUT} ({len(by_code)} codes, {len(violations)} guard violations)")
    if violations:
        for v in violations[:20]:
            print("  ", v)
        raise SystemExit(1)


if __name__ == "__main__":
    main()
