@php
    $workbookValues = $workbookValues ?? [];
    $defaultSightedAt = old('sighted_at', isset($workbookValues['CI']) && $workbookValues['CI'] !== '' ? $workbookValues['CI'] : '');
@endphp
<form method="POST" action="{{ route('tools.tilkit.records.sightings.store', [$tool, $record]) }}" class="grid md:grid-cols-2 gap-4 tk-stagger" data-tk-form-draft="tilkit:sighting:{{ $record->id }}">
    @csrf
    <div>
        <x-tilkit.workbook-field-label letter="CI" />
        <input type="date" name="sighted_at" value="{{ $defaultSightedAt }}" required class="tk-input">
    </div>
    <div>
        <x-tilkit.workbook-field-label letter="AB" />
        <input type="text" name="observed_by" class="tk-input" placeholder="Observer name or group">
    </div>
    <div class="md:col-span-2">
        <x-tilkit.workbook-field-label letter="O" />
        <textarea name="place_text" rows="2" class="tk-input" placeholder="Place description and context"></textarea>
    </div>
    <div class="md:col-span-2">
        <x-tilkit.workbook-field-label letter="Z" />
        <p class="text-xs text-tk-muted mb-2">Tap the map or use your device location to set the pin.</p>
        <x-tilkit.gps-map
            lat-name="latitude"
            lng-name="longitude"
            :center-lat="$record->site?->latitude"
            :center-lng="$record->site?->longitude"
            lat-label="Latitude"
            lng-label="Longitude"
        />
    </div>
    <div class="md:col-span-2">
        <x-tilkit.workbook-field-label letter="AA" />
        <input type="text" name="landmark" class="tk-input" placeholder="Landmark near the pin (tree, school, river bend…)">
    </div>
    <div class="md:col-span-2">
        <button type="submit" class="tk-btn-primary">Add sighting</button>
    </div>
</form>
@if ($record->sightings->isNotEmpty())
    <ul class="mt-6 space-y-3">
        @foreach ($record->sightings as $s)
            <li class="tk-card p-3 text-sm">
                <div class="flex flex-wrap justify-between gap-2">
                    <span class="font-medium text-tk-ink">{{ $s->sighted_at->format('Y-m-d') }}</span>
                    @if ($s->latitude !== null && $s->longitude !== null)
                        <a href="https://www.openstreetmap.org/?mlat={{ $s->latitude }}&amp;mlon={{ $s->longitude }}#map=16/{{ $s->latitude }}/{{ $s->longitude }}"
                           class="text-tk-accent text-xs underline" target="_blank" rel="noopener noreferrer">Open on map</a>
                    @endif
                </div>
                <p class="text-tk-muted mt-1">{{ $s->place_text ?? 'No place description' }}</p>
                @if ($s->landmark)
                    <p class="text-xs text-tk-muted mt-1"><span class="text-tk-ink">Landmark:</span> {{ $s->landmark }}</p>
                @endif
                @if ($s->latitude !== null && $s->longitude !== null)
                    <p class="text-xs font-mono text-tk-muted mt-1">{{ $s->latitude }}, {{ $s->longitude }}</p>
                    <x-tilkit.gps-map-view
                        :points="[[
                            'lat' => (float) $s->latitude,
                            'lng' => (float) $s->longitude,
                            'label' => $s->sighted_at->format('Y-m-d'),
                            'detail' => $s->place_text ?? 'Field sighting',
                            'kind' => 'sighting',
                        ]]"
                        height="h-40"
                        :legend="false"
                        class="mt-3"
                    />
                @endif
            </li>
        @endforeach
    </ul>
@endif
