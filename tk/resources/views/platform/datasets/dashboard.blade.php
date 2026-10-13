<x-app-layout :full-width="true">
    <x-slot name="header">
        @include('platform.datasets.partials.header')
    </x-slot>

    <x-analytics.acc-shell context="dataset" :acc-section="$accSection" :dataset="$dataset" :wide="true" title="Executive overview" subtitle="Build any number of charts with your chosen types and columns">
        @include('analytics.partials.dataset-chart-builder', [
            'filters' => $filters,
            'dataset' => $dataset,
            'filterAction' => route('platform.datasets.show', $dataset),
        ])

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
                'label' => 'Charts on page',
                'value' => number_format(count($chartPayloads)),
            ])
            @include('analytics.partials.kpi-card', [
                'label' => 'AI analyses run',
                'value' => number_format($overview['analysis_runs']),
                'href' => route('platform.datasets.analyze', $dataset),
            ])
        </div>

        @if ($chartPayloads === [])
            <div class="tk-card p-8 text-center text-tk-muted text-sm mb-6">
                No charts configured. Use the chart builder above to add visualizations.
            </div>
        @else
            <div class="dataset-panel-grid mb-6">
                @foreach ($chartPayloads as $chart)
                    @php
                        $widePanel = in_array($chart['render'] ?? 'canvas', ['crosstab', 'sample_rows', 'table'], true);
                    @endphp
                    <div @class(['tk-card p-5 flex flex-col dataset-panel-card', 'dataset-panel-wide' => $widePanel])>
                        <h3 class="font-semibold mb-1 text-sm">{{ $chart['title'] ?? 'Chart' }}</h3>
                        <p class="text-xs text-tk-muted mb-3 capitalize">{{ str_replace('_', ' ', $chart['type'] ?? 'chart') }}</p>
                        @include('analytics.partials.dataset-display-widget', [
                            'chart' => $chart,
                            'filterAction' => route('platform.datasets.show', $dataset),
                            'query' => $filters->toQueryArray(),
                        ])
                    </div>
                @endforeach
            </div>
        @endif

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
        };
    </script>
    @vite('resources/js/acc-dashboard.js')
    @endpush
</x-app-layout>
