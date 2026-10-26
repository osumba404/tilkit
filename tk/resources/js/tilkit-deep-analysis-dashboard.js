/**
 * Customizable dashboard cards for Deep Analysis (BI-style layout + config drawer).
 */

let deps = {};

const DASHBOARD_STORAGE_KEY = () => `tilkit-da-dashboard-${deps.cfg?.toolSlug || 'default'}`;

const dashHistory = { past: [], future: [], max: 50, restoring: false };

function cloneCardsForHistory() {
    return JSON.parse(
        JSON.stringify(
            deps.state.dashboardCards.map(({ id, type, title, span, config }) => ({ id, type, title, span, config })),
        ),
    );
}

export function seedDashboardHistory() {
    dashHistory.past = [cloneCardsForHistory()];
    dashHistory.future = [];
}

export function pushDashboardHistory() {
    if (dashHistory.restoring || !deps.state) {
        return;
    }
    const snap = cloneCardsForHistory();
    const last = dashHistory.past[dashHistory.past.length - 1];
    if (last && JSON.stringify(last) === JSON.stringify(snap)) {
        return;
    }
    dashHistory.past.push(snap);
    if (dashHistory.past.length > dashHistory.max) {
        dashHistory.past.shift();
    }
    dashHistory.future = [];
}

function restoreDashboardSnapshot(snap) {
    dashHistory.restoring = true;
    deps.state.dashboardCards = snap.map((c) => normalizeDashboardCard(c));
    saveDashboardCards();
    closeConfigDrawer();
    closeGallery();
    renderDashboardCanvas();
    dashHistory.restoring = false;
}

export function undoDashboard() {
    if (dashHistory.past.length < 2) {
        return false;
    }
    const current = dashHistory.past.pop();
    dashHistory.future.push(current);
    const prev = dashHistory.past[dashHistory.past.length - 1];
    restoreDashboardSnapshot(JSON.parse(JSON.stringify(prev)));

    return true;
}

export function redoDashboard() {
    if (dashHistory.future.length === 0) {
        return false;
    }
    const current = cloneCardsForHistory();
    dashHistory.past.push(current);
    const next = dashHistory.future.pop();
    restoreDashboardSnapshot(JSON.parse(JSON.stringify(next)));

    return true;
}

export const WIDGET_CATALOG = {
    kpi: {
        label: 'KPI metric',
        category: 'Metrics',
        icon: '123',
        desc: 'Headline number with context line',
        defaultSpan: 3,
        defaultConfig: () => ({ metric: 'rows', customColumn: 'U', agg: 'mean', label: '', showSub: true }),
    },
    chart: {
        label: 'Chart builder',
        category: 'Charts',
        icon: 'CH',
        desc: 'Bar, line, pie, scatter, histogram, box, area',
        defaultSpan: 6,
        defaultConfig: () => ({ chartType: 'bar', x: '_site', y: 'U', title: '', cumulative: false }),
    },
    category_breakdown: {
        label: 'Breakdown',
        category: 'Charts',
        icon: 'PI',
        desc: 'Donut or bar counts for one field',
        defaultSpan: 4,
        defaultConfig: () => ({ column: 'U', viz: 'donut', title: '' }),
    },
    timeline: {
        label: 'Timeline',
        category: 'Charts',
        icon: 'TL',
        desc: 'Volume or aggregated values by date',
        defaultSpan: 8,
        defaultConfig: () => ({ dateCol: '_collection_date', valueCol: '', agg: 'count', title: '' }),
    },
    heatmap: {
        label: 'Matrix',
        category: 'Charts',
        icon: 'MX',
        desc: 'Cross-tab two categorical fields',
        defaultSpan: 6,
        defaultConfig: () => ({ rowField: '_season', colField: '_site', title: '' }),
    },
    funnel: {
        label: 'Funnel',
        category: 'Metrics',
        icon: 'FN',
        desc: 'Logged → sighted → confirmed → approved',
        defaultSpan: 4,
        defaultConfig: () => ({ title: 'Capture funnel' }),
    },
    map: {
        label: 'Map',
        category: 'Geo',
        icon: 'MP',
        desc: 'GPS / latitude & longitude',
        defaultSpan: 8,
        defaultConfig: () => ({ lat: '_latitude', lon: '_longitude', color: '_site', title: '' }),
    },
    leaderboard: {
        label: 'Ranked list',
        category: 'Tables',
        icon: 'RK',
        desc: 'Top categories by row count',
        defaultSpan: 6,
        defaultConfig: () => ({ column: '_indicator_uid', limit: 8, title: '' }),
    },
    field_snapshot: {
        label: 'Field quality',
        category: 'Tables',
        icon: 'FQ',
        desc: 'Present vs missing counts',
        defaultSpan: 6,
        defaultConfig: () => ({ columns: '_site,_season,U,N,AP', title: '' }),
    },
    data_table: {
        label: 'Data table',
        category: 'Tables',
        icon: 'TB',
        desc: 'Scrollable preview of row values',
        defaultSpan: 12,
        defaultConfig: () => ({ columns: '_indicator_uid,_site,_season,U,N,AP', limit: 15, title: '' }),
    },
};

const SPAN_CLASSES = { 3: 'tk-da-span-3', 4: 'tk-da-span-4', 6: 'tk-da-span-6', 8: 'tk-da-span-8', 12: 'tk-da-span-12' };
const PLAIN_HEATMAP_SCALE = [[0, '#f1f5f9'], [0.5, '#5eead4'], [1, '#0d9488']];

