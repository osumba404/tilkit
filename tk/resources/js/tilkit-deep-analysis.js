import Plotly from 'plotly.js-dist-min';
import * as ss from 'simple-statistics';

const cfg = window.__tilkitDeepAnalysis || {};
const STORAGE_KEY = `tilkit-da-notebook-${cfg.toolSlug || 'default'}`;

const CELL_TYPES = {
    describe: { label: 'Descriptive statistics', icon: 'Σ' },
    profile: { label: 'Data profile (EDA)', icon: '◫' },
    groupby: { label: 'Group & aggregate', icon: '⊞' },
    chart: { label: 'Visualization', icon: '▤' },
    correlation: { label: 'Correlation heatmap', icon: '▦' },
    ttest: { label: 'T-test (two groups)', icon: 'T' },
    anova: { label: 'ANOVA', icon: 'F' },
    chi2: { label: 'Chi-square', icon: 'χ²' },
    timeseries: { label: 'Time series & trend', icon: '↗' },
    map: { label: 'Geo scatter map', icon: '🌍' },
};

const CHART_TYPES = [
    { id: 'bar', label: 'Bar' },
    { id: 'line', label: 'Line' },
    { id: 'scatter', label: 'Scatter' },
    { id: 'pie', label: 'Pie' },
    { id: 'box', label: 'Box plot' },
    { id: 'histogram', label: 'Histogram' },
    { id: 'area', label: 'Area (cumulative)' },
    { id: 'heatmap', label: '2D heatmap' },
];

const state = {
    columns: [],
    rows: [],
    filters: { sites: [], seasons: [], programs: [] },
    cells: [],
};

function csrf() {
    return cfg.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function jsonHeaders() {
    return { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
}

function columnMeta(id) {
    return state.columns.find((c) => c.id === id);
}

function isNumericColumn(id) {
    const m = columnMeta(id);
    if (!m) return false;
    if (m.kind === 'number' || m.kind === 'integer') return true;
    if (m.kind === 'number_or_text') {
        const vals = state.rows.map((r) => r[id]).filter((v) => v !== null && v !== '');
        return vals.length > 0 && vals.every((v) => typeof v === 'number' || (typeof v === 'string' && !Number.isNaN(Number(v))));
    }
    return false;
}

function numericValues(id) {
    return state.rows
        .map((r) => r[id])
        .filter((v) => v !== null && v !== '')
        .map((v) => (typeof v === 'number' ? v : Number(v)))
        .filter((v) => !Number.isNaN(v));
}

function categoricalValues(id) {
    return state.rows.map((r) => r[id]).filter((v) => v !== null && v !== '');
}

function defaultParams(type) {
    const numCol = state.columns.find((c) => isNumericColumn(c.id))?.id || 'U';
    const catCol = state.columns.find((c) => c.kind === 'categorical' || c.id === '_site')?.id || '_site';
    const dateCol = state.columns.find((c) => c.kind === 'date')?.id || '_collection_date';

    switch (type) {
        case 'describe':
            return { columns: [numCol] };
        case 'profile':
            return { maxColumns: 24 };
        case 'groupby':
            return { group: catCol, metric: numCol, agg: 'mean' };
        case 'chart':
            return { chartType: 'bar', x: catCol, y: numCol, color: '', cumulative: false, title: '' };
        case 'correlation':
            return { columns: state.columns.filter((c) => isNumericColumn(c.id)).slice(0, 8).map((c) => c.id) };
        case 'ttest':
            return { numeric: numCol, split: catCol, groupA: '', groupB: '' };
        case 'anova':
            return { numeric: numCol, factor: catCol };
        case 'chi2':
            return { colA: catCol, colB: '_season' };
        case 'timeseries':
            return { dateCol, valueCol: numCol, agg: 'mean', forecastPeriods: 3 };
        case 'map':
            return { lat: '_latitude', lon: '_longitude', color: catCol, size: numCol };
        default:
            return {};
    }
}

function loadNotebook() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                state.cells = parsed;
                return;
            }
        }
    } catch {
        /* ignore */
    }
    state.cells = [
        { id: crypto.randomUUID(), type: 'profile', params: {}, ran: false },
        { id: crypto.randomUUID(), type: 'chart', params: { chartType: 'bar', x: '_site', y: 'U', title: 'Tercile by site' }, ran: false },
        { id: crypto.randomUUID(), type: 'correlation', params: {}, ran: false },
    ];
}

function saveNotebook() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state.cells.map(({ id, type, params }) => ({ id, type, params }))));
}

async function fetchSchema() {
    const res = await fetch(cfg.schemaUrl, { headers: jsonHeaders(), credentials: 'same-origin' });
    if (!res.ok) throw new Error('Could not load schema');
    const data = await res.json();
    state.columns = data.columns || [];
    state.filters = data.filters || state.filters;
}

