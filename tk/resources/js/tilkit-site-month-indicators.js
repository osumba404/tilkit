import {
    clearCustomDraft,
    loadCustomDraft,
    saveCustomDraft,
    showDraftRestoredToast,
} from './tk-form-draft';

const AUTOSAVE_DELAY_MS = 1200;

function urlFromTemplate(template, siteId) {
    return template.replace('__SITE__', encodeURIComponent(String(siteId)));
}

function emptyAssignments() {
    const out = {};
    for (let m = 1; m <= 12; m += 1) {
        out[m] = [];
    }
    return out;
}

function assignmentsFingerprint(assignments) {
    return JSON.stringify(assignments);
}

/** @param {{ types: { codes: { id: number }[] }[] }} category */
function codeIdsInCategory(category) {
    const ids = [];
    category.types.forEach((type) => {
        type.codes.forEach((code) => ids.push(Number(code.id)));
    });
    return ids;
}

function renderGrid(container, legend, monthLabels, assignments, activeMonth) {
    const month = activeMonth;
    const selected = new Set((assignments[month] || []).map((id) => Number(id)));

    let html = '<div class="flex flex-wrap gap-1 p-3 border-b border-tk-line bg-tk-soft/30">';
    for (let m = 1; m <= 12; m += 1) {
        const label = monthLabels[m] || `Month ${m}`;
        const count = (assignments[m] || []).length;
        const active = m === month ? 'tk-wizard-tab-active' : 'tk-wizard-tab-inactive';
        html += `<button type="button" class="tk-wizard-tab text-xs py-1 px-2 ${active}" data-tk-month-tab="${m}">${label}${count ? ` (${count})` : ''}</button>`;
    }
    html += '</div><div class="p-4 space-y-4 max-h-[28rem] overflow-y-auto" data-tk-site-indicator-scroll>';

    legend.forEach((category) => {
        html += `<div class="border-b border-tk-line/50 pb-4 last:border-0" data-tk-ind-category="${category.key}">`;
        html += `<div class="flex flex-wrap items-center gap-2 mb-2">`;
        html += `<h4 class="text-xs font-semibold uppercase tracking-wide text-tk-muted flex-1 min-w-[10rem]">${category.key} · ${category.label}</h4>`;
        html += `<div class="flex flex-wrap gap-2 shrink-0" role="group" aria-label="Bulk actions for ${category.label}">`;
        html += `<button type="button" class="tk-btn-secondary text-xs py-1 px-2.5 font-semibold" data-tk-cat-select-all="${category.key}">Select all</button>`;
        html += `<button type="button" class="tk-btn-secondary text-xs py-1 px-2.5 font-semibold" data-tk-cat-clear-all="${category.key}">Clear all</button>`;
        html += `</div></div>`;
        category.types.forEach((type) => {
            html += `<div class="mb-3"><p class="text-xs font-medium text-tk-ink mb-1">${type.key} · ${type.label}</p><ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-1 text-sm">`;
            type.codes.forEach((code) => {
                const checked = selected.has(Number(code.id)) ? 'checked' : '';
                html += `<li><label class="flex gap-2 items-start cursor-pointer"><input type="checkbox" class="mt-1" data-tk-code-id="${code.id}" ${checked}><span class="font-mono text-xs">${code.code}</span><span class="text-tk-muted text-xs">${code.label || ''}</span></label></li>`;
            });
            html += '</ul></div>';
        });
        html += '</div>';
    });

    html += '</div>';
    container.innerHTML = html;
}

function syncGridUi(container, monthLabels, assignments, activeMonth) {
    const selected = new Set((assignments[activeMonth] || []).map((id) => Number(id)));

    container.querySelectorAll('[data-tk-month-tab]').forEach((btn) => {
        const m = Number(btn.dataset.tkMonthTab);
        const label = monthLabels[m] || `Month ${m}`;
        const count = (assignments[m] || []).length;
        btn.textContent = `${label}${count ? ` (${count})` : ''}`;
        btn.classList.toggle('tk-wizard-tab-active', m === activeMonth);
        btn.classList.toggle('tk-wizard-tab-inactive', m !== activeMonth);
    });

    container.querySelectorAll('input[data-tk-code-id]').forEach((input) => {
        input.checked = selected.has(Number(input.dataset.tkCodeId));
    });
}