const TEMPLATES = {
    executive: [
        { type: 'kpi', title: 'Total rows', span: 3, config: { metric: 'rows', label: 'Workbook rows', showSub: true } },
        { type: 'kpi', title: 'Sighted', span: 3, config: { metric: 'sighted', label: 'Sighted', showSub: true } },
        { type: 'kpi', title: 'Confirmed', span: 3, config: { metric: 'confirmed', label: 'Confirmed', showSub: true } },
        { type: 'kpi', title: 'Approved', span: 3, config: { metric: 'approved', label: 'Approved', showSub: true } },
        { type: 'timeline', title: 'Collection timeline', span: 8, config: { dateCol: '_collection_date', valueCol: '', agg: 'count', title: 'Records over time' } },
        { type: 'category_breakdown', title: 'Tercile mix', span: 4, config: { column: 'U', viz: 'donut', title: 'Forecast tercile (U)' } },
        { type: 'chart', title: 'Tercile by site', span: 6, config: { chartType: 'bar', x: '_site', y: 'U', title: 'Mean tercile by site', cumulative: false } },
        { type: 'chart', title: 'By season', span: 6, config: { chartType: 'bar', x: '_season', y: 'U', title: 'Mean tercile by season', cumulative: false } },
        { type: 'map', title: 'Field map', span: 8, config: { lat: '_latitude', lon: '_longitude', color: '_site', title: 'Indicator locations' } },
        { type: 'funnel', title: 'Validation funnel', span: 4, config: { title: 'Capture funnel' } },
    ],
    field: [
        { type: 'map', title: 'GPS map', span: 12, config: { lat: '_latitude', lon: '_longitude', color: '_site', title: 'Sightings map' } },
        { type: 'leaderboard', title: 'Indicators', span: 6, config: { column: '_indicator_uid', limit: 10, title: 'Top indicators' } },
        { type: 'category_breakdown', title: 'Validation', span: 6, config: { column: 'AP', viz: 'bar', title: 'Validation (AP)' } },
        { type: 'field_snapshot', title: 'Field coverage', span: 12, config: { columns: '_site,_season,N,AC,AD,AP,CE', title: 'Coverage' } },
    ],
    validation: [
        { type: 'kpi', title: 'Total rows', span: 3, config: { metric: 'rows', label: 'Workbook rows', showSub: true } },
        { type: 'kpi', title: 'Sighted', span: 3, config: { metric: 'sighted', label: 'Sighted (N)', showSub: true } },
        { type: 'kpi', title: 'Confirmed', span: 3, config: { metric: 'confirmed', label: 'Confirmed (AC)', showSub: true } },
        { type: 'kpi', title: 'Approved', span: 3, config: { metric: 'approved', label: 'Approved (AD)', showSub: true } },
        { type: 'funnel', title: 'Validation funnel', span: 6, config: { title: 'Capture funnel' } },
        { type: 'category_breakdown', title: 'Validation status', span: 6, config: { column: 'AP', viz: 'donut', title: 'Status (AP)' } },
        { type: 'heatmap', title: 'Volume by season & site', span: 8, config: { rowField: '_season', colField: '_site' } },
        { type: 'chart', title: 'Sighted by site', span: 4, config: { chartType: 'bar', x: '_site', y: 'N', title: 'Mean sighted (N)', cumulative: false } },
        { type: 'field_snapshot', title: 'Sign-off fields', span: 12, config: { columns: '_indicator_uid,_site,_season,N,AC,AD,AP', title: 'Validation chain' } },
    ],
};

/** Preset keys → toolbar labels (keep in sync with deep-analysis.blade.php). */
export const DASHBOARD_TEMPLATE_KEYS = ['executive', 'field', 'validation'];

export function initDashboard(shared) {
    deps = shared;
    loadDashboardCards();
}

export function saveDashboardCards() {
    localStorage.setItem(
        DASHBOARD_STORAGE_KEY(),
        JSON.stringify(
            deps.state.dashboardCards.map(({ id, type, title, span, config }) => ({ id, type, title, span, config })),
        ),
    );
}

export function loadDashboardCards() {
    try {
        const raw = localStorage.getItem(DASHBOARD_STORAGE_KEY());
        if (raw) {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                deps.state.dashboardCards = parsed.map((c) => normalizeDashboardCard(c));
                return;
            }
        }
    } catch {
        /* ignore */
    }
    deps.state.dashboardCards = [];
}

function newCard(partial) {
    return normalizeDashboardCard({
        id: crypto.randomUUID(),
        editing: false,
        ...partial,
    });
}

/** Merge saved config with defaults so renders never throw on missing fields. */
function normalizeDashboardCard(card) {
    const meta = WIDGET_CATALOG[card.type];
    const defaults = meta?.defaultConfig?.() || {};
    card.title = card.title || meta?.label || card.type || 'Visual';
    card.span = Number(card.span) || meta?.defaultSpan || 6;
    card.config = {
        ...defaults,
        ...(card.config && typeof card.config === 'object' ? card.config : {}),
    };

    return card;
}

export function applyDashboardTemplate(key) {
    const t = TEMPLATES[key];
    if (!t) return;
    if (deps.state.dashboardCards.length && !confirm('Replace your current dashboard with this template?')) {
        return;
    }
    pushDashboardHistory();
    deps.state.dashboardCards = t.map((c) => newCard({ ...c, config: { ...c.config } }));
    saveDashboardCards();
    renderDashboardCanvas();
}

export function suggestStarterLayout() {
    applyDashboardTemplate('executive');
}

export function addDashboardCard(type) {
    const meta = WIDGET_CATALOG[type];
    if (!meta) return;
    pushDashboardHistory();
    const card = newCard({
        type,
        title: meta.label,
        span: meta.defaultSpan,
        config: meta.defaultConfig(),
    });
    deps.state.dashboardCards.push(card);
    saveDashboardCards();
    closeGallery();
    renderDashboardCanvas();
    openConfigDrawer(card.id);
}