async function fetchRows() {
    const programEl = document.getElementById('tk-da-filter-program');
    const sitesEl = document.getElementById('tk-da-filter-sites');
    const seasonsEl = document.getElementById('tk-da-filter-seasons');
    const params = new URLSearchParams();
    const programId = programEl?.value;
    if (programId) params.set('program_id', programId);
    [...(sitesEl?.selectedOptions || [])].forEach((o) => params.append('site_ids[]', o.value));
    [...(seasonsEl?.selectedOptions || [])].forEach((o) => params.append('season_ids[]', o.value));

    const url = `${cfg.datasetUrl}?${params.toString()}`;
    const res = await fetch(url, { headers: jsonHeaders(), credentials: 'same-origin' });
    if (!res.ok) throw new Error('Could not load dataset');
    const data = await res.json();
    state.rows = data.rows || [];
    return data.row_count ?? state.rows.length;
}

function renderFilters() {
    const prog = document.getElementById('tk-da-filter-program');
    const sites = document.getElementById('tk-da-filter-sites');
    const seasons = document.getElementById('tk-da-filter-seasons');
    if (!prog || !sites || !seasons) return;

    prog.innerHTML = '<option value="">All programs</option>';
    (state.filters.programs || []).forEach((p) => {
        const o = document.createElement('option');
        o.value = p.id;
        o.textContent = p.name;
        prog.appendChild(o);
    });

    sites.innerHTML = '';
    (state.filters.sites || []).forEach((s) => {
        const o = document.createElement('option');
        o.value = s.id;
        o.textContent = s.name;
        sites.appendChild(o);
    });

    seasons.innerHTML = '';
    (state.filters.seasons || []).forEach((s) => {
        const o = document.createElement('option');
        o.value = s.id;
        o.textContent = s.label;
        seasons.appendChild(o);
    });
}

function renderColumnList(filter = '') {
    const ul = document.getElementById('tk-da-column-list');
    if (!ul) return;
    const q = filter.toLowerCase();
    ul.innerHTML = '';
    state.columns
        .filter((c) => !q || c.label.toLowerCase().includes(q) || c.id.toLowerCase().includes(q))
        .forEach((c) => {
            const li = document.createElement('li');
            li.className = 'py-1 border-b border-tk-line/40';
            li.innerHTML = `<span class="font-mono text-tk-accent">${escapeHtml(c.id)}</span> <span class="text-tk-muted">${escapeHtml(c.kind)}</span>`;
            ul.appendChild(li);
        });
}

function escapeHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function columnOptions(selected = '', kinds = null) {
    return state.columns
        .filter((c) => !kinds || kinds.includes(c.kind) || (kinds.includes('numeric') && isNumericColumn(c.id)))
        .map((c) => `<option value="${escapeHtml(c.id)}" ${c.id === selected ? 'selected' : ''}>${escapeHtml(c.label)}</option>`)
        .join('');
}

function renderCells() {
    const root = document.getElementById('tk-da-cells');
    const empty = document.getElementById('tk-da-empty');
    if (!root) return;

    root.innerHTML = '';
    empty?.classList.toggle('hidden', state.cells.length > 0);

    state.cells.forEach((cell, index) => {
        if (!cell.params || Object.keys(cell.params).length === 0) {
            cell.params = defaultParams(cell.type);
        }
        const wrap = document.createElement('article');
        wrap.className = 'tk-da-cell tk-card p-4';
        wrap.dataset.cellId = cell.id;

        wrap.innerHTML = `
            <header class="flex flex-wrap items-center gap-2 mb-3">
                <span class="tk-da-cell-badge">${CELL_TYPES[cell.type]?.icon || '?'}</span>
                <select class="tk-input text-sm tk-da-type-select" data-index="${index}">
                    ${Object.entries(CELL_TYPES).map(([k, v]) => `<option value="${k}" ${k === cell.type ? 'selected' : ''}>${v.label}</option>`).join('')}
                </select>
                <div class="flex-1"></div>
                <button type="button" class="tk-btn-secondary text-xs tk-da-run">Run cell</button>
                <button type="button" class="tk-btn-secondary text-xs tk-da-up" ${index === 0 ? 'disabled' : ''}>↑</button>
                <button type="button" class="tk-btn-secondary text-xs tk-da-down" ${index === state.cells.length - 1 ? 'disabled' : ''}>↓</button>
                <button type="button" class="text-xs text-red-600 tk-da-delete">Remove</button>
            </header>
            <div class="tk-da-params mb-3"></div>
            <div class="tk-da-output text-sm"></div>
        `;

        root.appendChild(wrap);
        renderCellParams(wrap, cell);
        if (cell.ran && cell.lastHtml) {
            const out = wrap.querySelector('.tk-da-output');
            if (out) out.innerHTML = cell.lastHtml;
            attachPlotlyDownload(wrap);
        }

        wrap.querySelector('.tk-da-type-select')?.addEventListener('change', (e) => {
            cell.type = e.target.value;
            cell.params = defaultParams(cell.type);
            cell.ran = false;
            cell.lastHtml = '';
            saveNotebook();
            renderCells();
        });
        wrap.querySelector('.tk-da-run')?.addEventListener('click', () => runCell(cell, wrap));
        wrap.querySelector('.tk-da-delete')?.addEventListener('click', () => {
            state.cells = state.cells.filter((c) => c.id !== cell.id);
            saveNotebook();
            renderCells();
        });
        wrap.querySelector('.tk-da-up')?.addEventListener('click', () => {
            if (index > 0) {
                [state.cells[index - 1], state.cells[index]] = [state.cells[index], state.cells[index - 1]];
                saveNotebook();
                renderCells();
            }
        });
        wrap.querySelector('.tk-da-down')?.addEventListener('click', () => {
            if (index < state.cells.length - 1) {
                [state.cells[index + 1], state.cells[index]] = [state.cells[index], state.cells[index + 1]];
                saveNotebook();
                renderCells();
            }
        });
    });
}

