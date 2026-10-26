<x-app-layout :fullWidth="true">
    <x-slot name="header">
        <x-page-header title="Deep Analysis" subtitle="Notebook-style EDA, charts, statistics, and time series on workbook data">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.insights', $tool) }}" class="tk-btn-secondary">← Analysis hub</a>
                <button type="button" id="tk-da-reload-data" class="tk-btn-secondary">Reload dataset</button>
                <button type="button" id="tk-da-add-cell" class="tk-btn-primary">Add cell</button>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @include('tools.tilkit.partials.subnav')

    <div id="tk-deep-analysis-root" class="tk-da-workspace animate-tk-fade-in">
        <aside class="tk-da-sidebar tk-card p-4">
            <h2 class="tk-section-title text-sm mb-3">Dataset</h2>
            <p id="tk-da-status" class="text-xs text-tk-muted mb-4">Loading schema…</p>
            <label class="block text-xs font-medium text-tk-muted mb-1">Program</label>
            <select id="tk-da-filter-program" class="tk-input w-full mb-3 text-sm">
                <option value="">All programs</option>
            </select>
            <label class="block text-xs font-medium text-tk-muted mb-1">Sites</label>
            <select id="tk-da-filter-sites" class="tk-input w-full mb-3 text-sm" multiple size="4"></select>
            <label class="block text-xs font-medium text-tk-muted mb-1">Seasons</label>
            <select id="tk-da-filter-seasons" class="tk-input w-full mb-3 text-sm" multiple size="3"></select>
            <button type="button" id="tk-da-apply-filters" class="tk-btn-primary w-full text-sm mb-4">Apply filters &amp; refresh rows</button>

            <h3 class="text-xs font-semibold text-tk-ink mb-2">Columns</h3>
            <input type="search" id="tk-da-column-search" class="tk-input w-full text-sm mb-2" placeholder="Search columns…" />
            <ul id="tk-da-column-list" class="tk-da-column-list text-xs max-h-64 overflow-y-auto"></ul>

            <div class="mt-4 pt-4 border-t border-tk-line/80">
                <button type="button" id="tk-da-reset-notebook" class="text-xs text-tk-muted underline">Reset notebook layout</button>
            </div>
        </aside>

        <section class="tk-da-main">
            <div class="tk-card p-4 mb-4">
                <p class="text-sm text-tk-muted">
                    Run each cell independently. Edit parameters anytime and click <strong class="text-tk-ink">Run cell</strong> again.
                    Charts support download (PNG). Statistical tests use the filtered row set loaded on the left.
                </p>
            </div>
            <div id="tk-da-cells" class="tk-da-cells space-y-4"></div>
            <p id="tk-da-empty" class="text-sm text-tk-muted hidden">No cells yet. Use “Add cell” to start exploring.</p>
        </section>
    </div>

    @push('head')
        @vite(['resources/js/tilkit-deep-analysis.js'])
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
