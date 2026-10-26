import Plotly from 'plotly.js-dist-min';
import Sortable from 'sortablejs';
import * as ss from 'simple-statistics';
import {
    enrichColumnRoles,
    buildSelectOptions,
    pickerRoleFor,
    roleHint,
    galleryAvailability,
    FIELD_ROLE,
} from './tilkit-deep-analysis-fields.js';
import {
    initDashboard,
    loadDashboardCards,
    openGallery,
    renderDashboardCanvas,
    refreshDashboardCardBodies,
    resetDashboardLayout,
    saveDashboardCards,
    applyDashboardTemplate,
    closeGallery,
    closeConfigDrawer,
    closeFullscreen,
    seedDashboardHistory,
    pushDashboardHistory,
    undoDashboard,
    redoDashboard,
} from './tilkit-deep-analysis-dashboard.js';

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

const PALETTE = ['#0d9488', '#0284c7', '#d97706', '#7c3aed', '#db2777', '#65a30d', '#475569'];
const TAB_KEY = `tilkit-da-tab-${cfg.toolSlug || 'default'}`;

const state = {
    columns: [],
    rows: [],
    filters: { sites: [], seasons: [], programs: [] },
    cells: [],
    crossFilter: null,
    rowSearch: '',
    activeTab: 'dashboard',
    dashboardCards: [],
    dashboardEditMode: true,
    configDrawerCardId: null,
};

let dashboardSortable = null;
const EDIT_MODE_KEY = () => `tilkit-da-edit-${cfg.toolSlug || 'default'}`;
let slicerDebounce = null;

function activeRows() {
    let rows = state.rows;
    if (state.crossFilter?.column) {
        const { column, value } = state.crossFilter;
        rows = rows.filter((r) => String(r[column] ?? '(blank)') === String(value));
    }
    const q = (state.rowSearch || '').trim().toLowerCase();
    if (q) {
        rows = rows.filter((r) => JSON.stringify(r).toLowerCase().includes(q));
    }

    return rows;
}

function plotLayout(title, extra = {}) {
    const { xaxis: xExtra, yaxis: yExtra, legend: legendExtra, margin: marginExtra, ...restExtra } = extra;
    const showLegend = restExtra.showlegend === true;
    const defaultMargin = { t: 48, r: 20, b: showLegend ? 88 : 56, l: 60 };
    return {
        title: { text: title, font: { size: 15, color: '#0f172a' }, x: 0, xanchor: 'left' },
        margin: { ...defaultMargin, ...marginExtra },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: '#fafafa',
        font: { family: 'system-ui, sans-serif', size: 11, color: '#334155' },
        colorway: PALETTE,
        hovermode: 'closest',
        hoverlabel: { bgcolor: '#ffffff', bordercolor: '#94a3b8', font: { size: 12, color: '#0f172a' } },
        ...(showLegend
            ? {
                  legend: {
                      orientation: 'h',
                      yanchor: 'top',
                      y: -0.22,
                      x: 0,
                      xanchor: 'left',
                      font: { size: 10 },
                      ...legendExtra,
                  },
              }
            : {}),
        ...restExtra,
        xaxis: {
            gridcolor: '#e2e8f0',
            linecolor: '#cbd5e1',
            tickfont: { size: 11, color: '#64748b' },
            zeroline: false,
            automargin: true,
            ...xExtra,
        },
        yaxis: {
            gridcolor: '#e2e8f0',
            linecolor: '#cbd5e1',
            tickfont: { size: 11, color: '#64748b' },
            zeroline: false,
            automargin: true,
            ...yExtra,
        },
    };
}

/** Human-readable axis / legend label for a workbook or meta column id. */
function fieldLabel(columnId) {
    if (columnId === null || columnId === undefined || columnId === '') {
        return '';
    }
    const m = columnMeta(String(columnId));
    if (!m) {
        return String(columnId).replace(/^_/, '');
    }
    const lab = m.label || m.id;
    if (lab.includes(' · ')) {
        const parts = lab.split(' · ');
        return parts.length > 1 ? parts.slice(1).join(' · ') : parts[0];
    }

    return lab;
}

/** Short axis label — prefer column letter for workbook fields. */
function fieldLabelShort(columnId, maxLen = 28) {
    const m = columnMeta(String(columnId));
    const id = m?.id || String(columnId);
    if (/^[A-Z]{1,2}$/.test(id)) {
        return id;
    }
    if (id.startsWith('_')) {
        return fieldLabel(columnId).replace(/^./, (c) => c.toUpperCase()) || id.replace(/^_/, '');
    }
    const full = fieldLabel(columnId);
    return full.length > maxLen ? `${full.slice(0, maxLen - 1)}…` : full;
}

function axisMeasureLabel(columnId, aggWord = 'Mean') {
    const short = fieldLabelShort(columnId);
    return `${aggWord} (${short})`;
}

