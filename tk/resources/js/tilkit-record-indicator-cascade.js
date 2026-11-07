function fillSelect(select, options, placeholder) {
    if (!select) {
        return;
    }
    select.innerHTML = '';
    const first = document.createElement('option');
    first.value = '';
    first.textContent = placeholder;
    select.appendChild(first);
    options.forEach((opt) => {
        const el = document.createElement('option');
        el.value = String(opt.value);
        el.textContent = opt.label;
        if (opt.dataset) {
            Object.entries(opt.dataset).forEach(([k, v]) => {
                el.dataset[k] = v;
            });
        }
        select.appendChild(el);
    });
    select.disabled = options.length === 0;
}

function composeColumnD(behaviour, intensity) {
    const b = (behaviour || '').trim();
    const i = (intensity || '').trim();
    if (!b) {
        return '';
    }
    if (!i) {
        return b;
    }

    return `${b} | Intensity: ${i}`;
}

function parseColumnD(columnD) {
    const raw = (columnD || '').trim();
    if (!raw) {
        return { behaviour: '', intensity: '' };
    }
    const match = raw.match(/^(.+?)\s*\|\s*Intensity:\s*(.+)$/i);
    if (match) {
        return { behaviour: match[1].trim(), intensity: match[2].trim() };
    }

    return { behaviour: raw, intensity: '' };
}

