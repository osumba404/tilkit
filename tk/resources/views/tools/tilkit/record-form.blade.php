@php
    $isEdit = $record !== null;
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="$isEdit ? 'Edit indicator record' : 'New indicator record'"
            :subtitle="$tool->name.' · v4 workbook capture ('.count(\App\Support\Tilkit\WorkbookColumnRegistry::rawColumns()).' raw fields)'">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">TILKIT home</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (session('status'))
        <div class="tk-card p-4 mb-4 text-sm text-tk-ink">{{ session('status') }}</div>
    @endif

    @if ($isEdit)
        <div class="tk-card p-5 space-y-4 mb-6">
            <h3 class="tk-section-title text-base">Field sighting (updates N, O, CI, Z, AA, AB)</h3>
            <form method="POST" action="{{ route('tools.tilkit.records.sightings.store', [$tool, $record]) }}" class="grid md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Date sighted</label>
                    <input type="date" name="sighted_at" required class="tk-input">
                </div>
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Place</label>
                    <input type="text" name="place_text" class="tk-input">
                </div>
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Landmark</label>
                    <input type="text" name="landmark" class="tk-input">
                </div>
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Observed by</label>
                    <input type="text" name="observed_by" class="tk-input">
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="tk-btn-secondary">Add sighting to this record</button>
                </div>
            </form>
            @if ($record->sightings->isNotEmpty())
                <ul class="text-xs text-tk-muted space-y-1">
                    @foreach ($record->sightings as $s)
                        <li>{{ $s->sighted_at->format('Y-m-d') }} — {{ $s->place_text ?? '—' }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('tools.tilkit.records.update', [$tool, $record]) : route('tools.tilkit.records.store', $tool) }}"
        enctype="multipart/form-data"
        class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PATCH')
        @endif

        @if (! $isEdit)
            <div class="tk-card p-5 grid md:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Site (AI)</label>
                    <select name="site_id" required class="tk-input">
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Season (AM)</label>
                    <select name="season_id" required class="tk-input">
                        @foreach ($seasons as $season)
                            <option value="{{ $season->id }}" @selected(old('season_id') == $season->id)>{{ $season->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Indicator (AH)</label>
                    <select name="indicator_id" required class="tk-input">
                        @foreach ($indicators as $indicator)
                            <option value="{{ $indicator->id }}" @selected(old('indicator_id') == $indicator->id)>{{ $indicator->indicator_uid }} — {{ $indicator->local_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <div class="tk-card p-4 text-sm flex flex-wrap gap-4">
                <span><strong>Site:</strong> {{ $record->site?->name }}</span>
                <span><strong>Season:</strong> {{ $record->season?->label }}</span>
                <span><strong>ID:</strong> {{ $record->indicator?->indicator_uid }}</span>
            </div>
        @endif

        @foreach ($stageGroups as $stage => $columns)
            <details class="tk-card p-5 group" @if($loop->first) open @endif>
                <summary class="cursor-pointer font-semibold text-tk-ink list-none flex justify-between">
                    <span>{{ $stage }}</span>
                    <span class="text-xs text-tk-muted font-normal">{{ count($columns) }} fields</span>
                </summary>
                <div class="mt-4 grid md:grid-cols-2 gap-4">
                    @foreach ($columns as $col)
                        @php
                            $letter = $col['column'];
                            $kind = \App\Support\Tilkit\WorkbookColumnRegistry::inputKind($col);
                            $current = old("workbook.{$letter}", $workbookValues[$letter] ?? '');
                            if ($kind === 'photos') {
                                continue;
                            }
                        @endphp
                        <div>
                            <label class="text-xs text-tk-muted block mb-1">
                                <span class="font-mono text-tk-accent">{{ $letter }}</span>
                                {{ $col['field'] }}
                            </label>
                            @if ($kind === 'yes_no')
                                <select name="workbook[{{ $letter }}]" class="tk-input">
                                    <option value="">—</option>
                                    <option value="1" @selected((string) $current === '1')>Yes (1)</option>
                                    <option value="0" @selected((string) $current === '0')>No (0)</option>
                                </select>
                            @elseif ($kind === 'rating')
                                <select name="workbook[{{ $letter }}]" class="tk-input">
                                    <option value="">—</option>
                                    @foreach ([3 => '3 High', 2 => '2 Medium', 1 => '1 Low', 0 => '0 Not sure'] as $v => $label)
                                        <option value="{{ $v }}" @selected((string) $current === (string) $v)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @elseif ($letter === 'AP')
                                <select name="workbook[{{ $letter }}]" class="tk-input">
                                    <option value="">—</option>
                                    @foreach (\App\Enums\Tilkit\ValidationStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected((string) $current === $status->value)>{{ str_replace('_', ' ', $status->value) }}</option>
                                    @endforeach
                                </select>
                            @elseif ($letter === 'U')
                                <select name="workbook[{{ $letter }}]" class="tk-input">
                                    <option value="">—</option>
                                    @foreach (\App\Enums\Tilkit\TercileForecast::cases() as $t)
                                        @if ($t->isForecast())
                                            <option value="{{ $t->value }}" @selected((string) $current === (string) $t->value)>{{ $t->label() }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            @elseif ($kind === 'date')
                                <input type="date" name="workbook[{{ $letter }}]" value="{{ $current }}" class="tk-input">
                            @elseif ($kind === 'json')
                                <textarea name="workbook[{{ $letter }}]" rows="2" class="tk-input font-mono text-xs" placeholder='{"crops":"+"}'>{{ is_array($current) ? json_encode($current) : $current }}</textarea>
                            @else
                                <input type="text" name="workbook[{{ $letter }}]" value="{{ is_scalar($current) ? $current : '' }}" class="tk-input">
                            @endif
                        </div>
                    @endforeach
                </div>
            </details>
        @endforeach

        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-2">Photos (column Y — at least three recommended)</h3>
            <input type="file" name="workbook_photos[]" accept="image/*" multiple class="tk-input">
            @if ($isEdit && $record->workbookMedia->isNotEmpty())
                <p class="text-xs text-tk-muted mt-2">{{ $record->workbookMedia->count() }} photo(s) stored.</p>
            @endif
        </div>

        @if ($isEdit && isset($derivedColumns) && $derivedColumns !== [])
            <details class="tk-card p-5">
                <summary class="cursor-pointer font-semibold text-tk-ink list-none">
                    Derived responses (read-only, {{ count($derivedColumns) }} columns)
                </summary>
                <p class="text-xs text-tk-muted mt-2 mb-4">Recalculated on save; not editable here.</p>
                <div class="grid md:grid-cols-2 gap-3 text-sm">
                    @foreach ($derivedColumns as $col)
                        @php
                            $letter = $col['column'];
                            $shown = $derivedMap[$letter]['value'] ?? '—';
                        @endphp
                        <div class="border border-tk-line rounded-lg p-3">
                            <span class="font-mono text-tk-accent text-xs">{{ $letter }}</span>
                            <div class="text-tk-muted text-xs">{{ $col['field'] }}</div>
                            <div class="mt-1 font-medium">{{ is_scalar($shown) ? $shown : json_encode($shown) }}</div>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif

        <div class="flex gap-3">
            <button type="submit" class="tk-btn-primary">{{ $isEdit ? 'Save workbook fields' : 'Create record' }}</button>
            <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">Cancel</a>
        </div>
    </form>
</x-app-layout>
