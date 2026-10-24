@props([
    'latName',
    'lngName',
    'lat' => '',
    'lng' => '',
    'centerLat' => null,
    'centerLng' => null,
    'latLabel' => 'Latitude',
    'lngLabel' => 'Longitude',
])

@once
    @push('head')
        @vite(['resources/js/tilkit-gps.js'])
    @endpush
@endonce

<div
    {{ $attributes->merge(['class' => 'tk-gps-map']) }}
    data-tk-gps-map
    data-lat-name="{{ $latName }}"
    data-lng-name="{{ $lngName }}"
    @if ($centerLat !== null && $centerLat !== '') data-center-lat="{{ $centerLat }}" @endif
    @if ($centerLng !== null && $centerLng !== '') data-center-lng="{{ $centerLng }}" @endif
>
    <div class="rounded-tk border border-tk-line overflow-hidden bg-tk-soft shadow-tk-xs">
        <div class="tk-gps-map-canvas h-56 w-full" data-tk-gps-canvas role="application" aria-label="Map to set GPS coordinates"></div>
    </div>
    <div class="flex flex-wrap gap-2 mt-3">
        <button type="button" class="tk-btn-secondary text-xs py-2" data-tk-gps-locate>Use my location</button>
        <button type="button" class="tk-btn-secondary text-xs py-2" data-tk-gps-clear">Clear pin</button>
    </div>
    <p class="text-xs text-tk-muted mt-2" data-tk-gps-status>Tap the map to place the location pin, or use your device GPS.</p>
    <div class="grid grid-cols-2 gap-3 mt-3">
        <div>
            <label class="text-xs text-tk-muted block mb-1">{{ $latLabel }}</label>
            <input
                type="number"
                step="any"
                name="{{ $latName }}"
                value="{{ $lat }}"
                class="tk-input font-mono text-sm"
                placeholder="-1.234567"
                inputmode="decimal"
            >
        </div>
        <div>
            <label class="text-xs text-tk-muted block mb-1">{{ $lngLabel }}</label>
            <input
                type="number"
                step="any"
                name="{{ $lngName }}"
                value="{{ $lng }}"
                class="tk-input font-mono text-sm"
                placeholder="36.789012"
                inputmode="decimal"
            >
        </div>
    </div>
</div>
