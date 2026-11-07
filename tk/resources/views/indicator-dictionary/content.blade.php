<div
    class="tk-dictionary-page pb-16"
    x-data="tilkitDictionary(@js($d))"
    x-init="init()"
>
    <div class="lg:grid lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8 xl:gap-10">
        <nav class="lg:hidden mb-6 flex flex-wrap gap-2 text-xs" aria-label="Dictionary sections (mobile)">
            <a href="#dict-overview" class="tk-btn-secondary py-1 px-2">Overview</a>
            <a href="#dict-hierarchy" class="tk-btn-secondary py-1 px-2">Hierarchy</a>
            <a href="#dict-browse" class="tk-btn-secondary py-1 px-2">Browse codes</a>
        </nav>

        <nav class="hidden lg:block sticky top-6 self-start text-sm space-y-1" aria-label="Dictionary sections">
            <p class="text-xs font-semibold uppercase tracking-wide text-tk-muted mb-2">On this page</p>
            <a href="#dict-overview" class="block py-1 text-tk-accent hover:underline">Overview</a>
            <a href="#dict-hierarchy" class="block py-1 text-tk-muted hover:text-tk-ink">Hierarchy &amp; columns</a>
            <a href="#dict-stages" class="block py-1 text-tk-muted hover:text-tk-ink">Workshop stages</a>
            <a href="#dict-type-lines" class="block py-1 text-tk-muted hover:text-tk-ink">Seven type lines</a>
            <a href="#dict-browse" class="block py-1 text-tk-muted hover:text-tk-ink">Browse codes</a>
            <span class="block py-1 text-tk-muted text-xs mt-4">Markdown: <code class="text-[0.65rem]">tilkit_docs/indicator-hierarchy-reference.md</code></span>
        </nav>

        <div class="space-y-8 min-w-0">
            <div class="tk-card p-5 sm:p-6 space-y-4" id="dict-overview">
                <h2 class="tk-section-title text-lg">Overview</h2>
                <p class="text-sm text-tk-muted max-w-3xl">
                    This dictionary matches the <strong>Indicator Code Legend</strong> in the v4 master workbook
                    ({{ $d['code_count'] }} codes, {{ $d['version'] }}).
                    Use it to check that <strong>category</strong>, <strong>type line</strong>, <strong>legend code</strong>,
                    and <strong>workbook columns A–D</strong> stay aligned - e.g. plant flowering vs bird migration.
                </p>
                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border border-tk-line/80 bg-tk-soft/50 px-3 py-2">
                        <dt class="text-tk-muted text-xs">Categories</dt>
                        <dd class="font-medium text-tk-ink">6 (01–06)</dd>
                    </div>
                    <div class="rounded-lg border border-tk-line/80 bg-tk-soft/50 px-3 py-2">
                        <dt class="text-tk-muted text-xs">Type lines</dt>
                        <dd class="font-medium text-tk-ink">7 (01-PLT … 06-SEN)</dd>
                    </div>
                    <div class="rounded-lg border border-tk-line/80 bg-tk-soft/50 px-3 py-2">
                        <dt class="text-tk-muted text-xs">Column D intensities</dt>
                        <dd class="font-medium text-tk-ink">{{ implode(', ', array_column($d['intensity_options'], 'label')) }}</dd>
                    </div>
                    <div class="rounded-lg border border-tk-line/80 bg-tk-soft/50 px-3 py-2">
                        <dt class="text-tk-muted text-xs">Analytical levels</dt>
                        <dd class="font-medium text-tk-ink">{{ count($d['analytical_levels']) }} documented</dd>
                    </div>
                </dl>
            </div>

            <div class="tk-card p-5 sm:p-6 space-y-4" id="dict-hierarchy">
                <h2 class="tk-section-title text-lg">Hierarchy vs workbook columns</h2>
                <p class="text-xs text-tk-muted">What belongs at each level - avoid putting classification text in behaviour (D) or tercile (U).</p>
                <div class="overflow-x-auto -mx-1">
                    <table class="tk-table-premium w-full text-sm min-w-[36rem]">
                        <thead>
                            <tr>
                                <th class="px-2 py-2">Level</th>
                                <th class="px-2 py-2">Workbook</th>
                                <th class="px-2 py-2">Put here</th>
                                <th class="px-2 py-2">Do not put here</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($d['analytical_levels'] as $level)
                                <tr>
                                    <td class="px-2 py-2 font-medium whitespace-nowrap">{{ $level['name'] }}</td>
                                    <td class="px-2 py-2 font-mono text-xs">{{ $level['workbook'] }}</td>
                                    <td class="px-2 py-2 text-tk-muted">{{ $level['contains'] }}</td>
                                    <td class="px-2 py-2 text-tk-muted">{{ $level['excludes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tk-card p-5 sm:p-6 space-y-4" id="dict-stages">
                <h2 class="tk-section-title text-lg">Community Data List stages</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach ($d['community_stages'] as $key => $stage)
                        <div class="rounded-xl border border-tk-line/80 p-4 bg-tk-soft/30">
                            <h3 class="text-sm font-semibold text-tk-ink">{{ $key }}. {{ $stage['title'] }}</h3>
                            <p class="text-xs text-tk-muted mt-1">{{ $stage['hierarchy_note'] }}</p>
                            <p class="text-xs font-mono text-tk-muted mt-2">Columns: {{ implode(', ', $stage['columns']) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tk-card p-5 sm:p-6 space-y-3" id="dict-type-lines">
                <h2 class="tk-section-title text-lg">Seven type lines</h2>
                <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
                    @foreach ($d['taxonomy']['types'] ?? [] as $tl => $label)
                        <li class="rounded-lg border border-tk-line/70 px-3 py-2">
                            <span class="font-mono text-tk-ink">{{ $tl }}</span>
                            <span class="text-tk-muted"> - {{ $label }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="tk-card p-5 sm:p-6 space-y-5" id="dict-browse">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="tk-section-title text-lg">Browse legend codes</h2>
                        <p class="text-xs text-tk-muted mt-1">Open each level: <strong>category</strong> → <strong>type line</strong> → <strong>legend code</strong> → workbook &amp; behaviours. Indent shows depth.</p>
                    </div>
                    <p class="text-sm text-tk-muted" x-show="filteredCount !== totalCount" x-cloak>
                        Showing <span x-text="filteredCount"></span> of <span x-text="totalCount"></span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <label class="flex-1 min-w-[12rem]">
                        <span class="sr-only">Search</span>
                        <input type="search" class="tk-input w-full" placeholder="Search code, type, classification, behaviour…" x-model.trim="query" @input="applyFilter()">
                    </label>
                    <label class="min-w-[10rem]">
                        <span class="sr-only">Category</span>
                        <select class="tk-input w-full" x-model="category" @change="applyFilter()">
                            <option value="">All categories</option>
                            @foreach ($d['categories'] as $cat)
                                <option value="{{ $cat['key'] }}">{{ $cat['key'] }} - {{ $cat['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="min-w-[10rem]">
                        <span class="sr-only">Type line</span>
                        <select class="tk-input w-full" x-model="typeLine" @change="applyFilter()">
                            <option value="">All type lines</option>
                            @foreach ($d['taxonomy']['types'] ?? [] as $tl => $label)
                                <option value="{{ $tl }}">{{ $tl }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div id="dict-tree-root" class="tk-dictionary-tree border-t border-tk-line/80 pt-4">
                    <template x-for="cat in filteredCategories" :key="cat.key">
                        <details class="tk-details-panel tk-dict-summary-l0 mb-2">
                            <summary class="flex flex-wrap items-center gap-2">
                                <span class="text-tk-muted text-xs font-mono" x-text="'L1 · Cat ' + cat.key"></span>
                                <span x-text="cat.label"></span>
                                <span class="tk-details-panel-count" x-text="typeCodeCount(cat) + ' codes'"></span>
                            </summary>
                            <div class="tk-details-panel-body">
                                <div class="tk-details-panel-inner tk-dict-nest">
                                    <template x-for="type in cat.types" :key="type.key">
                                        <details class="tk-details-panel tk-dict-summary-l1">
                                            <summary class="flex flex-wrap items-center gap-2">
                                                <span class="text-tk-muted text-xs font-mono" x-text="'L2 · ' + type.key"></span>
                                                <span x-text="type.label"></span>
                                                <span class="tk-details-panel-count" x-text="type.codes.length + ' codes'"></span>
                                            </summary>
                                            <div class="tk-details-panel-body">
                                                <div class="tk-details-panel-inner tk-dict-nest tk-dict-nest-2">
                                                    <template x-for="row in type.codes" :key="row.code">
                                                        <details class="tk-details-panel tk-dict-summary-l2">
                                                            <summary class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                                <span class="text-tk-muted text-xs font-mono">L3</span>
                                                                <span class="font-mono text-tk-ink" x-text="row.code"></span>
                                                                <span class="text-tk-muted font-normal" x-text="row.classification"></span>
                                                                <span class="tk-details-panel-count" x-text="row.behaviours.length + ' D opts'"></span>
                                                            </summary>
                                                            <div class="tk-details-panel-body">
                                                                <div class="tk-details-panel-inner tk-dict-nest tk-dict-nest-3 space-y-2 pb-1">
                                                                    <details class="tk-details-panel tk-dict-summary-l3">
                                                                        <summary>Workbook A - category (column A)</summary>
                                                                        <div class="tk-details-panel-body">
                                                                            <div class="tk-details-panel-inner px-3 py-2">
                                                                                <p class="font-mono text-xs text-tk-ink bg-tk-soft/80 rounded px-2 py-1.5" x-text="row.workbook_a"></p>
                                                                            </div>
                                                                        </div>
                                                                    </details>
                                                                    <details class="tk-details-panel tk-dict-summary-l3">
                                                                        <summary>Workbook B - type · sub-type (column B)</summary>
                                                                        <div class="tk-details-panel-body">
                                                                            <div class="tk-details-panel-inner px-3 py-2">
                                                                                <p class="font-mono text-xs text-tk-ink bg-tk-soft/80 rounded px-2 py-1.5" x-text="row.workbook_b"></p>
                                                                            </div>
                                                                        </div>
                                                                    </details>
                                                                    <details class="tk-details-panel tk-dict-summary-l3">
                                                                        <summary x-text="'Column D behaviours (' + row.behaviours.length + ')'"></summary>
                                                                        <div class="tk-details-panel-body">
                                                                            <div class="tk-details-panel-inner px-3 py-2">
                                                                                <ul class="space-y-1.5 text-xs text-tk-ink">
                                                                                    <template x-for="b in row.behaviours" :key="b">
                                                                                        <li class="rounded-md border border-tk-line/70 bg-tk-soft/50 px-2 py-1.5" x-text="b"></li>
                                                                                    </template>
                                                                                </ul>
                                                                                <p class="text-[0.65rem] text-tk-muted mt-2">Capture as: behaviour text <code class="font-mono">| Intensity: High</code></p>
                                                                            </div>
                                                                        </div>
                                                                    </details>
                                                                </div>
                                                            </div>
                                                        </details>
                                                    </template>
                                                </div>
                                            </div>
                                        </details>
                                    </template>
                                </div>
                            </div>
                        </details>
                    </template>
                </div>

                <p class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2" x-show="filteredCount === 0" x-cloak>
                    No codes match your filters. Clear the search or category.
                </p>
            </div>

            <p class="text-xs text-tk-muted">
                Offline compendium: <code class="text-xs">tilkit_docs/indicator-hierarchy-reference.md</code>.
                Regenerate catalog: <code class="text-xs">python tilkit_docs/build_indicator_behaviour_catalog.py</code>.
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.tilkitDictionary = function (payload) {
        const allCategories = payload.categories ?? [];
        const flat = payload.flat_codes ?? [];

        return {
            query: '',
            category: '',
            typeLine: '',
            totalCount: flat.length,
            filteredCount: flat.length,
            filteredCategories: allCategories,
            init() {
                this.applyFilter();
            },
            typeCodeCount(cat) {
                return (cat.types || []).reduce((n, t) => n + (t.codes || []).length, 0);
            },
            refreshPanels() {
                this.$nextTick(() => {
                    const root = document.getElementById('dict-tree-root');
                    if (root && typeof window.initTkDetailsPanels === 'function') {
                        root.querySelectorAll('details.tk-details-panel').forEach((el) => {
                            delete el.dataset.tkDetailsReady;
                        });
                        window.initTkDetailsPanels(root);
                    }
                });
            },
            applyFilter() {
                const q = this.query.toLowerCase();
                const cat = this.category;
                const tl = this.typeLine;

                const matches = (row) => {
                    if (cat && row.category !== cat) return false;
                    if (tl && row.type_line !== tl) return false;
                    if (!q) return true;
                    const hay = [
                        row.code,
                        row.classification,
                        row.type_line,
                        row.type_label,
                        row.category_label,
                        ...(row.behaviours || []),
                    ].join(' ').toLowerCase();
                    return hay.includes(q);
                };

                this.filteredCategories = allCategories
                    .map((c) => ({
                        ...c,
                        types: (c.types || [])
                            .map((t) => ({
                                ...t,
                                codes: (t.codes || []).filter(matches),
                            }))
                            .filter((t) => t.codes.length > 0),
                    }))
                    .filter((c) => c.types.length > 0);

                this.filteredCount = this.filteredCategories.reduce(
                    (n, c) => n + c.types.reduce((m, t) => m + t.codes.length, 0),
                    0,
                );
                this.refreshPanels();
            },
        };
    };
</script>
@endpush
