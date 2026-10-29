const STORAGE_PREFIX = 'tk-form-draft:v1:';

const SKIP_ACTION_FRAGMENTS = [
    '/logout',
    '/login',
    '/register',
    '/password',
    '/verification',
    'program.switch',
];

function storageKey(scope) {
    return STORAGE_PREFIX + scope;
}

export function loadCustomDraft(scope) {
    try {
        const raw = localStorage.getItem(storageKey(scope));
        if (!raw) {
            return null;
        }
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch {
        return null;
    }
}

export function saveCustomDraft(scope, data) {
    try {
        localStorage.setItem(
            storageKey(scope),
            JSON.stringify({ savedAt: Date.now(), data }),
        );
    } catch {
        /* quota or private mode */
    }
}

export function clearCustomDraft(scope) {
    try {
        localStorage.removeItem(storageKey(scope));
    } catch {
        /* ignore */
    }
}

export function showDraftRestoredToast(message = 'Restored unsaved work from this browser.') {
    if (document.querySelector('[data-tk-draft-toast-active]')) {
        return;
    }
    const el = document.createElement('div');
    el.className = 'tk-flash-toast';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.dataset.tkDraftToastActive = '1';
    el.innerHTML = `<p class="tk-flash-toast-message">${message}</p><button type="button" class="tk-flash-toast-dismiss" aria-label="Dismiss">×</button>`;
    el.querySelector('.tk-flash-toast-dismiss')?.addEventListener('click', () => el.remove());
    document.body.appendChild(el);
    window.setTimeout(() => el.remove(), 4500);
}

function shouldSkipForm(form) {
    if (form.dataset.tkFormDraft === 'off') {
        return true;
    }
    const action = (form.getAttribute('action') || '').toLowerCase();
    if (SKIP_ACTION_FRAGMENTS.some((frag) => action.includes(frag))) {
        return true;
    }
    if (form.dataset.confirm && form.querySelector('[type="submit"][formnovalidate]')) {
        return false;
    }
    return false;
}

function scopeForForm(form) {
    if (form.dataset.tkFormDraft && form.dataset.tkFormDraft !== 'auto') {
        return form.dataset.tkFormDraft;
    }
    const action = form.getAttribute('action') || window.location.pathname;
    const id = form.id || '';
    return `${window.location.pathname}|${action}|${id}`;
}

function isSkippedField(el) {
    if (!el.name || el.disabled) {
        return true;
    }
    if (el.type === 'file' || el.type === 'password') {
        return true;
    }
    if (el.name === '_token' || el.name === '_method') {
        return true;
    }
    return false;
}

export function serializeFormFields(form) {
    /** @type {Record<string, string | string[]>} */
    const out = {};

    [...form.elements].forEach((el) => {
        if (!(el instanceof HTMLInputElement || el instanceof HTMLSelectElement || el instanceof HTMLTextAreaElement)) {
            return;
        }
        if (isSkippedField(el)) {
            return;
        }

        const name = el.name;
        if (el instanceof HTMLInputElement && el.type === 'checkbox') {
            if (el.name.endsWith('[]')) {
                const base = el.name;
                if (!Array.isArray(out[base])) {
                    out[base] = [];
                }
                if (el.checked) {
                    out[base].push(el.value || '1');
                }
            } else {
                out[name] = el.checked ? (el.value || '1') : '0';
            }
            return;
        }

        if (el instanceof HTMLInputElement && el.type === 'radio') {
            if (el.checked) {
                out[name] = el.value;
            } else if (!(name in out)) {
                out[name] = '';
            }
            return;
        }

        if (el instanceof HTMLSelectElement && el.multiple) {
            out[name] = [...el.selectedOptions].map((o) => o.value);
            return;
        }

        out[name] = el.value;
    });

    return out;
}

/** @param {Record<string, string | string[]>} fields */
export function applyFormFields(form, fields) {
    Object.entries(fields).forEach(([name, value]) => {
        const els = form.querySelectorAll(`[name="${CSS.escape(name)}"]`);
        if (!els.length) {
            return;
        }

        const first = els[0];
        if (first instanceof HTMLInputElement && first.type === 'checkbox' && !name.endsWith('[]')) {
            const checked = value === '1' || value === 'on' || value === true || value === first.value;
            els.forEach((el) => {
                if (el instanceof HTMLInputElement) {
                    el.checked = checked;
                }
            });
            return;
        }

        if (first instanceof HTMLInputElement && first.type === 'radio') {
            els.forEach((el) => {
                if (el instanceof HTMLInputElement) {
                    el.checked = el.value === String(value);
                }
            });
            return;
        }

        if (first instanceof HTMLSelectElement && first.multiple && Array.isArray(value)) {
            [...first.options].forEach((opt) => {
                opt.selected = value.includes(opt.value);
            });
            return;
        }

        els.forEach((el) => {
            if (el instanceof HTMLInputElement || el instanceof HTMLSelectElement || el instanceof HTMLTextAreaElement) {
                el.value = Array.isArray(value) ? value[0] ?? '' : String(value ?? '');
            }
        });
    });
}

function formHasUserInput(form) {
    return Object.values(serializeFormFields(form)).some((v) => {
        if (Array.isArray(v)) {
            return v.length > 0;
        }
        return String(v ?? '').trim() !== '';
    });
}

function validationErrorsOnPage() {
    return document.body.dataset.tkValidationErrors === '1';
}

function debounce(fn, ms) {
    let t = null;
    return (...args) => {
        window.clearTimeout(t);
        t = window.setTimeout(() => fn(...args), ms);
    };
}

function meaningfulFieldCount(form) {
    let count = 0;
    [...form.elements].forEach((el) => {
        if (el instanceof HTMLButtonElement) {
            return;
        }
        if (el instanceof HTMLInputElement || el instanceof HTMLSelectElement || el instanceof HTMLTextAreaElement) {
            if (!isSkippedField(el)) {
                count += 1;
            }
        }
    });
    return count;
}

/**
 * @param {HTMLFormElement} form
 * @param {{ scope?: string, getMeta?: () => Record<string, unknown>, applyMeta?: (meta: Record<string, unknown>) => void, silentRestore?: boolean }} [options]
 */
export function initFormLocalDraft(form, options = {}) {
    if (form.dataset.tkFormDraftBound === '1') {
        return;
    }
    form.dataset.tkFormDraftBound = '1';

    const scope = options.scope || scopeForForm(form);
    const debouncedSave = debounce(() => {
        const fields = serializeFormFields(form);
        const meta = options.getMeta?.() ?? {};
        if (!formHasUserInput(form) && Object.keys(meta).length === 0) {
            clearCustomDraft(scope);
            return;
        }
        saveCustomDraft(scope, { fields, ...meta });
    }, 400);

    const draft = loadCustomDraft(scope);
    if (draft?.data?.fields && !validationErrorsOnPage()) {
        applyFormFields(form, draft.data.fields);
        options.applyMeta?.(draft.data);
        form.dispatchEvent(new CustomEvent('tk-form-draft-restored', {
            bubbles: true,
            detail: { scope, fields: draft.data.fields, meta: draft.data },
        }));
        if (!options.silentRestore) {
            showDraftRestoredToast();
        }
    }

    form.addEventListener('input', debouncedSave);
    form.addEventListener('change', debouncedSave);
    form.addEventListener('submit', () => {
        clearCustomDraft(scope);
    });
}

function bindFormDraft(form) {
    if (shouldSkipForm(form)) {
        return;
    }
    initFormLocalDraft(form, {
        scope: form.dataset.tkFormDraft && form.dataset.tkFormDraft !== 'auto' ? form.dataset.tkFormDraft : scopeForForm(form),
    });
}

export function bootFormDraftPersistence(root = document) {
    root.querySelectorAll('form').forEach((form) => {
        if (form.dataset.tkFormDraft === 'off') {
            return;
        }
        const method = (form.getAttribute('method') || 'get').toUpperCase();
        const explicit = form.dataset.tkFormDraft === 'on' || (form.dataset.tkFormDraft && form.dataset.tkFormDraft !== 'auto');
        if (method === 'GET' && !explicit) {
            return;
        }
        if (method !== 'POST' && !explicit) {
            return;
        }
        if (!form.closest('.tk-page-content, main')) {
            return;
        }
        if (!explicit && meaningfulFieldCount(form) < 3) {
            return;
        }
        bindFormDraft(form);
    });
}