export function openGallery() {
    closeGallery();
    const backdrop = document.createElement('div');
    backdrop.className = 'tk-da-gallery-backdrop';
    backdrop.id = 'tk-da-gallery';
    const categories = [...new Set(Object.values(WIDGET_CATALOG).map((w) => w.category))];
    backdrop.innerHTML = `
        <div class="tk-da-gallery-panel" role="dialog" aria-labelledby="tk-da-gallery-title">
            <div class="px-5 py-4 border-b border-tk-line/60 flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
                <div>
                    <h2 id="tk-da-gallery-title" class="text-lg font-semibold text-tk-ink">Visual gallery</h2>
                    <p class="text-xs text-tk-muted mt-1">Choose a visual type — you will set fields next.</p>
                </div>
                <input type="search" id="tk-da-gallery-search" class="tk-input text-sm w-full sm:w-56" placeholder="Search visuals…" />
                <button type="button" class="tk-da-icon-btn shrink-0" data-close-gallery aria-label="Close">✕</button>
            </div>
            <div class="tk-da-gallery-grid" id="tk-da-gallery-items">
                ${Object.entries(WIDGET_CATALOG)
                    .map(([key, w]) => {
                        const avail = deps.galleryAvailability?.(deps.state.columns, deps.isNumericColumn)?.[key];
                        const ready = avail !== false;
                        const badge = ready
                            ? ''
                            : '<span class="tk-da-gallery-badge">Needs compatible fields</span>';
                        return `<button type="button" class="tk-da-gallery-item${ready ? '' : ' tk-da-gallery-item--muted'}" data-add-widget="${key}" data-category="${deps.escapeHtml(w.category)}" data-search="${deps.escapeHtml(`${w.label} ${w.desc} ${w.category}`.toLowerCase())}" ${ready ? '' : 'disabled title="Load data with fields that match this visual (numbers, categories, dates, or GPS)"'}>
                    <div class="tk-da-gallery-item-icon">${deps.escapeHtml(w.icon)}</div>
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-tk-accent">${deps.escapeHtml(w.category)}</p>
                    <p class="font-semibold text-sm text-tk-ink mt-1">${deps.escapeHtml(w.label)}</p>
                    ${badge}
                    <p class="text-xs text-tk-muted mt-1 leading-relaxed">${deps.escapeHtml(w.desc)}</p>
                </button>`;
                    })
                    .join('')}
            </div>
        </div>`;
    document.body.appendChild(backdrop);
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop || e.target.closest('[data-close-gallery]')) closeGallery();
    });
    backdrop.querySelectorAll('[data-add-widget]').forEach((btn) => {
        btn.addEventListener('click', () => addDashboardCard(btn.getAttribute('data-add-widget')));
    });
    backdrop.querySelector('#tk-da-gallery-search')?.addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase();
        backdrop.querySelectorAll('.tk-da-gallery-item').forEach((item) => {
            item.classList.toggle('hidden', q.length > 0 && !item.getAttribute('data-search')?.includes(q));
        });
    });
}

export function closeGallery() {
    document.getElementById('tk-da-gallery')?.remove();
}

/** Re-render card bodies only (keep layout shell; avoids blank Plotly hosts after slicer refresh). */
export function refreshDashboardCardBodies() {
    const root = document.getElementById('tk-da-dashboard-canvas');
    if (!root || !deps.state.dashboardCards.length) {
        renderDashboardCanvas();
        return;
    }
    const shells = root.querySelectorAll('.tk-da-premium-card');
    if (shells.length !== deps.state.dashboardCards.length) {
        renderDashboardCanvas();
        return;
    }
    deps.state.dashboardCards.forEach((card) => renderCardContent(card));
    scheduleDashboardPlotResize();
}

export function renderDashboardCanvas() {
    const root = document.getElementById('tk-da-dashboard-canvas');
    if (!root) return;

    deps.destroySortable?.();
    closeConfigDrawer();
    root.innerHTML = '';
    root.classList.toggle('is-view-mode', !deps.state.dashboardEditMode);

    if (!deps.state.dashboardCards.length) {
        root.innerHTML = `<div class="tk-da-canvas-empty">
            <ol class="tk-da-steps">
                <li><span>1</span> Apply slicers (program, site, season)</li>
                <li><span>2</span> Add visuals from the gallery</li>
                <li><span>3</span> Configure each card — fields, width, title</li>
            </ol>
            <p class="text-sm text-tk-muted max-w-lg mb-6 mt-6">Build a dashboard like Power BI or Looker: only the cards you need, wired to your workbook columns.</p>
            <div class="flex flex-wrap gap-3 justify-center">
                <button type="button" id="tk-da-empty-add" class="tk-btn-primary text-sm">Open visual gallery</button>
                <button type="button" class="tk-btn-secondary text-sm" data-da-template="executive">Executive template</button>
                <button type="button" class="tk-btn-secondary text-sm" data-da-template="field">Field template</button>
                <button type="button" class="tk-btn-secondary text-sm" data-da-template="validation">Validation template</button>
            </div>
        </div>`;
        document.getElementById('tk-da-empty-add')?.addEventListener('click', openGallery);
        root.querySelectorAll('[data-da-template]').forEach((btn) => {
            btn.addEventListener('click', () => applyDashboardTemplate(btn.getAttribute('data-da-template')));
        });
        return;
    }

    const rowCount = deps.activeRows().length;
    deps.state.dashboardCards.forEach((card, index) => {
        root.appendChild(buildCardShell(card, index, rowCount));
    });

    deps.state.dashboardCards.forEach((card) => renderCardContent(card));
    scheduleDashboardPlotResize();

    if (deps.state.dashboardEditMode) {
        deps.initSortable?.(root);
    }
    bindCardEvents(root);
}

function scheduleDashboardPlotResize() {
    requestAnimationFrame(() => {
        deps.state.dashboardCards.forEach((card) => {
            const el = document.getElementById(`plot-${card.id}`);
            if (el?.querySelector?.('.plotly')) {
                deps.Plotly?.Plots?.resize(el);
            }
        });
    });
}

function buildCardShell(card, index, rowCount) {
    const spanClass = SPAN_CLASSES[card.span] || SPAN_CLASSES[6];
    const meta = WIDGET_CATALOG[card.type];
    const article = document.createElement('article');
    article.className = `tk-da-premium-card ${spanClass}`;
    article.setAttribute('data-card-id', card.id);
    article.style.animationDelay = `${Math.min(index * 50, 350)}ms`;
    const edit = deps.state.dashboardEditMode;
    article.innerHTML = `
        <header class="tk-da-card-head">
            ${edit ? '<span class="tk-da-card-grip" title="Drag to reorder">⠿</span>' : '<span class="tk-da-card-grip-placeholder"></span>'}
            <div class="tk-da-card-title-wrap">
                <h3 class="tk-da-card-title" data-card-title>${deps.escapeHtml(card.title || meta?.label || 'Visual')}</h3>
                <p class="tk-da-card-subtitle">${deps.escapeHtml(meta?.label || card.type)} · ${rowCount} rows in view</p>
            </div>
            <div class="tk-da-card-actions">
                ${edit ? '<button type="button" class="tk-da-text-btn" data-card-edit>Configure</button>' : ''}
                <div class="tk-da-card-menu">
                    <button type="button" class="tk-da-icon-btn" data-card-menu aria-label="More">⋯</button>
                    <div class="tk-da-card-menu-pop hidden">
                        <button type="button" data-card-dl>Download PNG</button>
                        <button type="button" data-card-fullscreen>Expand</button>
                        ${edit ? '<button type="button" data-card-duplicate>Duplicate</button>' : ''}
                        ${edit ? '<button type="button" data-card-remove class="is-danger">Remove</button>' : ''}
                    </div>
                </div>
            </div>
        </header>
        <div class="tk-da-card-body" data-card-body></div>
    `;
    return article;
}