/** Dashboard cards already have a header title — drop in-plot title and excess legend noise. */
function compactCardPlotLayout(layout, height = 280, { keepLegend = false } = {}) {
    return mergePlotLayout(layout, {
        height,
        title: undefined,
        showlegend: keepLegend ? Boolean(layout.showlegend) : false,
        margin: { t: 12, r: 16, b: keepLegend ? 88 : 64, l: 56 },
    });
}

function axisTitle(text) {
    const t = String(text || '').trim();
    if (!t) {
        return {};
    }

    return {
        title: {
            text: t,
            standoff: 12,
            font: { size: 12, color: '#334155', family: 'system-ui, sans-serif' },
        },
    };
}

/** Deep-merge Plotly layout so axis titles survive height/responsive updates. */
function mergePlotLayout(base, patch = {}) {
    const { xaxis: px, yaxis: py, margin: pm, legend: pl, ...restPatch } = patch;

    return {
        ...base,
        ...restPatch,
        margin: { ...(base?.margin || {}), ...(pm || {}) },
        legend: pl ? { ...(base?.legend || {}), ...pl } : base?.legend,
        xaxis: { ...(base?.xaxis || {}), ...(px || {}) },
        yaxis: { ...(base?.yaxis || {}), ...(py || {}) },
    };
}

/**
 * Standard chart layout with labeled axes and optional legend.
 * @param {string} title
 * @param {{ height?: number, xTitle?: string, yTitle?: string, showLegend?: boolean, colorbarTitle?: string }} opts
 */
function chartLayout(title, opts = {}) {
    const { height = 440, xTitle = '', yTitle = '', showLegend = false, colorbarTitle = '', ...rest } = opts;
    const layout = plotLayout(title, {
        height,
        showlegend: showLegend,
        margin: {
            r: colorbarTitle ? 72 : 28,
            b: showLegend ? 88 : xTitle ? 72 : 56,
            l: yTitle ? 60 : 52,
            t: 48,
        },
        xaxis: axisTitle(xTitle),
        yaxis: axisTitle(yTitle),
        ...rest,
    });
    if (colorbarTitle) {
        layout.coloraxis = {
            colorbar: { title: { text: colorbarTitle, side: 'right' }, thickness: 12, len: 0.9 },
        };
    }

    return layout;
}

function setDashboardLoading(on) {
    document.getElementById('tk-da-canvas-loading')?.classList.toggle('hidden', !on);
}

function loadDashboardEditMode() {
    const v = sessionStorage.getItem(EDIT_MODE_KEY());
    state.dashboardEditMode = v !== '0';
}

function syncEditModeUi() {
    const toggle = document.getElementById('tk-da-edit-mode');
    if (toggle) {
        toggle.setAttribute('aria-pressed', state.dashboardEditMode ? 'true' : 'false');
        toggle.textContent = state.dashboardEditMode ? 'Done editing' : 'Edit layout';
    }
    document.getElementById('tk-da-dashboard-canvas')?.classList.toggle('is-view-mode', !state.dashboardEditMode);
}

function scheduleSlicerRefresh() {
    clearTimeout(slicerDebounce);
    slicerDebounce = setTimeout(() => {
        refreshDataset().catch(() => {});
    }, 450);
}

function countByColumn(rows, col) {
    const counts = {};
    rows.forEach((r) => {
        const k = String(r[col] ?? '(blank)');
        counts[k] = (counts[k] || 0) + 1;
    });

    return counts;
}

function purgePlot(id) {
    const el = document.getElementById(id);
    if (el) {
        Plotly.purge(el);
    }
}

function mountPlotEl(el, data, layout, crossFilterColumn = null) {
    if (!el) {
        return;
    }
    try {
        Plotly.purge(el);
    } catch {
        /* first plot on this node */
    }
    const finalLayout = mergePlotLayout(layout);
    const plotPromise = Plotly.newPlot(el, data, finalLayout, { responsive: true, displayModeBar: false });
    if (plotPromise?.then) {
        plotPromise
            .then(() => {
                Plotly.Plots.resize(el);
                return Plotly.relayout(el, {
                    'xaxis.automargin': true,
                    'yaxis.automargin': true,
                });
            })
            .catch((err) => console.error('Plotly render failed', err));
    }
    if (crossFilterColumn) {
        el.on('plotly_click', (ev) => {
            const pt = ev.points?.[0];
            if (!pt) {
                return;
            }
            const value = pt.x ?? pt.label ?? pt.y ?? pt.text;
            if (value === undefined || value === null) {
                return;
            }
            const vStr = String(value);
            if (state.crossFilter?.column === crossFilterColumn && String(state.crossFilter.value) === vStr) {
                state.crossFilter = null;
            } else {
                state.crossFilter = { column: crossFilterColumn, value: vStr };
            }
            updateCrossFilterUi();
            refreshActiveView();
        });
    }
}