function renderCellParams(wrap, cell) {
    const el = wrap.querySelector('.tk-da-params');
    if (!el) return;
    const p = cell.params;

    const field = (label, inner) => `<label class="block text-xs text-tk-muted mb-1">${label}</label>${inner}`;

    switch (cell.type) {
        case 'describe':
            el.innerHTML = field('Numeric columns (comma-separated ids)', `<input class="tk-input w-full text-sm tk-da-param" data-key="columns" value="${(p.columns || []).join(', ')}" />`);
            break;
        case 'profile':
            el.innerHTML = field('Max columns to profile', `<input type="number" min="4" max="120" class="tk-input w-full text-sm tk-da-param" data-key="maxColumns" value="${p.maxColumns ?? 24}" />`);
            break;
        case 'groupby':
            el.innerHTML = `
                <div class="grid sm:grid-cols-3 gap-3">
                    ${field('Group by', `<select class="tk-input w-full text-sm tk-da-param" data-key="group">${columnOptions(p.group)}</select>`)}
                    ${field('Metric column', `<select class="tk-input w-full text-sm tk-da-param" data-key="metric">${columnOptions(p.metric, ['numeric'])}</select>`)}
                    ${field('Aggregation', `<select class="tk-input w-full text-sm tk-da-param" data-key="agg">
                        ${['count', 'sum', 'mean', 'median', 'min', 'max', 'std'].map((a) => `<option value="${a}" ${p.agg === a ? 'selected' : ''}>${a}</option>`).join('')}
                    </select>`)}
                </div>`;
            break;
        case 'chart':
            el.innerHTML = `
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    ${field('Chart type', `<select class="tk-input w-full text-sm tk-da-param" data-key="chartType">${CHART_TYPES.map((t) => `<option value="${t.id}" ${p.chartType === t.id ? 'selected' : ''}>${t.label}</option>`).join('')}</select>`)}
                    ${field('X / category', `<select class="tk-input w-full text-sm tk-da-param" data-key="x">${columnOptions(p.x)}</select>`)}
                    ${field('Y / value', `<select class="tk-input w-full text-sm tk-da-param" data-key="y">${columnOptions(p.y, ['numeric'])}</select>`)}
                    ${field('Color (optional)', `<select class="tk-input w-full text-sm tk-da-param" data-key="color"><option value="">—</option>${columnOptions(p.color)}</select>`)}
                    ${field('Title', `<input class="tk-input w-full text-sm tk-da-param" data-key="title" value="${escapeHtml(p.title || '')}" />`)}
                    ${field('Cumulative (line/area)', `<input type="checkbox" class="tk-da-param" data-key="cumulative" ${p.cumulative ? 'checked' : ''} />`)}
                </div>`;
            break;
        case 'correlation':
            el.innerHTML = field('Numeric column ids (comma-separated)', `<input class="tk-input w-full text-sm tk-da-param" data-key="columns" value="${(p.columns || []).join(', ')}" />`);
            break;
        case 'ttest':
            el.innerHTML = `
                <div class="grid sm:grid-cols-2 gap-3">
                    ${field('Numeric column', `<select class="tk-input w-full text-sm tk-da-param" data-key="numeric">${columnOptions(p.numeric, ['numeric'])}</select>`)}
                    ${field('Split by', `<select class="tk-input w-full text-sm tk-da-param" data-key="split">${columnOptions(p.split)}</select>`)}
                    ${field('Group A value', `<input class="tk-input w-full text-sm tk-da-param" data-key="groupA" value="${escapeHtml(p.groupA || '')}" />`)}
                    ${field('Group B value', `<input class="tk-input w-full text-sm tk-da-param" data-key="groupB" value="${escapeHtml(p.groupB || '')}" />`)}
                </div>`;
            break;
        case 'anova':
            el.innerHTML = `
                <div class="grid sm:grid-cols-2 gap-3">
                    ${field('Numeric response', `<select class="tk-input w-full text-sm tk-da-param" data-key="numeric">${columnOptions(p.numeric, ['numeric'])}</select>`)}
                    ${field('Factor (categorical)', `<select class="tk-input w-full text-sm tk-da-param" data-key="factor">${columnOptions(p.factor)}</select>`)}
                </div>`;
            break;
        case 'chi2':
            el.innerHTML = `
                <div class="grid sm:grid-cols-2 gap-3">
                    ${field('Column A', `<select class="tk-input w-full text-sm tk-da-param" data-key="colA">${columnOptions(p.colA)}</select>`)}
                    ${field('Column B', `<select class="tk-input w-full text-sm tk-da-param" data-key="colB">${columnOptions(p.colB)}</select>`)}
                </div>`;
            break;
        case 'timeseries':
            el.innerHTML = `
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    ${field('Date column', `<select class="tk-input w-full text-sm tk-da-param" data-key="dateCol">${columnOptions(p.dateCol, ['date'])}</select>`)}
                    ${field('Value column', `<select class="tk-input w-full text-sm tk-da-param" data-key="valueCol">${columnOptions(p.valueCol, ['numeric'])}</select>`)}
                    ${field('Aggregate per date', `<select class="tk-input w-full text-sm tk-da-param" data-key="agg">
                        ${['mean', 'sum', 'count', 'median'].map((a) => `<option value="${a}" ${p.agg === a ? 'selected' : ''}>${a}</option>`).join('')}
                    </select>`)}
                    ${field('Linear forecast periods', `<input type="number" min="0" max="24" class="tk-input w-full text-sm tk-da-param" data-key="forecastPeriods" value="${p.forecastPeriods ?? 3}" />`)}
                </div>`;
            break;
        case 'map':
            el.innerHTML = `
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    ${field('Latitude', `<select class="tk-input w-full text-sm tk-da-param" data-key="lat">${columnOptions(p.lat, ['numeric'])}</select>`)}
                    ${field('Longitude', `<select class="tk-input w-full text-sm tk-da-param" data-key="lon">${columnOptions(p.lon, ['numeric'])}</select>`)}
                    ${field('Color by', `<select class="tk-input w-full text-sm tk-da-param" data-key="color">${columnOptions(p.color)}</select>`)}
                    ${field('Size by (optional)', `<select class="tk-input w-full text-sm tk-da-param" data-key="size"><option value="">—</option>${columnOptions(p.size, ['numeric'])}</select>`)}
                </div>`;
            break;
        default:
            el.innerHTML = '<p class="text-tk-muted">Unknown cell type.</p>';
    }

    el.querySelectorAll('.tk-da-param').forEach((input) => {
        input.addEventListener('change', () => readParamsFromDom(wrap, cell));
        input.addEventListener('input', () => readParamsFromDom(wrap, cell));
    });
}

