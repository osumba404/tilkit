<form method="POST" action="{{ route('tools.tilkit.records.sightings.store', [$tool, $record]) }}" class="grid md:grid-cols-2 gap-4 tk-stagger">
    @csrf
    <div>
        <label class="text-xs text-tk-muted block mb-1">Date sighted (CI)</label>
        <input type="date" name="sighted_at" required class="tk-input">
    </div>
    <div class="md:col-span-2">
        <label class="text-xs text-tk-muted block mb-1">Where and when sighted (O)</label>
        <textarea name="place_text" rows="2" class="tk-input" placeholder="Place description and context"></textarea>
    </div>
    <div>
        <label class="text-xs text-tk-muted block mb-1">GPS latitude (Z)</label>
        <input type="number" step="any" name="latitude" class="tk-input" placeholder="-1.234567">
    </div>
    <div>
        <label class="text-xs text-tk-muted block mb-1">GPS longitude (Z)</label>
        <input type="number" step="any" name="longitude" class="tk-input" placeholder="36.789012">
    </div>
    <div>
        <label class="text-xs text-tk-muted block mb-1">Landmark (AA)</label>
        <input type="text" name="landmark" class="tk-input" placeholder="Landmark near GPS">
    </div>
    <div>
        <label class="text-xs text-tk-muted block mb-1">Observed by (AB)</label>
        <input type="text" name="observed_by" class="tk-input" placeholder="Observer name or group">
    </div>
    <div class="md:col-span-2">
        <button type="submit" class="tk-btn-secondary">Add sighting</button>
    </div>
</form>
@if ($record->sightings->isNotEmpty())
    <ul class="mt-4 text-xs text-tk-muted space-y-1">
        @foreach ($record->sightings as $s)
            <li>{{ $s->sighted_at->format('Y-m-d') }} - {{ $s->place_text ?? '-' }} ({{ $s->uuid }})</li>
        @endforeach
    </ul>
@endif
