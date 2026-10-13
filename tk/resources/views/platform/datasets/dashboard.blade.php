<x-app-layout>
    <x-slot name="header">
        @include('platform.datasets.partials.header')
    </x-slot>

    <x-analytics.acc-shell context="dataset" :acc-section="$accSection" :dataset="$dataset" title="Executive overview" subtitle="Interactive dashboard for your uploaded file">
        @include('analytics.partials.dataset-filter-bar', [
            'filters' => $filters,
            'dataset' => $dataset,
            'filterAction' => route('platform.datasets.show', $dataset),
        ])

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
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
                'label' => 'Category fields',
                'value' => number_format($overview['category_columns']),
            ])
            @include('analytics.partials.kpi-card', [
                'label' => 'AI analyses run',
                'value' => number_format($overview['analysis_runs']),
                'href' => route('platform.datasets.analyze', $dataset),
            ])
        </div>

        <div class="grid lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 tk-card p-5">
                <h3 class="font-semibold mb-1">Distribution · {{ $filters->dimensionA }}</h3>
                <p class="text-xs text-tk-muted mb-4">Click a segment to cross-filter the dashboard and explorer.</p>
                @if ($distributionA === [])
                    <p class="text-sm text-tk-muted py-8 text-center">No values to chart for this column. Pick another dimension above.</p>
                @else
                    <canvas id="accDatasetChartA" height="200"></canvas>
                @endif
            </div>
            <div class="tk-card p-5 text-sm">
                <h3 class="font-semibold mb-3">Data freshness</h3>
                <ul class="space-y-2 text-tk-muted">
                    <li class="flex justify-between gap-2"><span>Uploaded</span><span class="text-tk-ink">{{ $overview['uploaded_at']?->diffForHumans() }}</span></li>
                    <li class="flex justify-between gap-2"><span>Missing cells (profiled)</span><span class="text-tk-ink">{{ number_format($overview['missing_cells']) }}</span></li>
                    <li class="flex justify-between gap-2"><span>Numeric columns</span><span class="text-tk-ink">{{ number_format($overview['numeric_columns']) }}</span></li>
                </ul>
                <a href="{{ route('platform.datasets.analyze', $dataset) }}" class="tk-btn-primary text-sm w-full mt-4 text-center">Ask a question</a>
            </div>
        </div>

        @if ($lineSeries)
            <div class="tk-card p-5 mb-6">
                <h3 class="font-semibold mb-1">Line graph · {{ $lineSeries['y_column'] }} over {{ $lineSeries['x_column'] }}</h3>
                <p class="text-xs text-tk-muted mb-4">Trend view (first {{ count($lineSeries['labels']) }} points). Ask AI for custom line, scatter, or histogram charts.</p>
                <canvas id="accDatasetLineChart" height="120"></canvas>
            </div>
        @endif

        <div class="grid lg:grid-cols-2 gap-6 mb-6">
            <div class="tk-card p-5">
                <h3 class="font-semibold mb-4">By {{ $filters->dimensionB }}</h3>
                @if ($distributionB === [])
                    <p class="text-sm text-tk-muted py-8 text-center">No values to chart for this column.</p>
                @else
                    <canvas id="accDatasetChartB" height="200"></canvas>
                @endif
            </div>
            <div class="tk-card p-5">
                <h3 class="font-semibold mb-3">Column mapping</h3>
                <ul class="text-sm divide-y divide-tk-line max-h-64 overflow-y-auto">
                    @foreach ($dataset->columns as $col)
                        <li class="py-2 flex justify-between gap-2">
                            <span class="font-mono text-xs">{{ $col->name }}</span>
                            <span class="text-tk-muted">{{ $col->inferred_type }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </x-analytics.acc-shell>

    @push('scripts')
    <script>
        window.__tilkitAcc = {
            type: 'dataset',
            dimensionA: @json($distributionA),
            dimensionB: @json($distributionB),
            lineSeries: @json($lineSeries),
            baseUrl: @json(route('platform.datasets.show', $dataset)),
            query: @json($filters->toQueryArray()),
            dimensionAKey: @json($filters->dimensionA),
            dimensionBKey: @json($filters->dimensionB),
        };
    </script>
    @vite('resources/js/acc-dashboard.js')
    @endpush
</x-app-layout>
