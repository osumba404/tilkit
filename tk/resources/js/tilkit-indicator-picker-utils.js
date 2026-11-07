/** Shared labels and column-D helpers for indicator cascades and workbook fields. */

export function fillSelect(select, options, placeholder) {
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
    if (Object.prototype.hasOwnProperty.call(options, 'length')) {
        select.disabled = options.length === 0;
    }
}

export function composeColumnD(behaviour, intensity) {
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

export function parseColumnD(columnD) {
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

export function categoryOptionLabel(category) {
    return `${category.key} · ${category.label}`;
}

export function typeOptionLabel(type) {
    return `${type.key} · ${type.label}`;
}

/** @param {{ code: string, classification_label?: string|null, label?: string|null }} code */
export function legendCodeOptionLabel(code) {
    const base = code.code || '';
    const tail = code.classification_label || code.label || '';
    return tail ? `${base} — ${tail}` : base;
}

/** Checkbox / list label (code + classification, optional type line). */
export function legendCodeListLabel(code) {
    const main = legendCodeOptionLabel(code);
    const typeLine = code.type_line ? ` (${code.type_line})` : '';
    return `${main}${typeLine}`;
}