function bindCardEvents(root) {
    root.querySelectorAll('.tk-da-premium-card').forEach((el) => {
        const id = el.getAttribute('data-card-id');
        const card = deps.state.dashboardCards.find((c) => c.id === id);
        if (!card) return;

        el.querySelector('[data-card-edit]')?.addEventListener('click', () => openConfigDrawer(id));
        el.querySelector('[data-card-remove]')?.addEventListener('click', () => {
            pushDashboardHistory();
            deps.state.dashboardCards = deps.state.dashboardCards.filter((c) => c.id !== id);
            saveDashboardCards();
            renderDashboardCanvas();
        });
        el.querySelector('[data-card-duplicate]')?.addEventListener('click', () => {
            pushDashboardHistory();
            const copy = newCard({
                type: card.type,
                title: `${card.title} (copy)`,
                span: card.span,
                config: JSON.parse(JSON.stringify(card.config)),
            });
            deps.state.dashboardCards.push(copy);
            saveDashboardCards();
            renderDashboardCanvas();
        });
        el.querySelector('[data-card-dl]')?.addEventListener('click', () => {
            pop?.classList.add('hidden');
            const plot = el.querySelector('[data-plot-root]');
            if (plot && deps.Plotly) {
                deps.Plotly.downloadImage(plot, { format: 'png', width: 1400, height: 800, filename: `tilkit-${card.type}` });
            }
        });
        el.querySelector('[data-card-fullscreen]')?.addEventListener('click', () => {
            pop?.classList.add('hidden');
            openFullscreen(card);
        });
        const menuBtn = el.querySelector('[data-card-menu]');
        const pop = el.querySelector('.tk-da-card-menu-pop');
        menuBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            const willOpen = pop?.classList.contains('hidden');
            document.querySelectorAll('.tk-da-card-menu-pop').forEach((p) => p.classList.add('hidden'));
            if (willOpen) {
                pop?.classList.remove('hidden');
            }
        });
        pop?.querySelectorAll('button').forEach((btn) => {
            btn.addEventListener('click', (e) => e.stopPropagation());
        });
    });
    if (!window.__tkDaMenuBound) {
        window.__tkDaMenuBound = true;
        document.addEventListener('click', () => {
            document.querySelectorAll('.tk-da-card-menu-pop').forEach((p) => p.classList.add('hidden'));
        });
    }
}

function openFullscreen(card) {
    closeFullscreen();
    const wrap = document.createElement('div');
    wrap.className = 'tk-da-fullscreen';
    wrap.id = 'tk-da-fullscreen';
    wrap.innerHTML = `
        <div class="tk-da-fullscreen-inner">
            <header class="tk-da-fullscreen-head">
                <h2>${deps.escapeHtml(card.title)}</h2>
                <button type="button" class="tk-btn-secondary text-sm" data-close-fs>Close</button>
            </header>
            <div class="tk-da-fullscreen-body" id="tk-da-fs-plot"></div>
        </div>`;
    document.body.appendChild(wrap);
    document.body.classList.add('overflow-hidden');
    wrap.querySelector('[data-close-fs]')?.addEventListener('click', closeFullscreen);
    wrap.addEventListener('click', (e) => {
        if (e.target === wrap) closeFullscreen();
    });
    const temp = { ...card, id: `${card.id}-fs` };
    const host = wrap.querySelector('#tk-da-fs-plot');
    host.innerHTML = plotHost(temp);
    renderCardContent({ ...card, id: `${card.id}-fs` });
}

export function closeFullscreen() {
    document.getElementById('tk-da-fullscreen')?.remove();
    document.body.classList.remove('overflow-hidden');
}

export function closeConfigDrawer() {
    document.getElementById('tk-da-config-drawer')?.remove();
    deps.state.configDrawerCardId = null;
}

export function openConfigDrawer(cardId) {
    const card = deps.state.dashboardCards.find((c) => c.id === cardId);
    if (!card) return;
    pushDashboardHistory();
    closeConfigDrawer();
    deps.state.configDrawerCardId = cardId;
    const drawer = document.createElement('aside');
    drawer.id = 'tk-da-config-drawer';
    drawer.className = 'tk-da-config-drawer';
    drawer.innerHTML = `
        <div class="tk-da-config-drawer-head">
            <div>
                <p class="text-[10px] uppercase tracking-wide text-tk-muted font-semibold">Configure visual</p>
                <h2 class="text-base font-semibold text-tk-ink">${deps.escapeHtml(WIDGET_CATALOG[card.type]?.label || card.type)}</h2>
            </div>
            <button type="button" class="tk-da-icon-btn" data-close-drawer aria-label="Close">✕</button>
        </div>
        <div class="tk-da-config-drawer-body" data-drawer-form></div>
        <div class="tk-da-config-drawer-foot">
            <button type="button" class="tk-btn-secondary text-sm w-full" data-close-drawer>Done</button>
        </div>`;
    document.body.appendChild(drawer);
    const formRoot = drawer.querySelector('[data-drawer-form]');
    formRoot.innerHTML = buildConfigForm(card);
    wireConfigForm(formRoot, card);
    drawer.querySelectorAll('[data-close-drawer]').forEach((b) => b.addEventListener('click', closeConfigDrawer));
}

