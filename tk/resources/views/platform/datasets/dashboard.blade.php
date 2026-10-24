<x-app-layout :full-width="true">
    <x-slot name="header">
        @include('platform.datasets.partials.header')
    </x-slot>

    <x-analytics.acc-shell context="dataset" :acc-section="$accSection" :dataset="$dataset" :wide="true" title="Executive overview" subtitle="Add visualizations one at a time - each panel runs independently">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 w-full">
            @include('analytics.partials.kpi-card', [
                'label' => 'Rows in view',
                'value' => number_format($overview['filtered_rows']),
                'footnote' => $filters->hasActiveFilter() ? 'Filtered subset' : 'Full dataset',
                'href' => route('platform.datasets.explorer', $dataset).'?'.http_build_query($filters->toQueryArray()),
            ])
            @include('analytics.partials.kpi-card', [
                'label' => 'Columns',
                'value' => number_format($overview['columns']),
            ])
            @include('analytics.partials.kpi-card', [
                'label' => 'Panels on dashboard',
                'value' => number_format(count($chartPayloads)),
                'valueId' => 'dataset-kpi-panel-count',
            ])
            @include('analytics.partials.kpi-card', [
                'label' => 'AI analyses run',
                'value' => number_format($overview['analysis_runs']),
                'href' => route('platform.datasets.analyze', $dataset),
            ])
        </div>

        @if ($filters->hasActiveFilter())
            <div class="tk-card p-3 mb-4 text-xs flex flex-wrap items-center gap-2">
                <span class="text-tk-muted">Row filter active (applies to each panel when it runs):</span>
                <span class="bg-tk-soft px-2 py-0.5 rounded border border-tk-line">{{ $filters->filterColumn }} = {{ $filters->filterValue }}</span>
                <a href="{{ route('platform.datasets.show', $dataset) }}" class="text-tk-accent">Clear filter</a>
            </div>
        @endif

        <div id="dataset-panels-root">
            @include('analytics.partials.dataset-panels-stack', [
                'chartPayloads' => $chartPayloads,
                'dataset' => $dataset,
                'filters' => $filters,
            ])
        </div>

        <div id="dataset-add-panel-root">
            @include('analytics.partials.dataset-add-panel', [
                'filters' => $filters,
                'dataset' => $dataset,
            ])
        </div>

        <div class="tk-card p-5 mb-6 w-full">
            <h3 class="font-semibold mb-3">Column mapping</h3>
            <ul class="text-sm grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-8 gap-x-6 gap-y-1">
                @foreach ($dataset->columns as $col)
                    <li class="py-2 flex justify-between gap-2 border-b border-tk-line/60">
                        <span class="font-mono text-xs">{{ $col->name }}</span>
                        <span class="text-tk-muted">{{ $col->inferred_type }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-analytics.acc-shell>

    @push('scripts')
    <script>
        window.__tilkitAcc = {
            type: 'dataset',
            charts: @json($chartPayloads),
            baseUrl: @json(route('platform.datasets.show', $dataset)),
            query: @json($filters->toQueryArray()),
            datasetName: @json($dataset->name),
            csrf: @json(csrf_token()),
        };
    </script>
    @vite(['resources/js/acc-dashboard.js', 'resources/js/dataset-dashboard.js'])
    @endpush
</x-app-layout>
