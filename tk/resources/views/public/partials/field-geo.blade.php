@php
    $geoLabels = [
        'geopoint' => 'Tap the map or use your location to set a point.',
        'geotrace' => 'Add points to draw a path (at least 2). Tap the map or use your location.',
        'geoshape' => 'Add corners to draw an area (at least 3). The shape closes automatically.',
    ];
@endphp
<div
    class="tk-geo-widget rounded-md border border-tk-line overflow-hidden bg-white"
    data-tk-geo
    data-tk-geo-type="{{ $type }}"
    data-tk-geo-name="{{ $name }}"
    data-tk-geo-required="{{ $field->is_required ? '1' : '0' }}"
>
    <div class="h-52 w-full bg-tk-soft" data-tk-geo-map></div>
    <div class="p-3 space-y-2">
        <div class="flex flex-wrap gap-2">
            <button type="button" class="tk-btn-secondary text-xs py-1.5" data-tk-geo-locate>Use my location</button>
            @if ($type !== 'geopoint')
                <button type="button" class="tk-btn-secondary text-xs py-1.5" data-tk-geo-undo>Undo last point</button>
            @endif
            <button type="button" class="tk-btn-secondary text-xs py-1.5" data-tk-geo-clear>Clear</button>
        </div>
        <p class="text-xs text-tk-muted">{{ $geoLabels[$type] ?? '' }}</p>
        <p class="text-xs font-mono text-tk-ink" data-tk-geo-status>No location captured yet.</p>
        <input type="hidden" name="{{ $name }}" id="field-{{ $name }}" value="{{ old($name) }}" @if($field->is_required) data-required-geo @endif>
    </div>
</div>