function wireConfigForm(panel, card) {
    if (panel.dataset.daFormWired === '1') {
        return;
    }
    panel.dataset.daFormWired = '1';

    const applyLive = () => {
        readConfigForm(panel, card);
        const titleInput = panel.querySelector('[data-field-title]');
        if (titleInput?.value) {
            card.title = titleInput.value;
        }
        const titleEl = document.querySelector(`.tk-da-premium-card[data-card-id="${card.id}"] [data-card-title]`);
        if (titleEl) titleEl.textContent = card.title;
        const shell = document.querySelector(`.tk-da-premium-card[data-card-id="${card.id}"]`);
        if (shell) {
            const spanClass = SPAN_CLASSES[card.span] || SPAN_CLASSES[6];
            shell.className = `tk-da-premium-card ${spanClass}`;
        }
        renderCardContent(card);
        saveDashboardCards();
    };

    const debouncedApply = debounce(applyLive, 280);

    panel.addEventListener('change', (e) => {
        const input = e.target;
        if (!input.matches('[data-cfg], [data-field-title], [data-cfg-span]')) {
            return;
        }
        const key = input.getAttribute('data-cfg');
        if (key && ['chartType', 'viz', 'metric'].includes(key)) {
            readConfigForm(panel, card);
            const mount = panel.querySelector('[data-role-pickers]');
            if (mount) {
                mount.innerHTML = buildRolePickers(card);
            }
        }
        applyLive();
    });

    panel.addEventListener('input', (e) => {
        if (e.target.matches('[data-cfg], [data-field-title], [data-cfg-span]')) {
            debouncedApply();
        }
    });
}

function debounce(fn, ms) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
}

function plotHost(card) {
    return `<div class="tk-da-plot-host w-full min-h-[220px]" data-plot-root id="plot-${card.id}"></div>`;
}

function emptyState(msg) {
    return `<div class="tk-da-card-empty"><p>${deps.escapeHtml(msg)}</p></div>`;
}

function renderCardContent(card) {
    card = normalizeDashboardCard(card);
    const article = document.querySelector(`.tk-da-premium-card[data-card-id="${card.id}"]`);
    const bodyEl = article?.querySelector('[data-card-body]');
    if (!bodyEl) return;

    const rows = deps.activeRows();
    const cfg = card.config;

    if (rows.length === 0) {
        bodyEl.innerHTML = emptyState('No rows match current slicers or search.');
        return;
    }

    try {
        renderCardBody(card, bodyEl, rows, cfg);
        scheduleDashboardPlotResize();
    } catch (err) {
        console.error('Deep Analysis card render failed', card.type, err);
        bodyEl.innerHTML = emptyState(err?.message || 'Could not render this visual. Check field choices in Configure.');
    }
}

function renderCardBody(card, bodyEl, rows, cfg) {
    switch (card.type) {
        case 'kpi':
            bodyEl.innerHTML = renderKpiBody(rows, cfg);
            break;
        case 'chart':
            bodyEl.innerHTML = plotHost(card);
            renderChartCard(card, rows);
            break;
        case 'category_breakdown':
            bodyEl.innerHTML = plotHost(card);
            renderCategoryCard(card, rows);
            break;
        case 'map':
            bodyEl.innerHTML = plotHost(card);
            renderMapCard(card, rows);
            break;
        case 'timeline':
            bodyEl.innerHTML = plotHost(card);
            renderTimelineCard(card, rows);
            break;
        case 'heatmap':
            bodyEl.innerHTML = plotHost(card);
            renderHeatmapCard(card, rows);
            break;
        case 'funnel':
            bodyEl.innerHTML = plotHost(card);
            renderFunnelCard(card, rows);
            break;
        case 'leaderboard':
            bodyEl.innerHTML = renderLeaderboardBody(rows, cfg);
            break;
        case 'field_snapshot':
            bodyEl.innerHTML = renderSnapshotBody(rows, cfg);
            break;
        case 'data_table':
            bodyEl.innerHTML = renderDataTableBody(rows, cfg);
            break;
        default:
            bodyEl.innerHTML = emptyState('Unknown visual type.');
    }
}

function kpiValue(rows, cfg) {
    const n = rows.length;
    switch (cfg.metric) {
        case 'rows':
            return { v: n.toLocaleString(), sub: 'Rows in current view' };
        case 'sighted': {
            const s = rows.filter((r) => r.N === 1 || r.N === '1').length;
            return { v: s.toLocaleString(), sub: n ? `${Math.round((s / n) * 100)}% sighted` : '—' };
        }
        case 'confirmed': {
            const s = rows.filter((r) => r.AC === 1 || r.AC === '1').length;
            return { v: s.toLocaleString(), sub: n ? `${Math.round((s / n) * 100)}% confirmed` : '—' };
        }
        case 'approved': {
            const s = rows.filter((r) => r.AD === 1 || r.AD === '1').length;
            return { v: s.toLocaleString(), sub: n ? `${Math.round((s / n) * 100)}% approved` : '—' };
        }
        case 'avg_u': {
            const nums = deps.numericValues('U', rows);
            return { v: nums.length ? deps.ss.mean(nums).toFixed(2) : '—', sub: 'Mean tercile (U)' };
        }
        case 'distinct_sites':
            return { v: String(new Set(rows.map((r) => r._site).filter(Boolean)).size), sub: 'Unique sites' };
        case 'distinct_seasons':
            return { v: String(new Set(rows.map((r) => r._season).filter(Boolean)).size), sub: 'Unique seasons' };
        case 'column_agg': {
            const col = cfg.customColumn || 'U';
            const nums = deps.numericValues(col, rows);
            if (!nums.length) return { v: '—', sub: 'No numeric data' };
            const agg = cfg.agg || 'mean';
            const val =
                agg === 'mean'
                    ? deps.ss.mean(nums).toFixed(2)
                    : agg === 'sum'
                      ? deps.ss.sum(nums).toFixed(2)
                      : agg === 'median'
                        ? deps.ss.median(nums).toFixed(2)
                        : agg === 'min'
                          ? deps.ss.min(nums).toFixed(2)
                          : deps.ss.max(nums).toFixed(2);
            return { v: val, sub: `${agg} · ${col}` };
        }
        default:
            return { v: '—', sub: '' };
    }
}

function renderKpiBody(rows, cfg) {
    const { v, sub } = kpiValue(rows, cfg);
    const label = cfg.label || 'Metric';
    return `<div class="tk-da-kpi-premium">
        <p class="tk-da-kpi-premium-value">${deps.escapeHtml(String(v))}</p>
        <p class="tk-da-kpi-premium-label">${deps.escapeHtml(label)}</p>
        ${cfg.showSub !== false ? `<p class="tk-da-kpi-premium-delta">${deps.escapeHtml(sub)}</p>` : ''}
    </div>`;
}