export function bootTilkitSiteMonthIndicators(root = document) {
    const panel = root.querySelector('[data-tilkit-site-month-indicators]');
    if (!panel) {
        return;
    }

    const legend = JSON.parse(panel.dataset.legend || '[]');
    const monthLabels = JSON.parse(panel.dataset.monthLabels || '{}');
    const sites = JSON.parse(panel.dataset.sites || '[]');
    const loadTemplate = panel.dataset.loadUrlTemplate || '';
    const saveTemplate = panel.dataset.saveUrlTemplate || '';

    const siteSelect = panel.querySelector('[data-tk-site-indicator-select]');
    const grid = panel.querySelector('[data-tk-site-indicator-grid]');
    const statusEl = panel.querySelector('[data-tk-site-indicator-status]');
    const seasonsEl = panel.querySelector('[data-tk-site-indicator-seasons]');
    const saveBtn = panel.querySelector('[data-tk-site-indicator-save]');

    let assignments = emptyAssignments();
    let activeMonth = 1;
    let loadedSiteId = null;
    let lastSavedFingerprint = assignmentsFingerprint(emptyAssignments());
    let autosaveTimer = null;
    let saveInFlight = null;

    const draftScopeBase = panel.dataset.draftScope || `tilkit:site-month-indicators:${window.location.pathname}`;

    const draftScopeForSite = (siteId) => `${draftScopeBase}:${siteId}`;

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const persistLocalDraft = (siteId) => {
        if (!siteId) {
            return;
        }
        saveCustomDraft(draftScopeForSite(siteId), { assignments, activeMonth });
    };

    const setStatus = (msg, isError = false) => {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = msg;
        statusEl.classList.toggle('text-amber-800', isError);
        statusEl.classList.toggle('text-tk-muted', !isError);
    };

    const applySeasonSummaries = (seasons) => {
        if (!seasonsEl) {
            return;
        }
        const parts = (seasons || []).map(
            (s) => `${s.label}: ${s.code_count} unique code(s) across its months`,
        );
        seasonsEl.textContent = parts.length ? `Season union: ${parts.join(' · ')}` : '';
        seasonsEl.classList.toggle('hidden', !parts.length);
    };

    const applyServerPayload = (data) => {
        assignments = { ...emptyAssignments(), ...(data.assignments || {}) };
        lastSavedFingerprint = assignmentsFingerprint(assignments);
        applySeasonSummaries(data.seasons);
    };

    const refreshUi = () => {
        syncGridUi(grid, monthLabels, assignments, activeMonth);
    };

    const saveToServer = async (siteId, { keepalive = false, quiet = false } = {}) => {
        if (!siteId) {
            return false;
        }
        const fp = assignmentsFingerprint(assignments);
        if (fp === lastSavedFingerprint) {
            return true;
        }

        if (saveInFlight) {
            await saveInFlight;
            if (assignmentsFingerprint(assignments) === lastSavedFingerprint) {
                return true;
            }
        }

        if (!quiet) {
            setStatus('Saving to server…');
        }

        const run = (async () => {
            try {
                const res = await fetch(urlFromTemplate(saveTemplate, siteId), {
                    method: 'PUT',
                    keepalive,
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({ assignments }),
                });

                if (res.status === 401 || res.status === 419) {
                    persistLocalDraft(siteId);
                    setStatus('Session expired — changes kept in this browser. Sign in again to sync.', true);
                    return false;
                }

                if (!res.ok) {
                    throw new Error('Save failed');
                }

                const data = await res.json();
                applyServerPayload(data);
                clearCustomDraft(draftScopeForSite(siteId));
                refreshUi();
                setStatus('All changes saved to the server.');
                return true;
            } catch {
                persistLocalDraft(siteId);
                setStatus('Could not reach the server — changes kept in this browser and will retry.', true);
                return false;
            } finally {
                saveInFlight = null;
            }
        })();

        saveInFlight = run;
        return run;
    };

    const scheduleAutosave = (siteId) => {
        persistLocalDraft(siteId);
        const fp = assignmentsFingerprint(assignments);
        if (fp === lastSavedFingerprint) {
            return;
        }
        setStatus('Saving automatically…');
        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(() => {
            saveToServer(siteId, { quiet: true });
        }, AUTOSAVE_DELAY_MS);
    };

    const flushAutosave = (siteId) => {
        window.clearTimeout(autosaveTimer);
        const fp = assignmentsFingerprint(assignments);
        if (!siteId || fp === lastSavedFingerprint) {
            return;
        }
        persistLocalDraft(siteId);
        saveToServer(siteId, { keepalive: true, quiet: true });
    };

    const onAssignmentsChanged = () => {
        refreshUi();
        scheduleAutosave(siteSelect?.value || loadedSiteId);
    };

    grid.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        const monthTab = target.closest('[data-tk-month-tab]');
        if (monthTab) {
            event.preventDefault();
            activeMonth = Number(monthTab.dataset.tkMonthTab);
            persistLocalDraft(siteSelect?.value || loadedSiteId);
            refreshUi();
            return;
        }

        const selectAllBtn = target.closest('[data-tk-cat-select-all]');
        if (selectAllBtn) {
            event.preventDefault();
            const key = selectAllBtn.dataset.tkCatSelectAll;
            const category = legend.find((c) => c.key === key);
            if (!category) {
                return;
            }
            const list = new Set((assignments[activeMonth] || []).map(Number));
            codeIdsInCategory(category).forEach((id) => list.add(id));
            assignments[activeMonth] = [...list];
            onAssignmentsChanged();
            return;
        }

        const clearAllBtn = target.closest('[data-tk-cat-clear-all]');
        if (clearAllBtn) {
            event.preventDefault();
            const key = clearAllBtn.dataset.tkCatClearAll;
            const category = legend.find((c) => c.key === key);
            if (!category) {
                return;
            }
            const remove = new Set(codeIdsInCategory(category));
            const list = (assignments[activeMonth] || []).map(Number).filter((id) => !remove.has(id));
            assignments[activeMonth] = list;
            onAssignmentsChanged();
        }
    });

    grid.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || !target.matches('input[data-tk-code-id]')) {
            return;
        }
        const id = Number(target.dataset.tkCodeId);
        const list = new Set((assignments[activeMonth] || []).map(Number));
        if (target.checked) {
            list.add(id);
        } else {
            list.delete(id);
        }
        assignments[activeMonth] = [...list];
        onAssignmentsChanged();
    });

    const loadSite = async (siteId) => {
        if (!siteId) {
            return;
        }
        setStatus('Loading…');
        try {
            const res = await fetch(urlFromTemplate(loadTemplate, siteId), {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                throw new Error('Could not load catalogue');
            }
            const data = await res.json();
            applyServerPayload(data);
            loadedSiteId = siteId;

            const localDraft = loadCustomDraft(draftScopeForSite(siteId));
            const localFp = localDraft?.data?.assignments
                ? assignmentsFingerprint({ ...emptyAssignments(), ...localDraft.data.assignments })
                : null;
            if (
                localDraft?.data?.assignments
                && document.body.dataset.tkValidationErrors !== '1'
                && localFp !== lastSavedFingerprint
            ) {
                assignments = { ...emptyAssignments(), ...localDraft.data.assignments };
                activeMonth = Number(localDraft.data.activeMonth) || activeMonth;
                showDraftRestoredToast('Restored catalogue from this browser — syncing to server.');
                scheduleAutosave(siteId);
            }

            renderGrid(grid, legend, monthLabels, assignments, activeMonth);
            if (localFp === lastSavedFingerprint || !localDraft?.data?.assignments) {
                setStatus('Changes save automatically in this browser and to the server.');
            }
        } catch {
            setStatus('Failed to load catalogue for this site.', true);
        }
    };

    siteSelect?.addEventListener('change', async () => {
        const nextSiteId = siteSelect.value;
        if (loadedSiteId && loadedSiteId !== nextSiteId) {
            flushAutosave(loadedSiteId);
        }
        await loadSite(nextSiteId);
    });

    saveBtn?.addEventListener('click', async () => {
        const siteId = siteSelect?.value;
        if (!siteId) {
            return;
        }
        window.clearTimeout(autosaveTimer);
        saveBtn.disabled = true;
        await saveToServer(siteId);
        saveBtn.disabled = false;
    });

    window.addEventListener('pagehide', () => {
        flushAutosave(siteSelect?.value || loadedSiteId);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flushAutosave(siteSelect?.value || loadedSiteId);
        }
    });

    if (sites.length && siteSelect) {
        loadSite(siteSelect.value);
    }
}
