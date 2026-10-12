function parseRelevanceExpression(expr, values) {
    if (!expr || !String(expr).trim()) {
        return true;
    }
    const e = String(expr).trim();
    let m = e.match(/^([a-z0-9_]+)\s*!=\s*(.+)$/i);
    if (m) {
        return String(values[m[1]] ?? '') !== m[2].trim().replace(/^['"]|['"]$/g, '');
    }
    m = e.match(/^([a-z0-9_]+)\s*=\s*(.+)$/i);
    if (m) {
        return String(values[m[1]] ?? '') === m[2].trim().replace(/^['"]|['"]$/g, '');
    }
    if (e.endsWith('.empty')) {
        const name = e.slice(0, -6);
        const v = values[name];
        return v === null || v === undefined || v === '' || (Array.isArray(v) && v.length === 0);
    }
    if (/^[a-z0-9_]+$/i.test(e)) {
        const v = values[e];
        return !(v === null || v === undefined || v === '' || v === false);
    }
    return true;
}

function collectFormValues(form) {
    const values = {};
    form.querySelectorAll('[data-field-name]').forEach((wrap) => {
        const name = wrap.dataset.fieldName;
        if (!name) {
            return;
        }
        const input = wrap.querySelector(`[name="${name}"], [name="${name}[]"]`);
        if (input?.type === 'checkbox' && input.name.endsWith('[]')) {
            values[name] = [...form.querySelectorAll(`[name="${name}[]"]:checked`)].map((el) => el.value);
            return;
        }
        const el = wrap.querySelector(`[name="${name}"]`) || wrap.querySelector(`[name^="${name}["]`);
        if (!el) {
            return;
        }
        if (el.type === 'checkbox') {
            values[name] = el.checked;
        } else {
            values[name] = el.value;
        }
    });
    return values;
}

function applyRelevance(form) {
    const values = collectFormValues(form);
    form.querySelectorAll('[data-relevant-expr]').forEach((wrap) => {
        const visible = parseRelevanceExpression(wrap.dataset.relevantExpr, values);
        wrap.hidden = !visible;
        wrap.setAttribute('aria-hidden', visible ? 'false' : 'true');
        wrap.querySelectorAll('input, select, textarea').forEach((input) => {
            input.disabled = !visible;
        });
    });
}

function initRepeatGroups(form) {
    form.querySelectorAll('[data-repeat-group]').forEach((group) => {
        const addBtn = group.querySelector('[data-repeat-add]');
        const rows = group.querySelector('[data-repeat-rows]');
        const templateRow = group.querySelector('[data-repeat-row]');
        if (!addBtn || !rows || !templateRow) {
            return;
        }

        addBtn.addEventListener('click', () => {
            const index = rows.querySelectorAll('[data-repeat-row]').length;
            const clone = templateRow.cloneNode(true);
            clone.dataset.rowIndex = String(index);
            clone.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/\[0\]/, `[${index}]`);
                input.id = `${input.id || input.name}-${index}`.replace(/[^a-z0-9_-]/gi, '_');
                if (input.type !== 'hidden') {
                    input.value = '';
                }
                input.checked = false;
            });
            rows.appendChild(clone);
            applyRelevance(form);
        });
    });
}

function initDraftSave(form) {
    const saveUrl = form.dataset.draftSaveUrl;
    if (!saveUrl) {
        return;
    }
    const btn = form.querySelector('[data-wizard-save-draft]');
    const status = form.querySelector('[data-draft-status]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    const payloadFromForm = () => {
        const data = new FormData(form);
        const payload = {};
        for (const [key, value] of data.entries()) {
            if (key.startsWith('_') || key === 'confirm_duplicate') {
                continue;
            }
            payload[key] = value;
        }
        return payload;
    };

    const applyPayload = (payload) => {
        Object.entries(payload).forEach(([key, value]) => {
            const el = form.elements.namedItem(key);
            if (!el) {
                return;
            }
            if (el instanceof RadioNodeList) {
                [...el].forEach((node) => {
                    node.checked = node.value === value;
                });
            } else if (el.type === 'checkbox') {
                el.checked = value === '1' || value === true || value === 'on';
            } else {
                el.value = value;
            }
        });
        applyRelevance(form);
    };

    if (form.dataset.draftPayload) {
        try {
            applyPayload(JSON.parse(form.dataset.draftPayload));
        } catch {
            /* ignore */
        }
    }

    btn?.addEventListener('click', async () => {
        btn.disabled = true;
        try {
            const res = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ payload: payloadFromForm() }),
                credentials: 'same-origin',
            });
            if (res.ok && status) {
                status.textContent = status.dataset.savedLabel || 'Draft saved.';
                status.hidden = false;
            }
        } finally {
            btn.disabled = false;
        }
    });
}

function initFormWizard() {
    const form = document.querySelector('[data-form-wizard]');
    if (!form) {
        return;
    }

    const pages = [...form.querySelectorAll('[data-wizard-page]')];
    const prevBtn = form.querySelector('[data-wizard-prev]');
    const nextBtn = form.querySelector('[data-wizard-next]');
    const submitBtn = form.querySelector('[data-wizard-submit]');
    const progressLabel = form.querySelector('[data-wizard-progress]');
    const progressBar = form.querySelector('[data-wizard-progress-bar]');

    let current = 0;

    initRepeatGroups(form);
    form.addEventListener('input', () => applyRelevance(form));
    form.addEventListener('change', () => applyRelevance(form));
    applyRelevance(form);
    initDraftSave(form);

    const visibleFields = (page) =>
        [...page.querySelectorAll('input, select, textarea')].filter(
            (field) => !field.disabled && field.type !== 'hidden' && !field.closest('[hidden]'),
        );

    const validatePage = (index) => {
        const page = pages[index];
        for (const field of visibleFields(page)) {
            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }
        return true;
    };

    const syncUi = () => {
        pages.forEach((page, idx) => {
            const active = idx === current;
            page.hidden = !active;
            page.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        if (prevBtn) {
            prevBtn.hidden = current === 0;
        }
        if (nextBtn) {
            nextBtn.hidden = current >= pages.length - 1;
        }
        if (submitBtn) {
            submitBtn.hidden = current < pages.length - 1;
        }

        const total = pages.length;
        const step = current + 1;
        if (progressLabel) {
            progressLabel.textContent = `Section ${step} of ${total}`;
        }
        if (progressBar && total > 0) {
            progressBar.style.width = `${Math.round((step / total) * 100)}%`;
        }

        const activePage = pages[current];
        activePage?.setAttribute('tabindex', '-1');
        activePage?.focus({ preventScroll: true });
    };

    prevBtn?.addEventListener('click', () => {
        if (current > 0) {
            current -= 1;
            syncUi();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    nextBtn?.addEventListener('click', () => {
        if (!validatePage(current)) {
            return;
        }
        if (current < pages.length - 1) {
            current += 1;
            syncUi();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    form.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft' && event.altKey && current > 0) {
            event.preventDefault();
            current -= 1;
            syncUi();
        }
        if (event.key === 'ArrowRight' && event.altKey && current < pages.length - 1) {
            event.preventDefault();
            if (validatePage(current)) {
                current += 1;
                syncUi();
            }
        }
    });

    form.addEventListener('submit', (event) => {
        if (current < pages.length - 1) {
            event.preventDefault();
            return;
        }
        for (let i = 0; i < pages.length; i += 1) {
            if (!validatePage(i)) {
                event.preventDefault();
                current = i;
                syncUi();
                return;
            }
        }
    });

    syncUi();
}

initFormWizard();