export function bootTilkitRecordIndicatorCascade(root = document) {
    const cascade = root.querySelector('[data-tk-record-indicator-cascade]');
    const form = root.querySelector('[data-tk-record-create-form]');
    if (!cascade || !form) {
        return;
    }

    const optionsUrl = cascade.dataset.optionsUrl;
    const siteSelect = form.querySelector('[data-tk-site-select]');
    const seasonSelect = form.querySelector('[data-tk-season-select]');
    const dateInput = form.querySelector('[data-tk-collection-date]');

    const catSelect = cascade.querySelector('[data-tk-ind-cat]');
    const typeSelect = cascade.querySelector('[data-tk-ind-type]');
    const subSelect = cascade.querySelector('[data-tk-ind-subtype]');
    const specSelect = cascade.querySelector('[data-tk-ind-specific]');
    const behSelect = cascade.querySelector('[data-tk-ind-behaviour]');
    const intensitySelect = cascade.querySelector('[data-tk-ind-intensity]');
    const uidDisplay = cascade.querySelector('[data-tk-ind-uid-display]');
    const idInput = cascade.querySelector('[data-tk-ind-id-input]');
    const wbA = cascade.querySelector('[data-tk-wb-a]');
    const wbB = cascade.querySelector('[data-tk-wb-b]');
    const wbC = cascade.querySelector('[data-tk-wb-c]');
    const wbD = cascade.querySelector('[data-tk-wb-d]');
    const messageEl = cascade.querySelector('[data-tk-ind-message]');

    let payload = { legend: [], indicators: [], intensity_options: [] };

    const syncWorkbookD = () => {
        if (wbD) {
            wbD.value = composeColumnD(behSelect?.value, intensitySelect?.value);
        }
    };

    const applyIntensityOptions = () => {
        const options = (payload.intensity_options || []).map((o) => ({
            value: o.value,
            label: o.label || o.value,
        }));
        fillSelect(intensitySelect, options, options.length ? 'Choose intensity' : '—');
    };

    const showMessage = (lines) => {
        if (!messageEl) {
            return;
        }
        const text = (lines || []).join(' ');
        messageEl.textContent = text;
        messageEl.classList.toggle('hidden', !text);
    };

    const resetDownstream = (from) => {
        if (from <= 1) {
            fillSelect(typeSelect, [], '—');
        }
        if (from <= 2) {
            fillSelect(subSelect, [], '—');
        }
        if (from <= 3) {
            fillSelect(specSelect, [], '—');
        }
        if (from <= 4) {
            fillSelect(behSelect, [], '—');
        }
        if (from <= 4) {
            fillSelect(intensitySelect, [], '—');
        }
        if (from <= 5) {
            if (uidDisplay) {
                uidDisplay.value = '';
            }
            if (idInput) {
                idInput.value = '';
            }
            [wbA, wbB, wbC, wbD].forEach((el) => {
                if (el) {
                    el.value = '';
                }
            });
        }
    };

    const applyCategories = () => {
        resetDownstream(1);
        applyIntensityOptions();
        const options = payload.legend.map((c) => ({
            value: c.key,
            label: `${c.key} · ${c.label}`,
        }));
        fillSelect(catSelect, options, options.length ? 'Choose category' : 'No codes for this context');
        catSelect.disabled = options.length === 0;
    };

    const onCategory = () => {
        resetDownstream(2);
        const cat = catSelect?.value;
        if (!cat) {
            return;
        }
        const category = payload.legend.find((c) => c.key === cat);
        if (!category) {
            return;
        }
        const options = category.types.map((t) => ({
            value: t.key,
            label: `${t.key} · ${t.label}`,
        }));
        fillSelect(typeSelect, options, 'Choose type');
    };

    const onType = () => {
        resetDownstream(3);
        const cat = catSelect?.value;
        const typeKey = typeSelect?.value;
        if (!cat || !typeKey) {
            return;
        }
        const category = payload.legend.find((c) => c.key === cat);
        const type = category?.types.find((t) => t.key === typeKey);
        if (!type) {
            return;
        }
        const options = type.codes.map((code) => ({
            value: String(code.id),
            label: `${code.code}${code.classification_label ? ` — ${code.classification_label}` : ''}`,
            dataset: {
                code: code.code,
                behaviours: JSON.stringify(code.behaviours || []),
                workbook: JSON.stringify(code.workbook || {}),
            },
        }));
        fillSelect(subSelect, options, 'Choose sub-type / code');
    };

    const onSubtype = () => {
        resetDownstream(4);
        const codeOpt = subSelect?.selectedOptions[0];
        const codeId = Number(subSelect?.value);
        if (!codeId || !codeOpt) {
            return;
        }

        try {
            const wb = JSON.parse(codeOpt.dataset.workbook || '{}');
            if (wbA) {
                wbA.value = wb.A || '';
            }
            if (wbB) {
                wbB.value = wb.B || '';
            }
            if (wbC) {
                wbC.value = '';
            }
            if (wbD) {
                wbD.value = '';
            }
        } catch {
            /* ignore */
        }

        let behaviours = [];
        try {
            behaviours = JSON.parse(codeOpt.dataset.behaviours || '[]');
        } catch {
            behaviours = [];
        }
        fillSelect(
            behSelect,
            behaviours.map((b) => ({ value: b, label: b })),
            behaviours.length ? 'Choose observable behaviour' : 'No behaviours for this code',
        );
        applyIntensityOptions();

        const indicators = payload.indicators.filter((i) => Number(i.indicator_code_id) === codeId);
        const options = indicators.map((i) => ({
            value: String(i.id),
            label: `${i.indicator_uid} — ${i.local_name || 'Registered sign'}`,
            dataset: {
                uid: i.indicator_uid,
                wb: JSON.stringify(i.workbook || {}),
            },
        }));
        fillSelect(specSelect, options, indicators.length ? 'Choose registered sign' : 'No registered signs for this code');
    };

    const onSpecific = () => {
        const opt = specSelect?.selectedOptions[0];
        if (!opt || !opt.value) {
            return;
        }
        if (uidDisplay) {
            uidDisplay.value = opt.dataset.uid || '';
        }
        if (idInput) {
            idInput.value = opt.value;
        }
        try {
            const wb = JSON.parse(opt.dataset.wb || '{}');
            if (wbA) {
                wbA.value = wb.A || '';
            }
            if (wbB) {
                wbB.value = wb.B || '';
            }
            if (wbC) {
                wbC.value = wb.C || '';
            }
            const parsed = parseColumnD(wb.D || '');
            if (parsed.behaviour && behSelect) {
                behSelect.value = parsed.behaviour;
            }
            if (parsed.intensity && intensitySelect) {
                intensitySelect.value = parsed.intensity;
            }
            syncWorkbookD();
        } catch {
            /* ignore */
        }
    };

    const onBehaviourOrIntensity = () => {
        syncWorkbookD();
    };

    const restoreIndicatorSelection = () => {
        const indicatorId = Number(idInput?.value);
        if (!indicatorId || !payload.indicators?.length) {
            return;
        }
        const ind = payload.indicators.find((i) => Number(i.id) === indicatorId);
        if (!ind) {
            return;
        }
        const codeId = Number(ind.indicator_code_id);
        for (const cat of payload.legend) {
            for (const type of cat.types) {
                const code = type.codes.find((c) => Number(c.id) === codeId);
                if (!code) {
                    continue;
                }
                catSelect.value = cat.key;
                onCategory();
                typeSelect.value = type.key;
                onType();
                subSelect.value = String(code.id);
                onSubtype();
                specSelect.value = String(indicatorId);
                onSpecific();
                if (wbD?.value) {
                    const parsed = parseColumnD(wbD.value);
                    if (parsed.behaviour && behSelect) {
                        behSelect.value = parsed.behaviour;
                    }
                    if (parsed.intensity && intensitySelect) {
                        intensitySelect.value = parsed.intensity;
                    }
                }
                return;
            }
        }
    };

    catSelect?.addEventListener('change', onCategory);
    typeSelect?.addEventListener('change', onType);
    subSelect?.addEventListener('change', onSubtype);
    specSelect?.addEventListener('change', onSpecific);
    behSelect?.addEventListener('change', onBehaviourOrIntensity);
    intensitySelect?.addEventListener('change', onBehaviourOrIntensity);

    let loadToken = 0;
    const refreshOptions = async () => {
        const siteId = siteSelect?.value;
        const seasonId = seasonSelect?.value;
        const collectionDate = dateInput?.value;
        if (!siteId || !seasonId || !collectionDate || !optionsUrl) {
            fillSelect(catSelect, [], 'Select site, season, and date first');
            catSelect.disabled = true;
            resetDownstream(1);
            showMessage([]);
            return;
        }

        const token = ++loadToken;
        showMessage([]);
        fillSelect(catSelect, [], 'Loading…');
        catSelect.disabled = true;

        try {
            const qs = new URLSearchParams({
                site_id: siteId,
                season_id: seasonId,
                collection_date: collectionDate,
            });
            const res = await fetch(`${optionsUrl}?${qs.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                throw new Error('load failed');
            }
            const data = await res.json();
            if (token !== loadToken) {
                return;
            }
            payload = data;
            showMessage(data.messages || []);
            applyCategories();
            restoreIndicatorSelection();
        } catch {
            if (token !== loadToken) {
                return;
            }
            fillSelect(catSelect, [], 'Could not load options');
            showMessage(['Could not load indicator options.']);
        }
    };

    siteSelect?.addEventListener('change', refreshOptions);
    seasonSelect?.addEventListener('change', refreshOptions);
    dateInput?.addEventListener('change', refreshOptions);

    form.addEventListener('tk-form-draft-restored', () => {
        refreshOptions();
    });

    refreshOptions();
}
