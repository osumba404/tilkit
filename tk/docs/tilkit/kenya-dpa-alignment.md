# Kenya Data Protection Act, 2019 - TILKIT alignment

This note maps the **Data Protection Act, 2019** (Cap. 25C) and ODPC expectations to controls implemented in this platform. It is an engineering aid, not legal advice. Register as a data controller/processor with the ODPC where required.

## Section 25 - Data protection principles

| Principle | Platform control |
|-----------|------------------|
| (a) Right to privacy | RBAC, consent BC/BB on rows, export gate `TilkitConsentGate` |
| (b) Lawful, fair, transparent | `dc_projects.purpose`, lawful basis, public privacy notice `/t/{code}/privacy` |
| (c) Purpose limitation | Purpose stored per tool; workbook inventory in `config/kenya_dpa.php` |
| (d) Data minimization | Capture only v4 workbook fields; optional GPS/photos |
| (e) Family/private affairs | Do-no-harm BB; community consent BC; workshop governance |
| (f) Accuracy | Correction via capture UI and data subject correction requests |
| (g) Storage limitation | `retention_months` + `php artisan tilkit:enforce-retention` (monthly schedule) |
| (h) Cross-border transfers | `cross_border_transfers_allowed` + safeguards text; default **no** transfer |

## Section 26 - Data subject rights

| Right | Implementation |
|-------|----------------|
| Be informed | Privacy notice builder, workbook column inventory |
| Access | Admin responds via data subject request queue; exports for authorised roles only |
| Object | Request type `objection`; sets `processing_restricted` via admin workflow |
| Correction | Request type `correction`; edit workbook or admin correction |
| Erasure | Request type `erasure`; `TilkitPersonalDataService::anonymizeRecord()` |

Public form: `/t/{tool}/data-subject-request`  
Admin queue: **Team → Data protection (Kenya DPA)** (`tools.data-protection` and `tools.tilkit.data-protection`)

Shared UI partial: `resources/views/tools/partials/governance-kenya-dpa.blade.php` on overview, team, and generic tool show.

## Section 30 - Lawful processing

Lawful basis codes on `dc_projects.lawful_basis` match `config/kenya_dpa.lawful_bases` (consent default for community ILK).

## Workbook personal data

Configured in `config/kenya_dpa.php` → `tilkit_personal_data_columns` (AB, O, Z, AA, Y, BC, BB, BG–BK).

Community **consent (BC)** and **do-no-harm (BB)** are mirrored on `tilkit_indicator_records` and enforced on export. **Consent withdrawal** (BC share = 0) sets `processing_restricted` and blocks export.

## Operational checklist (pilot / production)

1. Set controller name, contacts, ODPC registration on **Data protection** screen.
2. Set `TILKIT_DATA_CONTROLLER_*` and `TILKIT_DPO_EMAIL` in `.env` for single-tool deploys.
3. Confirm `retention_months` (default 84) and run scheduler so `tilkit:enforce-retention` executes monthly.
4. Train facilitators on BC levels and BB before external dissemination.
5. Process data subject requests within your organisational SLA (30 days recommended).
6. Document cross-border safeguards before enabling `cross_border_transfers_allowed`.

## Related code

- `App\Services\KenyaDpa\TilkitPersonalDataService`
- `App\Services\KenyaDpa\PrivacyNoticeBuilder`
- `App\Models\DataSubjectRequest`
- `config/kenya_dpa.php`
