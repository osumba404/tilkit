<x-app-layout :fullWidth="true">
    <x-slot name="header">
        <x-page-header title="Deep Analysis" subtitle="Compose visuals, configure fields, and present filtered workbook data">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.insights', $tool) }}" class="tk-btn-secondary">← Analysis hub</a>
                <button type="button" id="tk-da-reload-data" class="tk-btn-secondary">Refresh data</button>
                <button type="button" id="tk-da-add-card" class="tk-btn-primary hidden" data-da-tab-action="dashboard">+ Add card</button>
                <button type="button" id="tk-da-add-cell" class="tk-btn-primary hidden" data-da-tab-action="explore">Add analysis cell</button>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @include('tools.tilkit.partials.subnav')

    <div id="tk-deep-analysis-root" class="tk-da-shell animate-tk-fade-in">
        <div class="tk-da-command tk-card">
            <div class="flex flex-col lg:flex-row lg:items-center gap-4 lg:justify-between">
                <nav class="tk-da-tabs" aria-label="Analysis views">
                    <button type="button" class="tk-da-tab is-active" data-da-tab="dashboard">Dashboard</button>
                    <button type="button" class="tk-da-tab" data-da-tab="explore">Explore</button>
                    <button type="button" class="tk-da-tab" data-da-tab="data">Data table</button>
                </nav>
                <p id="tk-da-status" class="text-xs text-tk-muted lg:text-right shrink-0">Loading…</p>
            </div>

            <div class="tk-da-slicers mt-4 pt-4 border-t border-tk-line/70">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <div>
                        <label class="tk-da-slicer-label" for="tk-da-filter-program">Program</label>
                        <select id="tk-da-filter-program" class="tk-input w-full text-sm">
                            <option value="">All programs</option>
                        </select>
                    </div>
                    <div>
                        <label class="tk-da-slicer-label" for="tk-da-filter-sites">Site</label>
                        <select id="tk-da-filter-sites" class="tk-input w-full text-sm">
                            <option value="">All sites</option>
                        </select>
                    </div>
                    <div>
                        <label class="tk-da-slicer-label" for="tk-da-filter-seasons">Season</label>
                        <select id="tk-da-filter-seasons" class="tk-input w-full text-sm">
                            <option value="">All seasons</option>
                        </select>
                    </div>
                    <div>
                        <label class="tk-da-slicer-label" for="tk-da-row-search">Search in view</label>
                        <input type="search" id="tk-da-row-search" class="tk-input w-full text-sm" placeholder="Filter visible rows…" list="tk-da-search-suggestions" autocomplete="off" />
                        <datalist id="tk-da-search-suggestions"></datalist>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <button type="button" id="tk-da-apply-filters" class="tk-btn-secondary text-sm">Refresh now</button>
                    <button type="button" id="tk-da-clear-crossfilter" class="tk-btn-secondary text-sm hidden">Clear selection</button>
                    <span id="tk-da-crossfilter-chip" class="tk-da-chip hidden"></span>
                    <span class="text-xs text-tk-muted hidden lg:inline">Slicers auto-apply · Click charts to filter · Configure each visual for fields and width</span>
                </div>
            </div>
        </div>

        <section id="tk-da-panel-dashboard" class="tk-da-panel">
            <div class="tk-da-toolbar-dash">
                <button type="button" id="tk-da-edit-mode" class="tk-btn-secondary text-sm" aria-pressed="true">Done editing</button>
                <span class="tk-da-toolbar-divider"></span>
                <button type="button" id="tk-da-toolbar-add" class="tk-btn-primary text-sm">+ Add visual</button>
                <button type="button" id="tk-da-template-exec" class="tk-btn-secondary text-sm">Executive template</button>
                <button type="button" id="tk-da-template-field" class="tk-btn-secondary text-sm">Field template</button>
                <button type="button" id="tk-da-reset-dashboard" class="tk-btn-secondary text-sm">Clear all</button>
            </div>
            <div class="tk-da-canvas-wrap">
                <div id="tk-da-canvas-loading" class="tk-da-canvas-loading hidden" aria-live="polite">
                    <div class="tk-da-spinner" role="status"></div>
                </div>
                <div id="tk-da-dashboard-canvas" class="tk-da-canvas"></div>
            </div>
        </section>

        <section id="tk-da-panel-explore" class="tk-da-panel hidden">
            <div class="tk-da-workspace">
                <aside class="tk-da-sidebar tk-card p-4">
                    <h2 class="tk-section-title text-sm mb-3">Fields</h2>
                    <input type="search" id="tk-da-column-search" class="tk-input w-full text-sm mb-2" placeholder="Search columns…" />
                    <select id="tk-da-column-role-filter" class="tk-input w-full text-sm mb-2" aria-label="Filter columns by role">
                        <option value="">All fields</option>
                        <option value="measure">Numbers (charts, KPIs)</option>
                        <option value="dimension">Categories (bars, groups)</option>
                        <option value="dimension_pie">Pie / donut (≤15 values)</option>
                        <option value="date">Dates (timelines)</option>
                        <option value="geo_lat">Latitude</option>
                        <option value="geo_lon">Longitude</option>
                        <option value="table_only">Table-only (photos, GPS text)</option>
                    </select>
                    <ul id="tk-da-column-list" class="tk-da-column-list text-xs max-h-[28rem] overflow-y-auto"></ul>
                    <div class="mt-4 pt-4 border-t border-tk-line/80">
                        <button type="button" id="tk-da-reset-notebook" class="text-xs text-tk-muted underline">Reset notebook layout</button>
                    </div>
                </aside>
                <div class="tk-da-main min-w-0">
                    <div class="tk-card p-4 mb-4">
                        <p class="text-sm text-tk-muted">
                            Notebook mode: run cells independently, change parameters anytime, download chart PNGs.
                            Dashboard slicers and chart selections apply here too.
                        </p>
                    </div>
                    <div id="tk-da-cells" class="tk-da-cells space-y-4"></div>
                    <p id="tk-da-empty" class="text-sm text-tk-muted hidden">No cells yet. Use “Add analysis cell” to start.</p>
                </div>
            </div>
        </section>

        <section id="tk-da-panel-data" class="tk-da-panel hidden">
            <div class="tk-card p-4 overflow-x-auto">
                <p class="text-sm text-tk-muted mb-4">Preview of filtered rows (first 200). Export full workbook from the weather log home.</p>
                <div id="tk-da-data-table"></div>
            </div>
        </section>
    </div>

    @push('head')
        @vite(['resources/css/tilkit-deep-analysis.css', 'resources/js/tilkit-deep-analysis.js'])
    @endpush
    @push('scripts')
        <script>
            window.__tilkitDeepAnalysis = {
                toolSlug: @json($tool->slug),
                schemaUrl: @json($schemaUrl),
                datasetUrl: @json($datasetUrl),
                csrf: @json(csrf_token()),
            };
        </script>
    @endpush
</x-app-layout>