function readParamsFromDom(wrap, cell) {
    wrap.querySelectorAll('.tk-da-param').forEach((input) => {
        const key = input.dataset.key;
        if (!key) return;
        if (input.type === 'checkbox') {
            cell.params[key] = input.checked;
        } else if (key === 'columns') {
            cell.params[key] = input.value.split(',').map((s) => s.trim()).filter(Boolean);
        } else if (key === 'maxColumns' || key === 'forecastPeriods') {
            cell.params[key] = Number(input.value);
        } else {
            cell.params[key] = input.value;
        }
    });
    saveNotebook();
}

function runCell(cell, wrap) {
    readParamsFromDom(wrap, cell);
    const out = wrap.querySelector('.tk-da-output');
    if (!out) return;

    if (state.rows.length === 0) {
        out.innerHTML = '<p class="text-amber-700">No rows loaded. Apply filters and refresh the dataset.</p>';
        return;
    }

    try {
        const result = executeCell(cell);
        cell.ran = true;
        cell.lastHtml = result.html;
        out.innerHTML = result.html;
        if (result.plot) {
            const plotEl = out.querySelector('.tk-da-plot');
            if (plotEl) {
                Plotly.newPlot(plotEl, result.plot.data, result.plot.layout, { responsive: true, displayModeBar: true });
            }
        }
        attachPlotlyDownload(wrap);
        saveNotebook();
    } catch (err) {
        out.innerHTML = `<p class="text-red-600">${escapeHtml(err.message || String(err))}</p>`;
    }
}