function mountPlot(id, data, layout, crossFilterColumn = null) {
    mountPlotEl(document.getElementById(id), data, layout, crossFilterColumn);
}

function destroyDashboardSortable() {
    if (dashboardSortable) {
        dashboardSortable.destroy();
        dashboardSortable = null;
    }
}

function initDashboardSortable(root) {
    destroyDashboardSortable();
    dashboardSortable = Sortable.create(root, {
        animation: 280,
        easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
        handle: '.tk-da-card-grip',
        ghostClass: 'is-dragging',
        dragClass: 'is-dragging',
        onEnd() {
            pushDashboardHistory();
            const order = [...root.querySelectorAll('.tk-da-premium-card')].map((el) => el.dataset.cardId);
            state.dashboardCards = order
                .map((id) => state.dashboardCards.find((c) => c.id === id))
                .filter(Boolean);
            saveDashboardCards();
        },
    });
}

function updateCrossFilterUi() {
    const chip = document.getElementById('tk-da-crossfilter-chip');
    const clearBtn = document.getElementById('tk-da-clear-crossfilter');
    if (!chip || !clearBtn) {
        return;
    }
    if (!state.crossFilter) {
        chip.classList.add('hidden');
        clearBtn.classList.add('hidden');
        chip.textContent = '';

        return;
    }
    const col = columnMeta(state.crossFilter.column)?.label || state.crossFilter.column;
    chip.textContent = `${col} = ${state.crossFilter.value}`;
    chip.classList.remove('hidden');
    clearBtn.classList.remove('hidden');
}

function setActiveTab(tab) {
    state.activeTab = tab;
    sessionStorage.setItem(TAB_KEY, tab);
    document.querySelectorAll('.tk-da-tab').forEach((btn) => {
        btn.classList.toggle('is-active', btn.dataset.daTab === tab);
    });
    document.querySelectorAll('.tk-da-panel').forEach((panel) => {
        panel.classList.toggle('hidden', panel.id !== `tk-da-panel-${tab}`);
    });
    document.querySelectorAll('[data-da-tab-action="explore"]').forEach((el) => {
        el.classList.toggle('hidden', tab !== 'explore');
    });
    document.querySelectorAll('[data-da-tab-action="dashboard"]').forEach((el) => {
        el.classList.toggle('hidden', tab !== 'dashboard');
    });
    refreshActiveView();
}

function refreshActiveView() {
    if (state.activeTab === 'dashboard') {
        refreshDashboardCardBodies();
    } else if (state.activeTab === 'data') {
        renderDataTable();
    } else if (state.activeTab === 'explore') {
        state.cells.forEach((c) => {
            if (c.ran) {
                const wrap = document.querySelector(`[data-cell-id="${c.id}"]`);
                if (wrap) {
                    runCell(c, wrap);
                }
            }
        });
    }
}

function renderDataTable() {
    const el = document.getElementById('tk-da-data-table');
    if (!el) {
        return;
    }
    const rows = activeRows().slice(0, 200);
    const cols = ['_indicator_uid', '_site', '_season', 'U', 'N', 'AC', 'AD', 'AP', 'CE', '_collection_date'];
    const head = cols.map((c) => columnMeta(c)?.label || c);
    const body = rows.map((r) => cols.map((c) => r[c] ?? ''));
    el.innerHTML =
        rows.length === 0
            ? '<p class="text-tk-muted">No rows match filters.</p>'
            : tableHtml(head, body) + (activeRows().length > 200 ? `<p class="text-xs text-tk-muted mt-2">Showing 200 of ${activeRows().length} rows.</p>` : '');
}

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

function numericValues(id, rows = null) {
    const data = rows ?? activeRows();

    return data
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
    const siteId = sitesEl?.value;
    if (siteId) params.append('site_ids[]', siteId);
    const seasonId = seasonsEl?.value;
    if (seasonId) params.append('season_ids[]', seasonId);

    const url = `${cfg.datasetUrl}?${params.toString()}`;
    const res = await fetch(url, { headers: jsonHeaders(), credentials: 'same-origin' });
    if (!res.ok) throw new Error('Could not load dataset');
    const data = await res.json();
    state.rows = data.rows || [];
    state.columns = enrichColumnRoles(state.columns, state.rows, isNumericColumn);
    return data.row_count ?? state.rows.length;
}

function slicerProgramId() {
    const v = document.getElementById('tk-da-filter-program')?.value;
    return v ? Number(v) : null;
}

function slicerSiteId() {
    const v = document.getElementById('tk-da-filter-sites')?.value;
    return v ? Number(v) : null;
}

/** Sites limited to the chosen program; all sites when program is “All”. */
function availableSlicerSites() {
    const all = state.filters.sites || [];
    const programId = slicerProgramId();
    if (!programId) {
        return all;
    }

    return all.filter((s) => Number(s.program_id) === programId);
}

