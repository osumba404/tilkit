@props([
    'current' => '',
    'behaviours' => [],
    'intensityOptions' => [],
])

@php
    $catalog = app(\App\Support\Tilkit\IndicatorBehaviourCatalog::class);
    $parsed = $catalog->parseColumnD(is_scalar($current) ? (string) $current : '');
@endphp

<div
    class="space-y-2"
    data-tk-workbook-column-d
    data-intensity-options='@json($intensityOptions)'
>
    <p class="text-xs text-tk-muted">
        Choose an observable behaviour for this legend code, then intensity.
        <a href="{{ route('dictionary') }}" class="text-tk-accent underline" target="_blank" rel="noopener">Indicator dictionary</a>
    </p>
    <div class="grid sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-medium text-tk-muted mb-1">Observable behaviour</label>
            <select class="tk-input" data-tk-wb-d-behaviour required>
                <option value="">Choose behaviour</option>
                @foreach ($behaviours as $behaviour)
                    <option value="{{ $behaviour }}" @selected($parsed['behaviour'] === $behaviour)>{{ $behaviour }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-tk-muted mb-1">Intensity / strength</label>
            <select class="tk-input" data-tk-wb-d-intensity>
                <option value="">-</option>
            </select>
        </div>
    </div>
    <input type="hidden" name="workbook[D]" value="{{ is_scalar($current) ? $current : '' }}" data-tk-wb-d-value>
</div>
