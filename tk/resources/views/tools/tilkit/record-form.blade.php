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

    <p class="text-sm text-tk-muted mb-4">
        Capture follows the v4 <a href="{{ route('tools.tilkit.workbook', $tool) }}" class="text-tk-accent underline">Community Data List</a>
        (sections A–E) and <strong>Sheet 1 - Indicator Log</strong> columns A–DT.
    </p>

    @if ($isEdit)
        <p class="mb-4">
            <a href="{{ route('tools.tilkit.records.wizard', [$tool, $record, 'A']) }}" class="tk-btn-primary text-sm">Workshop wizard (A–E)</a>
        </p>
        <div class="tk-card p-5 space-y-4 mb-6">
            <h3 class="tk-section-title text-base">Field sighting</h3>
            @include('tools.tilkit.partials.sighting-form', ['tool' => $tool, 'record' => $record])
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
                            <option value="{{ $indicator->id }}" @selected(old('indicator_id') == $indicator->id)>{{ $indicator->indicator_uid }} - {{ $indicator->local_name }}</option>
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
            <details class="tk-details-panel mb-3" @if($loop->first) open @endif>
                <summary>
                    <span class="min-w-0 flex-1 pr-4">{{ $stage }}</span>
                    <span class="tk-details-panel-count">{{ count($columns) }} fields</span>
                </summary>
                <div class="p-5 pt-4 grid md:grid-cols-2 gap-4 animate-tk-fade-in">
                    @foreach ($columns as $col)
                        @php
                            if (\App\Support\Tilkit\WorkbookInputSpec::skipInForm($col)) {
                                continue;
                            }
                            $letter = $col['column'];
                            $current = old("workbook.{$letter}", $workbookValues[$letter] ?? '');
                        @endphp
                        <x-tilkit.workbook-field :col="$col" :current="$current" />
                    @endforeach
                </div>
            </details>
        @endforeach

        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-2">Photos (column Y - at least three recommended)</h3>
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
                            $shown = $derivedMap[$letter]['value'] ?? '-';
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