/** Seasons that exist in data for the current program (and site, if chosen). */
function availableSlicerSeasons() {
    const all = state.filters.seasons || [];
    const scopes = state.filters.scopes || [];
    const programId = slicerProgramId();
    const siteId = slicerSiteId();

    if (!programId && !siteId) {
        return all;
    }

    let pool = scopes;
    if (programId) {
        pool = pool.filter((s) => s.program_id === programId);
    }
    if (siteId) {
        pool = pool.filter((s) => s.site_id === siteId);
    }

    const seasonIds = new Set(pool.map((s) => s.season_id));

    return all.filter((s) => seasonIds.has(Number(s.id)));
}

function fillSlicerSelect(selectEl, items, labelKey, allLabel, previousValue) {
    if (!selectEl) {
        return;
    }
    const prev = previousValue ?? selectEl.value;
    selectEl.innerHTML = '';
    const allOpt = document.createElement('option');
    allOpt.value = '';
    allOpt.textContent = allLabel;
    selectEl.appendChild(allOpt);
    items.forEach((item) => {
        const o = document.createElement('option');
        o.value = item.id;
        o.textContent = item[labelKey];
        selectEl.appendChild(o);
    });
    const stillValid = prev && items.some((item) => String(item.id) === String(prev));
    selectEl.value = stillValid ? prev : '';
}

/** Cascade site/season options when program or site changes. */
function syncDependentSlicers(from) {
    const sites = document.getElementById('tk-da-filter-sites');
    const seasons = document.getElementById('tk-da-filter-seasons');
    if (!sites || !seasons) {
        return;
    }

    if (from === 'program') {
        fillSlicerSelect(sites, availableSlicerSites(), 'name', 'All sites', sites.value);
        fillSlicerSelect(seasons, availableSlicerSeasons(), 'label', 'All seasons', seasons.value);
    } else if (from === 'site') {
        fillSlicerSelect(seasons, availableSlicerSeasons(), 'label', 'All seasons', seasons.value);
    }
}

function renderSearchSuggestions() {
    const searchList = document.getElementById('tk-da-search-suggestions');
    if (!searchList) {
        return;
    }
    searchList.innerHTML = '';
    const hints = new Set();
    availableSlicerSites().forEach((s) => hints.add(s.name));
    availableSlicerSeasons().forEach((s) => hints.add(s.label));
    (state.filters.programs || []).forEach((p) => hints.add(p.name));
    hints.forEach((label) => {
        const opt = document.createElement('option');
        opt.value = label;
        searchList.appendChild(opt);
    });
}

function renderFilters() {
    const prog = document.getElementById('tk-da-filter-program');
    const sites = document.getElementById('tk-da-filter-sites');
    const seasons = document.getElementById('tk-da-filter-seasons');
    if (!prog || !sites || !seasons) return;

    const programValue = prog.value;

    prog.innerHTML = '<option value="">All programs</option>';
    (state.filters.programs || []).forEach((p) => {
        const o = document.createElement('option');
        o.value = p.id;
        o.textContent = p.name;
        prog.appendChild(o);
    });
    prog.value = programValue && [...prog.options].some((o) => o.value === programValue) ? programValue : '';

    syncDependentSlicers('program');
    renderSearchSuggestions();
}

function renderColumnList(filter = '', roleFilter = '') {
    const ul = document.getElementById('tk-da-column-list');
    if (!ul) return;
    const q = filter.toLowerCase();
    ul.innerHTML = '';
    state.columns
        .filter((c) => {
            if (q && !c.label.toLowerCase().includes(q) && !c.id.toLowerCase().includes(q)) {
                return false;
            }
            if (!roleFilter) {
                return true;
            }
            if (roleFilter === 'measure') {
                return isNumericColumn(c.id);
            }
            if (roleFilter === 'table_only') {
                return c.roles?.includes('table_only');
            }
            return c.roles?.includes(roleFilter);
        })
        .forEach((c) => {
            const li = document.createElement('li');
            li.className = 'py-1.5 border-b border-tk-line/40';
            const badges = (c.roles || [])
                .filter((r) => !['table'].includes(r))
                .slice(0, 3)
                .map((r) => `<span class="tk-da-role-tag">${escapeHtml(r.replace('_', ' '))}</span>`)
                .join('');
            li.innerHTML = `<div class="font-mono text-tk-accent text-[11px]">${escapeHtml(c.id)}</div>
                <div class="text-tk-muted text-[10px] mt-0.5">${escapeHtml(c.kind)}${c.uniqueCount != null ? ` · ${c.uniqueCount} values` : ''}</div>
                <div class="flex flex-wrap gap-1 mt-1">${badges}</div>`;
            ul.appendChild(li);
        });
}

function columnOptionsForPicker(role, selected, allowEmpty = false) {
    if (!role) {
        return { html: '', count: 0, hint: '' };
    }
    const list = buildSelectOptions(state.columns, role, selected, allowEmpty, isNumericColumn);
    const count = filterColumnsCount(role);
    return { html: list, count, hint: roleHint(role, count) };
}

