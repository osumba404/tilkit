@php
    $def = $chart['definition'] ?? [];
    $editDefaults = [
        'type' => $def['type'] ?? 'bar',
        'column' => $def['column'] ?? '',
        'x_column' => $def['x_column'] ?? '',
        'y_column' => $def['y_column'] ?? '',
        'z_column' => $def['z_column'] ?? '',
        'metric' => $def['metric'] ?? 'count',
        'colors' => $chart['colors'] ?? null,
    ];
@endphp
<section
    id="panel-{{ $chart['id'] }}"
    data-panel-id="{{ $chart['id'] }}"
    @class(['tk-card p-5 w-full flex flex-col dataset-panel-card', 'ring-2 ring-tk-accent/30' => false])
    aria-label="Panel {{ $loopIndex + 1 }}"
    x-data="{ editing: false }">
    <div class="flex flex-wrap justify-between gap-2 mb-3 border-b border-tk-line pb-3">
        <div>
            <p class="text-xs text-tk-muted uppercase tracking-wide">Panel {{ $loopIndex + 1 }}</p>
            <h3 class="font-semibold text-base">{{ $chart['title'] ?? 'Visualization' }}</h3>
            <p class="text-xs text-tk-muted capitalize">{{ str_replace('_', ' ', $chart['type'] ?? 'chart') }}</p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
            <button type="button" class="tk-btn-secondary text-xs dataset-panel-export-png" data-panel-id="{{ $chart['id'] }}">
                Save as PNG
            </button>
            <button type="button" class="tk-btn-secondary text-xs" @click="editing = !editing" x-text="editing ? 'Close editor' : 'Edit & re-run'"></button>
            <form method="POST" action="{{ route('platform.datasets.panels.destroy', [$dataset, $chart['id']]) }}"
                class="dataset-panel-remove-form" data-panel-id="{{ $chart['id'] }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="tk-btn-secondary text-xs">Remove</button>
            </form>
        </div>
    </div>

    <div x-show="editing" x-cloak class="mb-4 p-4 bg-tk-soft/50 rounded-md border border-tk-line">
        <p class="text-xs text-tk-muted mb-3">Change settings and re-run this panel. The page will not reload.</p>
        <form method="POST" action="{{ route('platform.datasets.panels.update', [$dataset, $chart['id']]) }}"
            class="dataset-panel-update-form" data-panel-id="{{ $chart['id'] }}">
            @csrf
            @method('PATCH')
            @include('analytics.partials.dataset-panel-form-fields', [
                'dataset' => $dataset,
                'filters' => $filters,
                'defaults' => $editDefaults,
                'submitLabel' => 'Re-run this panel',
                'cancelEdit' => 'editing = false',
            ])
        </form>
    </div>

    <div class="dataset-panel-body flex flex-col flex-1" data-panel-body="{{ $chart['id'] }}">
        @include('analytics.partials.dataset-display-widget', [
            'chart' => $chart,
            'filterAction' => route('platform.datasets.show', $dataset),
            'query' => $filters->toQueryArray(),
        ])
    </div>
</section>
