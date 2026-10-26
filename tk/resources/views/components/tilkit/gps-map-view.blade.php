@props([
    'points' => [],
    'height' => 'h-80',
    'legend' => true,
])

@php
    $pointCount = is_countable($points) ? count($points) : 0;
@endphp

<div {{ $attributes->merge(['class' => 'tk-gps-map-view']) }}>
    @if ($legend && $pointCount > 0)
        <div class="flex flex-wrap gap-3 text-xs text-tk-muted mb-3">
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-tk-accent"></span> Workbook GPS (Z)</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-600"></span> Field sighting</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-slate-500"></span> Site anchor</span>
        </div>
    @endif
    <div class="tk-gps-fullscreen-host relative" data-tk-gps-fullscreen-host>
        <x-tilkit.gps-fullscreen-button />
        <div class="rounded-tk border border-tk-line overflow-hidden bg-tk-soft shadow-tk-xs tk-gps-fullscreen-map-shell">
            <div
                class="tk-gps-map-canvas w-full {{ $height }}"
                data-tk-gps-view
                data-points='@json($points)'
                role="img"
                aria-label="Map of {{ $pointCount }} captured locations"
            ></div>
        </div>
    </div>
    @if ($pointCount === 0)
        <p class="text-sm text-tk-muted mt-3">No GPS points yet. Capture column Z or log a field sighting with a map pin.</p>
    @else
        <p class="text-xs text-tk-muted mt-2">{{ number_format($pointCount) }} location{{ $pointCount === 1 ? '' : 's' }} on map · click a pin for details</p>
    @endif
</div>