function renderLeaderboardBody(rows, cfg) {
    const col = cfg.column || '_indicator_uid';
    const limit = cfg.limit || 8;
    const counts = deps.countByColumn(rows, col);
    const sorted = Object.entries(counts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, limit);
    const max = sorted[0]?.[1] || 1;
    if (!sorted.length) return emptyState('No values for this field.');
    return `<div class="tk-da-leaderboard tk-da-leaderboard-premium">${sorted
        .map(
            ([k, c], i) => `<div class="tk-da-leaderboard-row">
            <span class="tk-da-rank">${i + 1}</span>
            <span class="truncate flex-1 text-tk-ink">${deps.escapeHtml(k)}</span>
            <span class="tk-da-leaderboard-bar" style="width:${Math.max(10, (c / max) * 100)}%"></span>
            <span class="tabular-nums text-tk-muted w-10 text-right">${c}</span>
        </div>`,
        )
        .join('')}</div>`;
}

function renderSnapshotBody(rows, cfg) {
    const cols = String(cfg.columns || '')
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean);
    const tableRows = cols.map((id) => {
        const present = rows.filter((r) => r[id] !== null && r[id] !== '').length;
        return [deps.columnMeta(id)?.label || id, present, rows.length - present];
    });
    return `<div class="tk-da-table-wrap">${deps.tableHtml(['Field', 'Present', 'Missing'], tableRows)}</div>`;
}

function renderDataTableBody(rows, cfg) {
    const cols = String(cfg.columns || '')
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean);
    const limit = cfg.limit || 15;
    const head = cols.map((c) => deps.columnMeta(c)?.label || c);
    const body = rows.slice(0, limit).map((r) => cols.map((c) => r[c] ?? ''));
    return `<div class="tk-da-table-wrap">${deps.tableHtml(head, body)}${rows.length > limit ? `<p class="text-xs text-tk-muted mt-2">Showing ${limit} of ${rows.length} rows</p>` : ''}</div>`;
}

function renderChartCard(card, rows) {
    const cfg = card.config;
    const ct = cfg.chartType || 'bar';
    if (ct === 'pie' && !cfg.x) {
        const el = document.getElementById(`plot-${card.id}`);
        if (el) el.innerHTML = emptyState('Choose a category field for pie slices in Configure.');
        return;
    }
    if ((ct === 'histogram' || ct === 'box') && !cfg.y) {
        const el = document.getElementById(`plot-${card.id}`);
        if (el) el.innerHTML = emptyState('Choose a numeric column in Configure.');
        return;
    }
    if (ct !== 'pie' && ct !== 'histogram' && ct !== 'box' && (!cfg.x || !cfg.y)) {
        const el = document.getElementById(`plot-${card.id}`);
        if (el) el.innerHTML = emptyState('Choose category (X) and value (Y) fields in Configure.');
        return;
    }

    const result = deps.runChart({ ...cfg, title: cfg.title || card.title, compact: true });
    const el = document.getElementById(`plot-${card.id}`);
    if (!el || !result?.plot) {
        if (el) el.innerHTML = emptyState('Could not build chart — check fields and chart type.');
        return;
    }
    const cross = ct === 'histogram' || ct === 'box' ? cfg.y : cfg.x;
    const h = card.span >= 8 ? 340 : 280;
    const layout = deps.compactCardPlotLayout?.(result.plot.layout, h) || result.plot.layout;
    deps.mountPlotEl(el, result.plot.data, layout, cross);
}

function renderCategoryCard(card, rows) {
    const col = card.config.column || 'U';
    const counts = deps.countByColumn(rows, col);
    const labels = Object.keys(counts);
    const values = Object.values(counts);
    const el = document.getElementById(`plot-${card.id}`);
    if (!el || !labels.length) {
        if (el) el.innerHTML = emptyState('No data for this field.');
        return;
    }
    const colLab = deps.fieldLabel?.(col) || col;
    const title = card.config.title || card.title;
    const data =
        card.config.viz === 'bar'
            ? [
                  {
                      type: 'bar',
                      x: labels,
                      y: values,
                      showlegend: false,
                      marker: { color: deps.PALETTE[0] },
                      hovertemplate: '%{x}<br>Count: %{y}<extra></extra>',
                  },
              ]
            : [
                  {
                      type: 'pie',
                      labels,
                      values,
                      hole: 0.45,
                      name: colLab,
                      marker: { colors: deps.PALETTE },
                      hovertemplate: '%{label}<br>Count: %{value}<extra></extra>',
                  },
              ];
    const shortCol = deps.fieldLabelShort?.(col) || col;
    const layout =
        card.config.viz === 'bar'
            ? deps.compactCardPlotLayout(
                  deps.chartLayout('', { height: 280, xTitle: shortCol, yTitle: 'Count', showLegend: false }),
                  280,
              )
            : deps.compactCardPlotLayout(deps.chartLayout('', { height: 280, showLegend: true }), 280, { keepLegend: true });
    deps.mountPlotEl(el, data, layout, col);
}

function renderMapCard(card, rows) {
    const { lat, lon, color, title } = card.config;
    const lats = [];
    const lons = [];
    const texts = [];
    rows.forEach((r) => {
        const la = Number(r[lat]);
        const lo = Number(r[lon]);
        if (Number.isNaN(la) || Number.isNaN(lo)) return;
        lats.push(la);
        lons.push(lo);
        texts.push(String(r._indicator_uid || ''));
    });
    const el = document.getElementById(`plot-${card.id}`);
    if (!el) return;
    if (!lats.length) {
        el.innerHTML = emptyState('No coordinates — check lat/lon fields or capture GPS (Z).');
        return;
    }
    deps.mountPlotEl(
        el,
        [
            {
                type: 'scattergeo',
                lat: lats,
                lon: lons,
                text: texts,
                mode: 'markers',
                name: 'Indicators',
                marker: {
                    size: 10,
                    color: deps.PALETTE[0],
                    opacity: 0.9,
                },
            },
        ],
        deps.compactCardPlotLayout(
            deps.chartLayout('', {
                height: 320,
                showLegend: false,
                geo: { projection: { type: 'natural earth' }, showland: true },
            }),
            320,
        ),
        color,
    );
}

