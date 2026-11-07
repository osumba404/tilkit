#!/usr/bin/env python3
"""Audit v4 legend + taxonomy.json for hierarchy mismatches."""
import json
from pathlib import Path

import openpyxl

ROOT = Path(__file__).resolve().parent
XLSX = ROOT / "TILKIT_Weather_Indicator_Log_Master_Template_v4_OND2026.xlsx"
TAX = ROOT.parent / "tk" / "config" / "tilkit_indicator_taxonomy.json"

CANON_TYPE_FOR_CATEGORY = {
    "01": {"PLT", "ANI"},
    "02": {"ATM"},
    "03": {"AST"},
    "04": {"HYD"},
    "05": {"RIT"},
    "06": {"SEN"},
}
TYPE_LINES = {
    "01-PLT",
    "01-ANI",
    "02-ATM",
    "03-AST",
    "04-HYD",
    "05-RIT",
    "06-SEN",
}

tax = json.loads(TAX.read_text(encoding="utf-8"))
issues: list[str] = []

wb = openpyxl.load_workbook(XLSX, read_only=True, data_only=True)
ws = wb["Indicator Code Legend"]
codes: list[dict] = []
for row in ws.iter_rows(min_row=6, values_only=True):
    if not row or not row[0]:
        continue
    cat, cat_name, typ, typ_name, sub, sub_name = (row + (None,) * 6)[:6]
    if not cat or not typ or not sub:
        continue
    cat_s = str(cat).strip().zfill(2)
    typ_s = str(typ).strip().upper()
    sub_s = str(sub).strip().upper()
    code = f"{cat_s}-{typ_s}-{sub_s}"
    codes.append(
        {
            "code": code,
            "category": cat_s,
            "type": typ_s,
            "sub_type": sub_s,
            "category_name": str(cat_name or ""),
            "type_name": str(typ_name or ""),
            "label": str(sub_name or ""),
        }
    )

for r in codes:
    code, cat, typ = r["code"], r["category"], r["type"]
    tl = f"{cat}-{typ}"
    if tl not in TYPE_LINES:
        issues.append(f"{code}: type line {tl} not one of seven canonical lines")
    allowed = CANON_TYPE_FOR_CATEGORY.get(cat, set())
    if typ not in allowed:
        issues.append(f"{code}: type {typ} not allowed in category {cat} (allowed: {sorted(allowed)})")
    if not code.startswith(f"{cat}-"):
        issues.append(f"{code}: code prefix does not match category {cat}")
    cat_label = tax["categories"].get(cat, "")
    if cat_label and r["category_name"]:
        norm_a = cat_label.lower().replace("/", "").replace(" ", "")
        norm_b = r["category_name"].lower().replace("/", "").replace(" ", "")
        if norm_a[:8] not in norm_b and norm_b[:8] not in norm_a:
            issues.append(
                f"{code}: xlsx category name {r['category_name']!r} drifts from taxonomy {cat_label!r}"
            )

for tl, label in tax.get("types", {}).items():
    cat = tl[:2]
    typ = tl.split("-", 1)[1]
    if tl not in TYPE_LINES:
        issues.append(f"taxonomy.json type {tl} is not canonical")
    if typ not in CANON_TYPE_FOR_CATEGORY.get(cat, set()):
        issues.append(f"taxonomy.json {tl} incompatible with category {cat}")

print(f"Codes: {len(codes)}")
print(f"Issues: {len(issues)}")
for i in issues[:30]:
    print(" -", i)