function filterColumnsCount(role) {
    if (role === 'measure_or_dimension') {
        return state.columns.filter(
            (c) => !c.roles?.includes('table_only') && (isNumericColumn(c.id) || c.roles?.includes('dimension')),
        ).length;
    }
    if (role === 'table') {
        return state.columns.filter((c) => !c.roles?.includes('table_only')).length;
    }
    if (role === FIELD_ROLE.MEASURE || role === 'numeric') {
        return state.columns.filter((c) => isNumericColumn(c.id)).length;
    }
    return state.columns.filter((c) => c.roles?.includes(role) && !c.roles?.includes('table_only')).length;
}

function escapeHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function columnOptions(selected = '', kinds = null, allowEmpty = false) {
    let role = null;
    if (kinds?.includes('numeric')) {
        role = FIELD_ROLE.MEASURE;
    } else if (kinds?.includes('date')) {
        role = FIELD_ROLE.DATE;
    }
    if (role) {
        return buildSelectOptions(state.columns, role, selected, allowEmpty, isNumericColumn);
    }

    const empty = allowEmpty ? `<option value="" ${selected === '' ? 'selected' : ''}>—</option>` : '';

    return (
        empty +
        state.columns
            .filter((c) => {
                if (c.roles?.includes('table_only')) {
                    return false;
                }
                if (!kinds) {
                    return true;
                }
                return kinds.includes(c.kind) || (kinds.includes('numeric') && isNumericColumn(c.id));
            })
            .map((c) => `<option value="${escapeHtml(c.id)}" ${c.id === selected ? 'selected' : ''}>${escapeHtml(c.label)}</option>`)
            .join('')
    );
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
        wrap.setAttribute('data-cell-id', cell.id);

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

    if (activeRows().length === 0) {
        out.innerHTML = '<p class="text-amber-700">No rows in view. Adjust slicers, search, or clear chart selection.</p>';
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
                Plotly.newPlot(plotEl, result.plot.data, mergePlotLayout(result.plot.layout), {
                    responsive: true,
                    displayModeBar: true,
                });
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
    const dataRows = activeRows();
    const cols = Array.isArray(p.columns) ? p.columns : String(p.columns || '').split(',').map((s) => s.trim()).filter(Boolean);
    const out = [];
    cols.forEach((id) => {
        if (!isNumericColumn(id)) {
            out.push([id, '—', 'not numeric', '', '', '', '', '']);
            return;
        }
        const vals = numericValues(id, dataRows);
        if (vals.length === 0) {
            out.push([id, 0, '', '', '', '', '', '']);
            return;
        }
        out.push([
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
        html: tableHtml(['Column', 'n', 'mean', 'median', 'std', 'min', 'max', 'Q1 / Q3'], out),
    };
}

function runProfile(p) {
    const dataRows = activeRows();
    const max = p.maxColumns ?? 24;
    const cols = state.columns.slice(0, max);
    const rows = [];
    cols.forEach((c) => {
        const vals = dataRows.map((r) => r[c.id]);
        const nonNull = vals.filter((v) => v !== null && v !== '');
        const missing = dataRows.length - nonNull.length;
        const unique = new Set(nonNull.map(String)).size;
        let extra = '';
        if (isNumericColumn(c.id)) {
            const nums = numericValues(c.id, dataRows);
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
        html: `<p class="text-xs text-tk-muted mb-2">Automated profile on ${dataRows.length} rows (ydata-style summary, in-browser).</p>${tableHtml(['Column', 'kind', 'present', 'missing', 'unique', 'stats / top values'], rows)}`,
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
    const dataRows = activeRows();
    const groups = {};
    dataRows.forEach((r) => {
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
    const dataRows = activeRows();
    const xVals = dataRows.map((r) => r[p.x]);
    const compact = p.compact === true;
    const xLab = fieldLabel(p.x);
    const yLab = fieldLabel(p.y);
    const xAxis = compact ? fieldLabelShort(p.x) : xLab || p.x;
    const title = p.title || `${p.chartType}: ${yLab || p.y} by ${xLab || p.x}`;
    const plotTitle = compact ? '' : title;
    const plotHeight = compact ? 280 : 440;

    let plot = null;
    const toolbar = plotToolbar();

    if (p.chartType === 'pie') {
        const counts = {};
        xVals.forEach((v) => {
            const k = String(v ?? '(blank)');
            counts[k] = (counts[k] || 0) + 1;
        });
        plot = {
            data: [
                {
                    type: 'pie',
                    labels: Object.keys(counts),
                    values: Object.values(counts),
                    showlegend: !compact,
                    hovertemplate: '%{label}<br>Count: %{value}<extra></extra>',
                },
            ],
            layout: chartLayout(plotTitle, {
                height: plotHeight,
                showLegend: !compact,
            }),
        };
    } else if (p.chartType === 'histogram') {
        const nums = numericValues(p.y, dataRows);
        const yAxis = compact ? fieldLabelShort(p.y) : yLab || p.y;
        plot = {
            data: [
                {
                    type: 'histogram',
                    x: nums,
                    showlegend: !compact,
                    hovertemplate: 'Value: %{x}<br>Count: %{y}<extra></extra>',
                },
            ],
            layout: chartLayout(plotTitle, { height: plotHeight, xTitle: yAxis, yTitle: 'Frequency', showLegend: !compact }),
        };
    } else if (p.chartType === 'box') {
        const yAxis = compact ? fieldLabelShort(p.y) : yLab || p.y;
        plot = {
            data: [
                {
                    type: 'box',
                    y: numericValues(p.y, dataRows),
                    showlegend: !compact,
                    boxpoints: 'all',
                    hovertemplate: '%{y}<extra>' + yAxis + '</extra>',
                },
            ],
            layout: chartLayout(plotTitle, { height: plotHeight, yTitle: yAxis, showLegend: !compact }),
        };
    } else if (p.chartType === 'heatmap') {
        const xs = [...new Set(xVals.map(String))];
        const ys = [...new Set(dataRows.map((r) => String(r[p.color || p.x])))];
        const z = ys.map(() => xs.map(() => 0));
        dataRows.forEach((r) => {
            const xi = xs.indexOf(String(r[p.x]));
            const yi = ys.indexOf(String(r[p.color || p.y]));
            const v = Number(r[p.y]);
            if (xi >= 0 && yi >= 0 && !Number.isNaN(v)) z[yi][xi] += v;
        });
        plot = {
            data: [{ type: 'heatmap', x: xs, y: ys, z, colorbar: { title: { text: `Sum of ${yLab || p.y}` } } }],
            layout: chartLayout(title, {
                height: 440,
                xTitle: xLab || p.x,
                yTitle: fieldLabel(p.color) || 'Category',
                colorbarTitle: `Sum (${yLab || p.y})`,
            }),
        };
    } else if (p.chartType === 'scatter') {
        const xs = [];
        const ys = [];
        dataRows.forEach((r) => {
            const xv = r[p.x];
            const yv = Number(r[p.y]);
            if (Number.isNaN(yv)) {
                return;
            }
            const xNum = typeof xv === 'number' ? xv : Number(xv);
            if (!Number.isNaN(xNum) && String(xv).trim() !== '') {
                xs.push(xNum);
                ys.push(yv);
            } else {
                xs.push(String(xv ?? '(blank)'));
                ys.push(yv);
            }
        });
        if (xs.length === 0) {
            throw new Error('No numeric Y values for scatter. Pick a numeric value column.');
        }
        plot = {
            data: [
                {
                    type: 'scatter',
                    mode: 'markers',
                    x: xs,
                    y: ys,
                    showlegend: !compact,
                    marker: { color: PALETTE[0] },
                },
            ],
            layout: chartLayout(plotTitle, {
                height: plotHeight,
                xTitle: xAxis,
                yTitle: compact ? fieldLabelShort(p.y) : yLab || p.y,
                showLegend: !compact,
            }),
        };
    } else {
        const buckets = {};
        dataRows.forEach((r) => {
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
        const cumulative = p.cumulative && (p.chartType === 'line' || p.chartType === 'area');
        const yAxisLabel = compact
            ? axisMeasureLabel(p.y, cumulative ? 'Cumulative' : 'Mean')
            : cumulative
              ? `Cumulative mean · ${yLab || p.y}`
              : `Mean · ${yLab || p.y}`;
        if (cumulative) {
            let c = 0;
            series = series.map((v) => {
                c += v;
                return c;
            });
        }
        const traceType = p.chartType === 'scatter' ? 'scatter' : p.chartType === 'line' || p.chartType === 'area' ? 'scatter' : 'bar';
        const mode = p.chartType === 'scatter' || p.chartType === 'line' ? 'lines+markers' : undefined;
        const seriesName = cumulative ? `Cumulative ${yLab || p.y}` : `Mean ${yLab || p.y}`;
        plot = {
            data: [
                {
                    type: traceType,
                    mode,
                    x: labels,
                    y: series,
                    name: compact ? '' : seriesName,
                    showlegend: !compact,
                    fill: p.chartType === 'area' ? 'tozeroy' : undefined,
                    marker: { color: PALETTE[0] },
                    line: traceType === 'scatter' ? { color: PALETTE[0], width: 2 } : undefined,
                },
            ],
            layout: chartLayout(plotTitle, {
                height: plotHeight,
                xTitle: xAxis,
                yTitle: yAxisLabel,
                showLegend: !compact,
            }),
        };
    }

    return { html: `${toolbar}<div class="tk-da-plot w-full min-h-[420px]"></div>`, plot };
}

function runCorrelation(p) {
    const dataRows = activeRows();
    const cols = (Array.isArray(p.columns) ? p.columns : []).filter((id) => isNumericColumn(id));
    if (cols.length < 2) throw new Error('Pick at least two numeric columns.');
    const matrix = cols.map((rowCol) =>
        cols.map((colCol) => {
            const pairs = dataRows
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
    const colLabels = cols.map((c) => fieldLabel(c));
    const plot = {
        data: [
            {
                type: 'heatmap',
                z: matrix,
                x: colLabels,
                y: colLabels,
                colorscale: 'RdBu',
                zmid: 0,
                colorbar: { title: { text: 'Pearson r' } },
                hovertemplate: '%{y} vs %{x}<br>r = %{z:.3f}<extra></extra>',
            },
        ],
        layout: chartLayout('Pearson correlation', {
            height: 480,
            xTitle: 'Column',
            yTitle: 'Column',
            colorbarTitle: 'Pearson r',
        }),
    };
    return { html: `${plotToolbar()}<div class="tk-da-plot w-full min-h-[480px]"></div>`, plot };
}

function runTtest(p) {
    const dataRows = activeRows();
    const a = [];
    const b = [];
    dataRows.forEach((r) => {
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
    const dataRows = activeRows();
    const groups = {};
    dataRows.forEach((r) => {
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
    const dataRows = activeRows();
    const table = {};
    dataRows.forEach((r) => {
        const a = String(r[p.colA] ?? '(blank)');
        const b = String(r[p.colB] ?? '(blank)');
        table[a] = table[a] || {};
        table[a][b] = (table[a][b] || 0) + 1;
    });
    const rowsA = Object.keys(table);
    const colsB = [...new Set(rowsA.flatMap((a) => Object.keys(table[a])))];
    let chi2 = 0;
    const total = dataRows.length;
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
    const dataRows = activeRows();
    const buckets = {};
    dataRows.forEach((r) => {
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
    const valueLab = fieldLabel(p.valueCol);
    const dateLab = fieldLabel(p.dateCol);
    const aggLabel = p.agg === 'mean' ? 'Mean' : p.agg === 'sum' ? 'Sum' : p.agg === 'count' ? 'Count' : String(p.agg || 'Value');
    const plot = {
        data: [
            {
                type: 'scatter',
                mode: 'lines+markers',
                x: dates,
                y,
                name: `${aggLabel} · ${valueLab || p.valueCol}`,
                line: { color: PALETTE[0], width: 2 },
            },
            ...(forecastY.length
                ? [
                      {
                          type: 'scatter',
                          mode: 'lines',
                          x: forecastX.map((i) => `+${i - dates.length + 1}`),
                          y: forecastY,
                          name: 'Linear trend forecast',
                          line: { dash: 'dot', color: PALETTE[2] },
                      },
                  ]
                : []),
        ],
        layout: chartLayout(p.title || `Time series: ${valueLab || p.valueCol}`, {
            height: 440,
            xTitle: dateLab || p.dateCol,
            yTitle: `${aggLabel} · ${valueLab || p.valueCol}`,
            showLegend: true,
        }),
    };
    return { html: `${plotToolbar()}<div class="tk-da-plot w-full min-h-[440px]"></div>`, plot };
}

function runMap(p) {
    const dataRows = activeRows();
    const lats = [];
    const lons = [];
    const texts = [];
    const colors = [];
    const sizes = [];
    dataRows.forEach((r) => {
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
    const colorLab = fieldLabel(p.color);
    const plot = {
        data: [
            {
                type: 'scattergeo',
                lat: lats,
                lon: lons,
                text: texts,
                mode: 'markers',
                name: 'Indicators',
                marker: {
                    size: sizes,
                    color: colors,
                    colorscale: 'Viridis',
                    showscale: Boolean(colorLab),
                    colorbar: colorLab ? { title: { text: colorLab } } : undefined,
                },
            },
        ],
        layout: chartLayout(p.title || 'Record locations', {
            height: 480,
            showLegend: true,
            geo: { projection: { type: 'natural earth' }, showland: true, landcolor: '#f1f5f9' },
        }),
    };
    return { html: `${plotToolbar()}<div class="tk-da-plot w-full min-h-[480px]"></div>`, plot };
}

async function refreshDataset() {
    setDashboardLoading(true);
    const status = document.getElementById('tk-da-status');
    let count = 0;
    try {
        count = await fetchRows();
    } finally {
        setDashboardLoading(false);
    }
    const inView = activeRows().length;
    if (status) {
        status.textContent = `${count} rows loaded · ${inView} in view · ${state.columns.length} fields`;
    }
    refreshActiveView();
}

async function bootstrap() {
    const status = document.getElementById('tk-da-status');
    try {
        initDashboard({
            cfg,
            state,
            Plotly,
            ss,
            PALETTE,
            CHART_TYPES,
            activeRows,
            columnMeta,
            columnOptions,
            columnOptionsForPicker,
            pickerRoleFor,
            galleryAvailability,
            isNumericColumn,
            filterColumnsCount,
            countByColumn,
            numericValues,
            runChart,
            plotLayout,
            chartLayout,
            fieldLabel,
            mergePlotLayout,
            compactCardPlotLayout,
            fieldLabelShort,
            axisMeasureLabel,
            mountPlotEl,
            tableHtml,
            escapeHtml,
            destroySortable: destroyDashboardSortable,
            initSortable: initDashboardSortable,
        });
        loadDashboardEditMode();
        syncEditModeUi();
        loadDashboardCards();
        seedDashboardHistory();
        await fetchSchema();
        renderFilters();
        renderColumnList();
        await refreshDataset();
        loadNotebook();
        state.cells.forEach((c) => {
            if (!c.params || Object.keys(c.params).length === 0) c.params = defaultParams(c.type);
        });
        renderCells();
        const savedTab = sessionStorage.getItem(TAB_KEY) || 'dashboard';
        setActiveTab(savedTab);
    } catch (e) {
        if (status) status.textContent = e.message || 'Failed to load';
    }
}

document.getElementById('tk-da-apply-filters')?.addEventListener('click', () => scheduleSlicerRefresh());

document.getElementById('tk-da-filter-program')?.addEventListener('change', () => {
    syncDependentSlicers('program');
    renderSearchSuggestions();
    scheduleSlicerRefresh();
});

document.getElementById('tk-da-filter-sites')?.addEventListener('change', () => {
    syncDependentSlicers('site');
    renderSearchSuggestions();
    scheduleSlicerRefresh();
});

document.getElementById('tk-da-filter-seasons')?.addEventListener('change', () => scheduleSlicerRefresh());

document.getElementById('tk-da-edit-mode')?.addEventListener('click', () => {
    state.dashboardEditMode = !state.dashboardEditMode;
    sessionStorage.setItem(EDIT_MODE_KEY(), state.dashboardEditMode ? '1' : '0');
    syncEditModeUi();
    renderDashboardCanvas();
});

document.getElementById('tk-deep-analysis-root')?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-da-template]');
    if (!btn) {
        return;
    }
    applyDashboardTemplate(btn.getAttribute('data-da-template'));
});

document.querySelectorAll('.tk-da-tab').forEach((btn) => {
    btn.addEventListener('click', () => setActiveTab(btn.dataset.daTab || 'dashboard'));
});

document.getElementById('tk-da-row-search')?.addEventListener('input', (e) => {
    state.rowSearch = e.target.value;
    refreshActiveView();
});

document.getElementById('tk-da-clear-crossfilter')?.addEventListener('click', () => {
    state.crossFilter = null;
    updateCrossFilterUi();
    refreshActiveView();
});

document.getElementById('tk-da-add-card')?.addEventListener('click', () => openGallery());
document.getElementById('tk-da-toolbar-add')?.addEventListener('click', () => openGallery());
document.getElementById('tk-da-reset-dashboard')?.addEventListener('click', () => resetDashboardLayout());

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
    const role = document.getElementById('tk-da-column-role-filter')?.value || '';
    renderColumnList(e.target.value, role);
});

document.getElementById('tk-da-column-role-filter')?.addEventListener('change', (e) => {
    const q = document.getElementById('tk-da-column-search')?.value || '';
    renderColumnList(q, e.target.value);
});

function isDeepAnalysisTypingTarget(el) {
    if (!el) {
        return false;
    }
    const tag = el.tagName;
    return el.isContentEditable || tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
}

function handleDeepAnalysisKeyboard(e) {
    if (!document.getElementById('tk-deep-analysis-root')) {
        return;
    }

    if (e.key === 'Escape') {
        const active = document.activeElement;
        if (isDeepAnalysisTypingTarget(active)) {
            active.blur();
            e.preventDefault();
            return;
        }
        if (document.getElementById('tk-da-fullscreen')) {
            closeFullscreen();
            e.preventDefault();
            return;
        }
        if (document.getElementById('tk-da-gallery')) {
            closeGallery();
            e.preventDefault();
            return;
        }
        if (document.getElementById('tk-da-config-drawer')) {
            closeConfigDrawer();
            e.preventDefault();
            return;
        }
        if (state.crossFilter) {
            state.crossFilter = null;
            updateCrossFilterUi();
            refreshActiveView();
            e.preventDefault();
        }
        return;
    }

    if (!(e.ctrlKey || e.metaKey) || isDeepAnalysisTypingTarget(e.target)) {
        return;
    }

    const key = e.key.toLowerCase();
    if (key === 'z' && !e.shiftKey) {
        e.preventDefault();
        undoDashboard();
    } else if (key === 'y' || (key === 'z' && e.shiftKey)) {
        e.preventDefault();
        redoDashboard();
    }
}

document.addEventListener('keydown', handleDeepAnalysisKeyboard);

if (document.getElementById('tk-deep-analysis-root')) {
    bootstrap();
}