function renderTimelineCard(card, rows) {
    const { dateCol, valueCol, agg, title } = card.config;
    const buckets = {};
    rows.forEach((r) => {
        const d = r[dateCol];
        if (!d) return;
        const key = String(d).slice(0, 10);
        if (!buckets[key]) buckets[key] = [];
        if (agg === 'count' || !valueCol) buckets[key].push(1);
        else {
            const v = Number(r[valueCol]);
            if (!Number.isNaN(v)) buckets[key].push(v);
        }
    });
    const dates = Object.keys(buckets).sort();
    if (!dates.length) {
        const el = document.getElementById(`plot-${card.id}`);
        if (el) el.innerHTML = emptyState('No dates in this field for the current view.');
        return;
    }
    const y = dates.map((d) => {
        const vals = buckets[d];
        if (agg === 'count' || !valueCol) return vals.length;
        if (agg === 'sum') return deps.ss.sum(vals);
        if (agg === 'mean') return deps.ss.mean(vals);
        return vals.length;
    });
    const el = document.getElementById(`plot-${card.id}`);
    if (!el) {
        return;
    }
    const dateLab = deps.fieldLabelShort?.(dateCol) || dateCol;
    const yTitle =
        agg === 'count' || !valueCol
            ? 'Row count'
            : agg === 'sum'
              ? deps.axisMeasureLabel?.(valueCol, 'Sum') || 'Sum'
              : agg === 'mean'
                ? deps.axisMeasureLabel?.(valueCol, 'Mean') || 'Mean'
                : 'Value';
    deps.mountPlotEl(
        el,
        [
            {
                type: 'scatter',
                mode: 'lines+markers',
                fill: 'tozeroy',
                x: dates,
                y,
                showlegend: false,
                line: { color: deps.PALETTE[0], width: 2 },
            },
        ],
        deps.compactCardPlotLayout(
            deps.chartLayout('', { height: 300, xTitle: dateLab, yTitle, showLegend: false }),
            300,
        ),
        dateCol,
    );
}

function renderHeatmapCard(card, rows) {
    const rf = card.config.rowField || '_season';
    const cf = card.config.colField || '_site';
    const rowLabels = [...new Set(rows.map((r) => String(r[rf] ?? '(blank)')))].sort();
    const colLabels = [...new Set(rows.map((r) => String(r[cf] ?? '(blank)')))].sort();
    const z = rowLabels.map((row) => colLabels.map((col) => rows.filter((r) => String(r[rf]) === row && String(r[cf]) === col).length));
    const el = document.getElementById(`plot-${card.id}`);
    const rowLab = deps.fieldLabelShort?.(rf) || rf;
    const colLab = deps.fieldLabelShort?.(cf) || cf;
    deps.mountPlotEl(
        el,
        [
            {
                type: 'heatmap',
                x: colLabels,
                y: rowLabels,
                z,
                colorscale: PLAIN_HEATMAP_SCALE,
                showscale: true,
                colorbar: {
                    title: { text: 'Count', font: { color: deps.plotTheme?.().text || '#334155' } },
                    tickfont: { color: deps.plotTheme?.().tick || '#64748b' },
                },
                hovertemplate: '%{y} × %{x}<br>Count: %{z}<extra></extra>',
            },
        ],
        deps.compactCardPlotLayout(
            deps.chartLayout('', {
                height: 280,
                xTitle: colLab,
                yTitle: rowLab,
                colorbarTitle: 'Count',
            }),
            280,
        ),
        cf,
    );
}

function renderFunnelCard(card, rows) {
    const steps = [
        ['Logged', rows.length],
        ['Sighted', rows.filter((r) => r.N === 1 || r.N === '1').length],
        ['Confirmed', rows.filter((r) => r.AC === 1 || r.AC === '1').length],
        ['Approved', rows.filter((r) => r.AD === 1 || r.AD === '1').length],
    ];
    const el = document.getElementById(`plot-${card.id}`);
    deps.mountPlotEl(
        el,
        [
            {
                type: 'bar',
                x: steps.map((s) => s[0]),
                y: steps.map((s) => s[1]),
                showlegend: false,
                marker: { color: deps.PALETTE[0] },
                hovertemplate: '%{x}<br>Records: %{y}<extra></extra>',
            },
        ],
        deps.compactCardPlotLayout(
            deps.chartLayout('', { height: 280, xTitle: 'Stage', yTitle: 'Records', showLegend: false }),
            280,
        ),
        null,
    );
}

const PICKER_LABELS = {
    customColumn: 'Numeric field',
    x: 'Category (X)',
    y: 'Value (Y)',
    column: 'Field',
    lat: 'Latitude',
    lon: 'Longitude',
    color: 'Cross-filter when clicking',
    dateCol: 'Date field',
    valueCol: 'Value field (optional)',
    rowField: 'Rows',
    colField: 'Columns',
};

function roleFieldPicker(card, fieldKey, value, allowEmpty = false) {
    const role = deps.pickerRoleFor?.(card.type, fieldKey, card.config);
    if (role === null || role === undefined) {
        return '';
    }
    const picked = deps.columnOptionsForPicker?.(role, value ?? '', allowEmpty) || { html: '', hint: '' };
    let label = PICKER_LABELS[fieldKey] || fieldKey;
    if (card.type === 'chart' && (card.config?.chartType === 'histogram' || card.config?.chartType === 'box') && fieldKey === 'y') {
        label = 'Numeric column';
    }
    if (card.type === 'chart' && card.config?.chartType === 'pie' && fieldKey === 'x') {
        label = 'Category (slices)';
    }
    return `<label class="tk-da-field-label block">
        <span class="font-medium text-sm">${deps.escapeHtml(label)}</span>
        <span class="block text-[10px] font-normal text-tk-muted mt-0.5 leading-snug">${deps.escapeHtml(picked.hint || '')}</span>
        <select class="tk-input w-full text-sm mt-1" data-cfg="${fieldKey}">${picked.html}</select>
    </label>`;
}