function attachPlotlyDownload(wrap) {
    const btn = wrap.querySelector('.tk-da-download');
    if (btn) {
        btn.onclick = () => {
            const plotEl = wrap.querySelector('.tk-da-plot');
            if (plotEl) {
                Plotly.downloadImage(plotEl, { format: 'png', width: 1200, height: 700, filename: 'tilkit-chart' });
            }
        };
    }
}

function plotToolbar() {
    return `<div class="flex justify-end mb-2"><button type="button" class="tk-btn-secondary text-xs tk-da-download">Download PNG</button></div>`;
}

function tableHtml(headers, rows) {
    const head = headers.map((h) => `<th class="text-left px-2 py-1 border-b border-tk-line">${escapeHtml(h)}</th>`).join('');
    const body = rows
        .map((row) => `<tr>${row.map((c) => `<td class="px-2 py-1 border-b border-tk-line/50">${escapeHtml(c)}</td>`).join('')}</tr>`)
        .join('');
    return `<div class="overflow-x-auto"><table class="w-full text-xs"><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table></div>`;
}

function executeCell(cell) {
    const p = cell.params;
    switch (cell.type) {
        case 'describe':
            return runDescribe(p);
        case 'profile':
            return runProfile(p);
        case 'groupby':
            return runGroupBy(p);
        case 'chart':
            return runChart(p);
        case 'correlation':
            return runCorrelation(p);
        case 'ttest':
            return runTtest(p);
        case 'anova':
            return runAnova(p);
        case 'chi2':
            return runChi2(p);
        case 'timeseries':
            return runTimeseries(p);
        case 'map':
            return runMap(p);
        default:
            throw new Error('Unsupported cell type');
    }
}

function runDescribe(p) {
    const cols = Array.isArray(p.columns) ? p.columns : String(p.columns || '').split(',').map((s) => s.trim()).filter(Boolean);
    const rows = [];
    cols.forEach((id) => {
        if (!isNumericColumn(id)) {
            rows.push([id, '—', 'not numeric', '', '', '', '', '']);
            return;
        }
        const vals = numericValues(id);
        if (vals.length === 0) {
            rows.push([id, 0, '', '', '', '', '', '']);
            return;
        }
        rows.push([
            id,
            vals.length,
            ss.mean(vals).toFixed(4),
            ss.median(vals).toFixed(4),
            ss.standardDeviation(vals).toFixed(4),
            ss.min(vals).toFixed(4),
            ss.max(vals).toFixed(4),
            ss.quantile(vals, 0.25).toFixed(4) + ' / ' + ss.quantile(vals, 0.75).toFixed(4),
        ]);
    });
    return {
        html: tableHtml(['Column', 'n', 'mean', 'median', 'std', 'min', 'max', 'Q1 / Q3'], rows),
    };
}

function runProfile(p) {
    const max = p.maxColumns ?? 24;
    const cols = state.columns.slice(0, max);
    const rows = [];
    cols.forEach((c) => {
        const vals = state.rows.map((r) => r[c.id]);
        const nonNull = vals.filter((v) => v !== null && v !== '');
        const missing = state.rows.length - nonNull.length;
        const unique = new Set(nonNull.map(String)).size;
        let extra = '';
        if (isNumericColumn(c.id)) {
            const nums = numericValues(c.id);
            if (nums.length) {
                extra = `mean ${ss.mean(nums).toFixed(2)}, σ ${ss.standardDeviation(nums).toFixed(2)}`;
            }
        } else {
            const counts = {};
            nonNull.forEach((v) => {
                const k = String(v);
                counts[k] = (counts[k] || 0) + 1;
            });
            const top = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 2);
            extra = top.map(([k, n]) => `${k} (${n})`).join('; ');
        }
        rows.push([c.id, c.kind, nonNull.length, missing, unique, extra]);
    });
    return {
        html: `<p class="text-xs text-tk-muted mb-2">Automated profile on ${state.rows.length} rows (ydata-style summary, in-browser).</p>${tableHtml(['Column', 'kind', 'present', 'missing', 'unique', 'stats / top values'], rows)}`,
    };
}

function aggregate(values, agg) {
    if (agg === 'count') return values.length;
    const nums = values.map((v) => (typeof v === 'number' ? v : Number(v))).filter((v) => !Number.isNaN(v));
    if (agg === 'sum') return ss.sum(nums);
    if (agg === 'mean') return nums.length ? ss.mean(nums) : null;
    if (agg === 'median') return nums.length ? ss.median(nums) : null;
    if (agg === 'min') return nums.length ? ss.min(nums) : null;
    if (agg === 'max') return nums.length ? ss.max(nums) : null;
    if (agg === 'std') return nums.length > 1 ? ss.standardDeviation(nums) : null;
    return null;
}

