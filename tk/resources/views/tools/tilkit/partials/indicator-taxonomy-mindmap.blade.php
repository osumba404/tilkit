@php
    $analysis = $indicatorAnalysisMindmap ?? [
        'root' => 'TILKIT Indicator Analysis from Top to Behavior',
        'has_codes' => false,
        'legend_levels' => [],
        'pillars' => [],
        'code_types' => [],
    ];
@endphp

<div class="tk-card p-5 space-y-4 mt-6">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="tk-section-title text-base">Indicator analysis mind map</h3>
        <span class="text-xs text-tk-muted">Drag nodes · click to expand · scroll to zoom</span>
    </div>

    @if (($analysis['legend_levels'] ?? []) !== [])
        <ol class="text-xs text-tk-muted flex flex-wrap gap-x-4 gap-y-1 list-decimal list-inside max-w-4xl">
            @foreach ($analysis['legend_levels'] as $level)
                <li><span class="text-tk-ink font-medium">{{ $level['label'] }}</span> — {{ $level['detail'] }}</li>
            @endforeach
        </ol>
    @endif

    @if (! ($analysis['has_codes'] ?? false))
        <p class="text-sm text-amber-800">
            Import the master legend to fill the code branches:
            <code class="text-xs">php artisan tilkit:import-legend</code>
        </p>
    @endif

    <div class="tk-mindmap-host" data-tk-indicator-mindmap role="region" aria-label="Draggable indicator mind map">
        <script type="application/json" data-tk-mindmap-json>@json($analysis)</script>
        <div class="tk-mindmap-mount" data-tk-mindmap-mount></div>
    </div>
</div>
