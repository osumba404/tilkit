# TILKIT GitHub Project Board

This folder defines the **TILKIT Platform Roadmap** as GitHub milestones, labels, issues, and a Projects v2 board.

## One-time setup

1. Install [GitHub CLI](https://cli.github.com/): `winget install GitHub.cli`
2. Authenticate: `gh auth login`
3. From the **repository root** (`tilkit/`, not only `tk/`):

```powershell
powershell -ExecutionPolicy Bypass -File .github/tilkit-project/create-github-project.ps1
```

The script will:

- Ensure labels (`tilkit`, `epic`, `phase-0` … `phase-6`, etc.)
- Create milestones **Phase 0 - Foundation** through **Phase 6 - Pilots**
- Open **26 issues** from `issues.json` (7 epics + deliverables)
- Create project **TILKIT Platform Roadmap** and add all issues

## Board columns (recommended)

In GitHub Project settings, use status columns:

| Column | Use for |
|--------|---------|
| **Backlog** | Not started |
| **Ready** | Spec clear, unblocked |
| **In progress** | Active sprint |
| **Review** | PR / parity tests |
| **Done** | Merged / verified |

Optional **Phase** single-select field (script attempts to create it) for filtering.

## Issue map

| Milestone | Epic | Focus |
|-----------|------|--------|
| Phase 0 | Foundation | Spec, open questions, fixtures |
| Phase 1 | Schema | DB, seeds, admin |
| Phase 2 | Engine | Gate, weights, tally, golden tests |
| Phase 3 | Mobile | Sync API, offline sightings |
| Phase 4 | Workshop | FGD UX, validation queues |
| Phase 5 | Integration | NMHS ingest, v4 modules, exports |
| Phase 6 | Pilots | Excel import, SCLoWFER, Green Schools |

## Re-run safely

The script creates **new issues** each run. Do not run twice on the same repo unless you delete duplicates first. To refresh definitions, edit `issues.json` and create issues manually, or extend the script to skip existing titles.

## Source documents

Requirements live in `tilkit_docs/` (v4 workbook = acceptance test). See the chat work plan and `tilkit_docs/_extracted.txt` for the Developer Brief text.