function buildRolePickers(card) {
    const c = card.config || {};
    switch (card.type) {
        case 'kpi':
            return `${roleFieldPicker(card, 'customColumn', c.customColumn || 'U')}`;
        case 'chart':
            return `${roleFieldPicker(card, 'x', c.x)}${roleFieldPicker(card, 'y', c.y)}`;
        case 'category_breakdown':
            return roleFieldPicker(card, 'column', c.column);
        case 'map':
            return `${roleFieldPicker(card, 'lat', c.lat)}${roleFieldPicker(card, 'lon', c.lon)}${roleFieldPicker(card, 'color', c.color)}`;
        case 'timeline':
            return `${roleFieldPicker(card, 'dateCol', c.dateCol)}${roleFieldPicker(card, 'valueCol', c.valueCol || '', true)}`;
        case 'heatmap':
            return `${roleFieldPicker(card, 'rowField', c.rowField)}${roleFieldPicker(card, 'colField', c.colField)}`;
        case 'leaderboard':
            return roleFieldPicker(card, 'column', c.column);
        default:
            return '';
    }
}

function buildConfigForm(card) {
    card = normalizeDashboardCard(card);
    const c = card.config;
    let inner = `<div class="space-y-4">
        <label class="tk-da-field-label">Display title<input class="tk-input w-full text-sm mt-1" data-field-title value="${deps.escapeHtml(card.title || '')}" /></label>
        <label class="tk-da-field-label">Card width
            <select class="tk-input w-full text-sm mt-1" data-cfg-span>
                ${[3, 4, 6, 8, 12].map((s) => `<option value="${s}" ${card.span === s ? 'selected' : ''}>${s} columns (of 12)</option>`).join('')}
            </select>
        </label>`;

    switch (card.type) {
        case 'kpi':
            inner += `<label class="tk-da-field-label">Metric
                <select class="tk-input w-full text-sm mt-1" data-cfg="metric">
                    ${[
                        ['rows', 'Row count'],
                        ['sighted', 'Sighted'],
                        ['confirmed', 'Confirmed'],
                        ['approved', 'Approved'],
                        ['avg_u', 'Average tercile (U)'],
                        ['distinct_sites', 'Distinct sites'],
                        ['distinct_seasons', 'Distinct seasons'],
                        ['column_agg', 'Custom numeric field'],
                    ]
                        .map(([k, lab]) => `<option value="${k}" ${c.metric === k ? 'selected' : ''}>${lab}</option>`)
                        .join('')}
                </select>
            </label>
            <label class="tk-da-field-label">KPI label<input class="tk-input w-full text-sm mt-1" data-cfg="label" value="${deps.escapeHtml(c.label || '')}" /></label>
            <div data-role-pickers class="space-y-4">${buildRolePickers(card)}</div>
            <label class="tk-da-field-label">Aggregation<select class="tk-input w-full text-sm mt-1" data-cfg="agg">${['mean', 'sum', 'median', 'min', 'max'].map((a) => `<option value="${a}" ${c.agg === a ? 'selected' : ''}>${a}</option>`).join('')}</select></label>`;
            break;
        case 'chart':
            inner += `<label class="tk-da-field-label">Chart type<select class="tk-input w-full text-sm mt-1" data-cfg="chartType">${deps.CHART_TYPES.map((t) => `<option value="${t.id}" ${c.chartType === t.id ? 'selected' : ''}>${t.label}</option>`).join('')}</select></label>
            <p class="text-[10px] text-tk-muted -mt-2">Pie uses low-cardinality categories; histogram/box use one numeric column only.</p>
            <div data-role-pickers class="space-y-4">${buildRolePickers(card)}</div>`;
            break;
        case 'category_breakdown':
            inner += `<label class="tk-da-field-label">Style<select class="tk-input w-full text-sm mt-1" data-cfg="viz"><option value="donut" ${c.viz === 'donut' ? 'selected' : ''}>Donut</option><option value="bar" ${c.viz === 'bar' ? 'selected' : ''}>Bar</option></select></label>
            <div data-role-pickers class="space-y-4">${buildRolePickers(card)}</div>`;
            break;
        case 'map':
            inner += `<div data-role-pickers class="space-y-4">${buildRolePickers(card)}</div>`;
            break;
        case 'timeline':
            inner += `<div data-role-pickers class="space-y-4">${buildRolePickers(card)}</div>
            <label class="tk-da-field-label">Aggregate<select class="tk-input w-full text-sm mt-1" data-cfg="agg"><option value="count" ${c.agg === 'count' ? 'selected' : ''}>Count rows</option><option value="mean" ${c.agg === 'mean' ? 'selected' : ''}>Mean</option><option value="sum" ${c.agg === 'sum' ? 'selected' : ''}>Sum</option></select></label>`;
            break;
        case 'heatmap':
            inner += `<div data-role-pickers class="space-y-4">${buildRolePickers(card)}</div>`;
            break;
        case 'leaderboard':
            inner += `<div data-role-pickers class="space-y-4">${buildRolePickers(card)}</div>
            <label class="tk-da-field-label">Show top<input type="number" min="3" max="25" class="tk-input w-full text-sm mt-1" data-cfg="limit" value="${c.limit || 8}" /></label>`;
            break;
        case 'field_snapshot':
        case 'data_table':
            inner += `<label class="tk-da-field-label">Column ids (comma-separated)<input class="tk-input w-full text-sm mt-1" data-cfg="columns" value="${deps.escapeHtml(c.columns || '')}" placeholder="_site,U,N,AP" /></label>`;
            if (card.type === 'data_table') {
                inner += `<label class="tk-da-field-label">Max rows<input type="number" min="5" max="50" class="tk-input w-full text-sm mt-1" data-cfg="limit" value="${c.limit || 15}" /></label>`;
            }
            break;
        default:
            break;
    }
    inner += `<p class="text-xs text-tk-muted">Changes preview live on the card behind this panel.</p></div>`;
    return inner;
}

function readConfigForm(panel, card) {
    normalizeDashboardCard(card);
    panel.querySelectorAll('[data-cfg]').forEach((input) => {
        const key = input.getAttribute('data-cfg');
        if (!key) return;
        card.config[key] = input.type === 'number' ? Number(input.value) : input.value;
    });
    const spanSel = panel.querySelector('[data-cfg-span]');
    if (spanSel) card.span = Number(spanSel.value) || 6;
}

export function resetDashboardLayout() {
    if (confirm('Remove all cards from this dashboard?')) {
        pushDashboardHistory();
        deps.state.dashboardCards = [];
        saveDashboardCards();
        closeConfigDrawer();
        renderDashboardCanvas();
    }
}