function runGroupBy(p) {
    const groups = {};
    state.rows.forEach((r) => {
        const key = String(r[p.group] ?? '(blank)');
        if (!groups[key]) groups[key] = [];
        if (p.agg === 'count') {
            groups[key].push(1);
        } else {
            const v = r[p.metric];
            if (v !== null && v !== '') groups[key].push(v);
        }
    });
    const rows = Object.entries(groups)
        .map(([k, vals]) => [k, vals.length, aggregate(vals, p.agg === 'count' ? 'count' : p.agg)])
        .sort((a, b) => String(a[0]).localeCompare(String(b[0])));
    return {
        html: tableHtml(['Group', 'n', p.agg], rows.map((r) => [r[0], r[1], r[2] != null ? Number(r[2]).toFixed(4) : '—'])),
    };
}

function runChart(p) {
    const xVals = state.rows.map((r) => r[p.x]);
    const yVals = state.rows.map((r) => r[p.y]);
    const title = p.title || `${p.chartType}: ${p.y} by ${p.x}`;

    let plot = null;
    const toolbar = plotToolbar();

    if (p.chartType === 'pie') {
        const counts = {};
        xVals.forEach((v) => {
            const k = String(v ?? '(blank)');
            counts[k] = (counts[k] || 0) + 1;
        });
        plot = {
            data: [{ type: 'pie', labels: Object.keys(counts), values: Object.values(counts) }],
            layout: { title, height: 420 },
        };
    } else if (p.chartType === 'histogram') {
        const nums = numericValues(p.y);
        plot = {
            data: [{ type: 'histogram', x: nums, name: p.y }],
            layout: { title, height: 420, xaxis: { title: p.y } },
        };
    } else if (p.chartType === 'box') {
        plot = {
            data: [{ type: 'box', y: numericValues(p.y), name: p.y, boxpoints: 'all' }],
            layout: { title, height: 420 },
        };
    } else if (p.chartType === 'heatmap') {
        const xs = [...new Set(xVals.map(String))];
        const ys = [...new Set(state.rows.map((r) => String(r[p.color || p.x])))];
        const z = ys.map(() => xs.map(() => 0));
        state.rows.forEach((r) => {
            const xi = xs.indexOf(String(r[p.x]));
            const yi = ys.indexOf(String(r[p.color || p.y]));
            const v = Number(r[p.y]);
            if (xi >= 0 && yi >= 0 && !Number.isNaN(v)) z[yi][xi] += v;
        });
        plot = {
            data: [{ type: 'heatmap', x: xs, y: ys, z }],
            layout: { title, height: 440 },
        };
    } else {
        const buckets = {};
        state.rows.forEach((r) => {
            const k = String(r[p.x] ?? '(blank)');
            if (!buckets[k]) buckets[k] = [];
            const v = r[p.y];
            if (v !== null && v !== '') buckets[k].push(Number(v));
        });
        const labels = Object.keys(buckets).sort();
        let series = labels.map((k) => {
            const vals = buckets[k].filter((v) => !Number.isNaN(v));
            return vals.length ? ss.mean(vals) : 0;
        });
        if (p.cumulative && (p.chartType === 'line' || p.chartType === 'area')) {
            let c = 0;
            series = series.map((v) => {
                c += v;
                return c;
            });
        }
        const traceType = p.chartType === 'scatter' ? 'scatter' : p.chartType === 'line' || p.chartType === 'area' ? 'scatter' : 'bar';
        const mode = p.chartType === 'scatter' || p.chartType === 'line' ? 'lines+markers' : undefined;
        plot = {
            data: [
                {
                    type: traceType,
                    mode,
                    x: labels,
                    y: series,
                    fill: p.chartType === 'area' ? 'tozeroy' : undefined,
                },
            ],
            layout: { title, height: 440, xaxis: { title: p.x }, yaxis: { title: p.y } },
        };
    }

    return { html: `${toolbar}<div class="tk-da-plot w-full min-h-[420px]"></div>`, plot };
}

function runCorrelation(p) {
    const cols = (Array.isArray(p.columns) ? p.columns : []).filter((id) => isNumericColumn(id));
    if (cols.length < 2) throw new Error('Pick at least two numeric columns.');
    const matrix = cols.map((rowCol) =>
        cols.map((colCol) => {
            const pairs = state.rows
                .map((r) => [r[rowCol], r[colCol]])
                .filter(([a, b]) => a !== null && b !== null && a !== '' && b !== '' && !Number.isNaN(Number(a)) && !Number.isNaN(Number(b)))
                .map(([a, b]) => [Number(a), Number(b)]);
            if (pairs.length < 3) return 0;
            return ss.sampleCorrelation(
                pairs.map((x) => x[0]),
                pairs.map((x) => x[1]),
            );
        }),
    );
    const plot = {
        data: [{ type: 'heatmap', z: matrix, x: cols, y: cols, colorscale: 'RdBu', zmid: 0 }],
        layout: { title: 'Pearson correlation', height: 480 },
    };
    return { html: `${plotToolbar()}<div class="tk-da-plot w-full min-h-[480px]"></div>`, plot };
}

