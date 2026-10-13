@php
    $panelCount = count($filters->charts);
    $maxCharts = (int) config('tilkit.dataset_analysis.max_dashboard_charts', 12);
    $defaults = [
        'type' => old('type', 'bar'),
        'column' => old('column', $dataset->columns->first()?->name ?? ''),
        'x_column' => old('x_column', $dataset->columns->first()?->name ?? ''),
        'y_column' => old('y_column', $dataset->columns->firstWhere('inferred_type', 'category')?->name ?? $dataset->columns->skip(1)->first()?->name ?? ''),
        'z_column' => old('z_column', $dataset->columns->first(fn ($c) => in_array($c->inferred_type, ['integer', 'decimal', 'number'], true))?->name ?? ''),
        'metric' => old('metric', 'count'),
        'colors' => old('colors'),
    ];
@endphp
<div class="tk-card p-5 mb-6 border-2 border-dashed border-tk-accent/40" id="dataset-add-panel">
    <div class="flex flex-wrap justify-between gap-2 mb-4">
        <div>
            <h3 class="font-semibold text-sm">Add one visualization</h3>
            <p class="text-xs text-tk-muted">Configure a single panel and click <strong>Run this panel</strong>. It renders in place; add the next one here. (<span data-dataset-panel-count>{{ $panelCount }}</span>/{{ $maxCharts }} on dashboard)</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($panelCount > 0)
                <button type="button" id="dataset-export-pdf" class="tk-btn-secondary text-xs">Save dashboard as PDF</button>
                <form method="POST" action="{{ route('platform.datasets.panels.clear', $dataset) }}"
                    class="dataset-panels-clear-form">
                    @csrf
                    <button type="submit" class="tk-btn-secondary text-xs">Clear all panels</button>
                </form>
            @endif
        </div>
    </div>

    <p class="text-sm text-red-600 mb-3 hidden dataset-panel-form-error" role="alert"></p>

    @if ($panelCount >= $maxCharts)
        <p class="text-sm text-tk-muted dataset-add-panel-limit">Panel limit reached. Remove a panel above before adding another.</p>
    @else
        <form method="POST" action="{{ route('platform.datasets.panels.store', $dataset) }}"
            class="dataset-panel-store-form"
            id="dataset-panel-store-form">
            @csrf
            @include('analytics.partials.dataset-panel-form-fields', [
                'dataset' => $dataset,
                'filters' => $filters,
                'defaults' => $defaults,
                'submitLabel' => 'Run this panel',
            ])
        </form>
    @endif
</div>
