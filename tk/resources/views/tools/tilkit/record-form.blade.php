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

    @include('tools.tilkit.partials.subnav', ['tilkitNav' => $tilkitNav ?? 'capture'])

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
            @include('tools.tilkit.partials.sighting-form', [
                'tool' => $tool,
                'record' => $record,
                'workbookValues' => $workbookValues,
            ])
        </div>
    @endif

    @if (! $isEdit)
        <div class="tk-card p-5 space-y-4 mb-6">
            <h3 class="tk-section-title text-base">Program / initiative</h3>
            <p class="text-xs text-tk-muted">Choose where you are entering data (for example East Africa 2016–2021 or Ghana pilot 2019–2026). Sites and seasons below belong to this program.</p>
            <form method="GET" action="{{ route('tools.tilkit.records.create', $tool) }}" class="max-w-xl">
                <select name="program_id" class="tk-input" aria-label="Program or initiative" onchange="this.form.submit()">
                    @foreach ($tilkitPrograms ?? [] as $program)
                        <option value="{{ $program->id }}" @selected(($activeProgram->id ?? null) === $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
            </form>
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
            <div class="tk-card p-5 grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Site (AI)</label>
                    <select name="site_id" required class="tk-input">
                        @forelse ($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->name }}</option>
                        @empty
                            <option value="" disabled>No sites — add under Setup for this program</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Monitoring season — months (AM)</label>
                    <select name="season_id" required class="tk-input">
                        @forelse ($seasons as $season)
                            <option value="{{ $season->id }}" @selected(old('season_id') == $season->id)>{{ $season->monthRangeLabel() }}</option>
                        @empty
                            <option value="" disabled>No seasons — add month ranges under Setup</option>
                        @endforelse
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
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Date data collected (AN)</label>
                    <input type="date" name="collection_date" value="{{ old('collection_date', now()->toDateString()) }}" required class="tk-input">
                </div>
                <div>
                    <label class="text-xs text-tk-muted block mb-1">Collection year (with season → AM)</label>
                    <input type="number" name="collection_year" value="{{ old('collection_year', now()->year) }}" required min="1990" max="2100" class="tk-input" placeholder="e.g. 2024">
                </div>
            </div>
        @else
            <div class="tk-card p-4 text-sm flex flex-wrap gap-4">
                <span><strong>Site:</strong> {{ $record->site?->name }}</span>
                <span><strong>Season:</strong> {{ $record->season?->monthRangeLabel() }}@if($record->collection_year) · {{ $record->collection_year }}@endif</span>
                @if ($record->collection_date)
                    <span><strong>Collected:</strong> {{ $record->collection_date->format('Y-m-d') }}</span>
                @endif
                <span><strong>ID:</strong> {{ $record->indicator?->indicator_uid }}</span>
            </div>
        @endif

        @foreach ($stageGroups as $stage => $columns)
            <x-tilkit.details-panel variant="card" :open="$loop->first">
                <x-slot:summary>
                    <span class="min-w-0 flex-1 pr-4">{{ $stage }}</span>
                    <span class="tk-details-panel-count">{{ count($columns) }} fields</span>
                </x-slot:summary>
                <div class="p-5 pt-4 grid md:grid-cols-2 gap-4">
                    @foreach ($columns as $col)
                        @php
                            if (\App\Support\Tilkit\WorkbookInputSpec::skipInForm($col)) {
                                continue;
                            }
                            $letter = $col['column'];
                            $current = old("workbook.{$letter}", $workbookValues[$letter] ?? '');
                        @endphp
                        <x-tilkit.workbook-field
                            :col="$col"
                            :current="$current"
                            :center-lat="$isEdit ? $record->site?->latitude : null"
                            :center-lng="$isEdit ? $record->site?->longitude : null"
                        />
                    @endforeach
                </div>
            </x-tilkit.details-panel>
        @endforeach

        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-2">Photos (column Y - at least three recommended)</h3>
            <input type="file" name="workbook_photos[]" accept="image/*" multiple class="tk-input">
            @if ($isEdit && $record->workbookMedia->isNotEmpty())
                <p class="text-xs text-tk-muted mt-2">{{ $record->workbookMedia->count() }} photo(s) stored.</p>
            @endif
        </div>

        @if ($isEdit && isset($derivedColumns) && $derivedColumns !== [])
            <x-tilkit.details-panel variant="card">
                <x-slot:summary>
                    <span class="flex-1">Derived responses (read-only)</span>
                    <span class="tk-details-panel-count">{{ count($derivedColumns) }} columns</span>
                </x-slot:summary>
                <p class="text-xs text-tk-muted mb-4 px-5 pt-4">Recalculated on save; not editable here.</p>
                <div class="grid md:grid-cols-2 gap-3 text-sm px-5 pb-5">
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
            </x-tilkit.details-panel>
        @endif

        <div class="flex gap-3">
            <button type="submit" class="tk-btn-primary">{{ $isEdit ? 'Save workbook fields' : 'Create record' }}</button>
            <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">Cancel</a>
        </div>
    </form>
</x-app-layout>