function runTtest(p) {
    const a = [];
    const b = [];
    state.rows.forEach((r) => {
        const g = String(r[p.split]);
        const v = Number(r[p.numeric]);
        if (Number.isNaN(v)) return;
        if (g === String(p.groupA)) a.push(v);
        if (g === String(p.groupB)) b.push(v);
    });
    if (a.length < 2 || b.length < 2) throw new Error('Need at least 2 numeric values in each group. Set group A/B to exact category values.');
    const t = ss.tTestTwoSample(a, b);
    const html = `
        <ul class="space-y-1 text-sm">
            <li><strong>Group A</strong> (${escapeHtml(p.groupA)}): n=${a.length}, mean=${ss.mean(a).toFixed(4)}</li>
            <li><strong>Group B</strong> (${escapeHtml(p.groupB)}): n=${b.length}, mean=${ss.mean(b).toFixed(4)}</li>
            <li><strong>t statistic</strong>: ${t.toFixed(4)} (Welch two-sample; use for exploratory comparison)</li>
        </ul>`;
    return { html };
}

function oneWayAnovaF(samples) {
    const all = samples.flat();
    const grandMean = ss.mean(all);
    const k = samples.length;
    const n = all.length;
    let ssBetween = 0;
    samples.forEach((g) => {
        ssBetween += g.length * (ss.mean(g) - grandMean) ** 2;
    });
    let ssWithin = 0;
    samples.forEach((g) => {
        const m = ss.mean(g);
        g.forEach((v) => {
            ssWithin += (v - m) ** 2;
        });
    });
    const dfBetween = k - 1;
    const dfWithin = n - k;
    if (dfBetween <= 0 || dfWithin <= 0) {
        throw new Error('Not enough data for ANOVA.');
    }
    const msBetween = ssBetween / dfBetween;
    const msWithin = ssWithin / dfWithin;
    if (msWithin === 0) {
        throw new Error('Zero within-group variance.');
    }

    return msBetween / msWithin;
}

function runAnova(p) {
    const groups = {};
    state.rows.forEach((r) => {
        const k = String(r[p.factor] ?? '(blank)');
        const v = Number(r[p.numeric]);
        if (Number.isNaN(v)) return;
        if (!groups[k]) groups[k] = [];
        groups[k].push(v);
    });
    const samples = Object.values(groups).filter((g) => g.length >= 2);
    if (samples.length < 2) throw new Error('Need at least two factor levels with numeric data.');
    const f = oneWayAnovaF(samples);
    const rows = Object.entries(groups).map(([k, vals]) => [k, vals.length, vals.length ? ss.mean(vals).toFixed(4) : '—']);
    return {
        html: `<p class="mb-2"><strong>F statistic</strong>: ${f.toFixed(4)}</p>${tableHtml(['Level', 'n', 'mean'], rows)}`,
    };
}

function runChi2(p) {
    const table = {};
    state.rows.forEach((r) => {
        const a = String(r[p.colA] ?? '(blank)');
        const b = String(r[p.colB] ?? '(blank)');
        table[a] = table[a] || {};
        table[a][b] = (table[a][b] || 0) + 1;
    });
    const rowsA = Object.keys(table);
    const colsB = [...new Set(rowsA.flatMap((a) => Object.keys(table[a])))];
    let chi2 = 0;
    const total = state.rows.length;
    const colTotals = {};
    colsB.forEach((b) => {
        colTotals[b] = rowsA.reduce((s, a) => s + (table[a][b] || 0), 0);
    });
    rowsA.forEach((a) => {
        const rowTotal = colsB.reduce((s, b) => s + (table[a][b] || 0), 0);
        colsB.forEach((b) => {
            const obs = table[a][b] || 0;
            const exp = (rowTotal * colTotals[b]) / total;
            if (exp > 0) chi2 += ((obs - exp) ** 2) / exp;
        });
    });
    const htmlRows = rowsA.map((a) => [a, ...colsB.map((b) => table[a][b] || 0)]);
    return {
        html: `<p class="mb-2"><strong>Chi-square</strong>: ${chi2.toFixed(4)} (df ≈ ${(rowsA.length - 1) * (colsB.length - 1)})</p>${tableHtml(['', ...colsB], htmlRows)}`,
    };
}

function runTimeseries(p) {
    const buckets = {};
    state.rows.forEach((r) => {
        const d = r[p.dateCol];
        const v = r[p.valueCol];
        if (!d || v === null || v === '') return;
        const key = String(d).slice(0, 10);
        if (!buckets[key]) buckets[key] = [];
        buckets[key].push(Number(v));
    });
    const dates = Object.keys(buckets).sort();
    const y = dates.map((d) => aggregate(buckets[d], p.agg));
    const xIdx = dates.map((_, i) => i);
    const forecastN = Math.max(0, Math.min(24, Number(p.forecastPeriods) || 0));
    let forecastX = [];
    let forecastY = [];
    if (forecastN > 0 && xIdx.length >= 2) {
        const reg = ss.linearRegression(xIdx.map((xi, i) => [xi, y[i]]));
        for (let i = 1; i <= forecastN; i++) {
            forecastX.push(dates.length - 1 + i);
            forecastY.push(ss.linearRegressionLine(reg)(dates.length - 1 + i));
        }
    }
    const plot = {
        data: [
            { type: 'scatter', mode: 'lines+markers', x: dates, y, name: p.valueCol },
            ...(forecastY.length
                ? [{ type: 'scatter', mode: 'lines', x: forecastX.map((i) => `+${i - dates.length + 1}`), y: forecastY, name: 'Linear trend forecast', line: { dash: 'dot' } }]
                : []),
        ],
        layout: { title: `Time series: ${p.valueCol}`, height: 440, xaxis: { title: p.dateCol } },
    };
    return { html: `${plotToolbar()}<div class="tk-da-plot w-full min-h-[440px]"></div>`, plot };
}

function runMap(p) {
    const lats = [];
    const lons = [];
    const texts = [];
    const colors = [];
    const sizes = [];
    state.rows.forEach((r) => {
        const lat = Number(r[p.lat]);
        const lon = Number(r[p.lon]);
        if (Number.isNaN(lat) || Number.isNaN(lon)) return;
        lats.push(lat);
        lons.push(lon);
        texts.push(String(r._indicator_uid || r._record_id));
        colors.push(String(r[p.color] ?? ''));
        if (p.size) {
            const s = Number(r[p.size]);
            sizes.push(Number.isNaN(s) ? 8 : 6 + Math.min(24, Math.abs(s)));
        } else {
            sizes.push(10);
        }
    });
    if (lats.length === 0) throw new Error('No geo points. Ensure latitude/longitude or GPS-derived columns have data.');
    const plot = {
        data: [
            {
                type: 'scattergeo',
                lat: lats,
                lon: lons,
                text: texts,
                mode: 'markers',
                marker: { size: sizes, color: colors, colorscale: 'Viridis', showscale: true },
            },
        ],
        layout: { title: 'Record locations', height: 480, geo: { projection: { type: 'natural earth' } } },
    };
    return { html: `${plotToolbar()}<div class="tk-da-plot w-full min-h-[480px]"></div>`, plot };
}

async function bootstrap() {
    const status = document.getElementById('tk-da-status');
    try {
        await fetchSchema();
        renderFilters();
        renderColumnList();
        const count = await fetchRows();
        if (status) status.textContent = `${count} rows · ${state.columns.length} columns`;
        loadNotebook();
        state.cells.forEach((c) => {
            if (!c.params || Object.keys(c.params).length === 0) c.params = defaultParams(c.type);
        });
        renderCells();
    } catch (e) {
        if (status) status.textContent = e.message || 'Failed to load';
    }
}

document.getElementById('tk-da-apply-filters')?.addEventListener('click', async () => {
    const status = document.getElementById('tk-da-status');
    try {
        const count = await fetchRows();
        if (status) status.textContent = `${count} rows · ${state.columns.length} columns`;
    } catch (e) {
        if (status) status.textContent = e.message || 'Refresh failed';
    }
});

document.getElementById('tk-da-reload-data')?.addEventListener('click', () => bootstrap());

document.getElementById('tk-da-add-cell')?.addEventListener('click', () => {
    state.cells.push({ id: crypto.randomUUID(), type: 'chart', params: defaultParams('chart'), ran: false });
    saveNotebook();
    renderCells();
});

document.getElementById('tk-da-reset-notebook')?.addEventListener('click', () => {
    if (confirm('Clear all cells and restore the default notebook?')) {
        localStorage.removeItem(STORAGE_KEY);
        loadNotebook();
        state.cells.forEach((c) => {
            c.params = defaultParams(c.type);
            c.ran = false;
            c.lastHtml = '';
        });
        renderCells();
    }
});

document.getElementById('tk-da-column-search')?.addEventListener('input', (e) => {
    renderColumnList(e.target.value);
});

if (document.getElementById('tk-deep-analysis-root')) {
    bootstrap();
}
