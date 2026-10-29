@php
    $isEdit = $record !== null;
@endphp
<x-app-layout>
    @include('tools.tilkit.partials.subnav', ['tilkitNav' => $tilkitNav ?? 'capture', 'showProgramSwitcher' => false])

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
            <p class="text-xs text-tk-muted">Choose where you are entering data. Sites and seasons below belong to this program.</p>
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
        class="space-y-6"
        @if (! $isEdit) data-tk-form-draft="tilkit:record-create:{{ $tool->id }}" @endif
        @if ($isEdit) data-tk-form-draft="tilkit:record-edit:{{ $record->id }}" @endif
    >
        @csrf
        @if ($isEdit)
            @method('PATCH')
        @endif

        @if (! $isEdit)
            <div class="tk-card p-5 grid md:grid-cols-2 lg:grid-cols-3 gap-4" data-tk-site-season-form data-tk-record-create-form>
                <div>
                    <label class="text-sm text-tk-ink block mb-1 font-medium">{{ \App\Support\Tilkit\WorkbookFormLabel::captionForLetter('AI') }}</label>
                    <select name="site_id" required class="tk-input" data-tk-site-select>
                        @forelse ($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->name }}</option>
                        @empty
                            <option value="" disabled>No sites - add under Setup for this program</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="text-sm text-tk-ink block mb-1 font-medium">{{ \App\Support\Tilkit\WorkbookFormLabel::captionForLetter('AM') }}</label>
                    <p class="text-xs text-tk-muted mb-2">Seasons belong to the selected site (configure under Setup).</p>
                    <select name="season_id" required class="tk-input" data-tk-season-select>
                        @forelse ($seasons as $season)
                            <option value="{{ $season->id }}" data-site-id="{{ $season->site_id ?? '' }}" @selected(old('season_id') == $season->id)>{{ $season->monthRangeLabel() }}</option>
                        @empty
                            <option value="" disabled>No seasons - add under Setup for each site</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="text-sm text-tk-ink block mb-1 font-medium">{{ \App\Support\Tilkit\WorkbookFormLabel::captionForLetter('AN') }}</label>
                    <input type="date" name="collection_date" value="{{ old('collection_date', now()->toDateString()) }}" required class="tk-input" data-tk-collection-date>
                </div>
                <div>
                    <label class="text-sm text-tk-ink block mb-1 font-medium">{{ \App\Support\Tilkit\WorkbookFormLabel::captionForLetter('AO') }}</label>
                    <p class="text-xs text-tk-muted mb-2">Calendar year for this row, together with the season months above.</p>
                    <input type="number" name="collection_year" value="{{ old('collection_year', now()->year) }}" required min="1990" max="2100" class="tk-input" placeholder="e.g. 2024">
                </div>
                @include('tools.tilkit.partials.record-indicator-cascade')
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

        @php
            $letterBands = $letterBands ?? [];
            $bandKeys = array_keys($letterBands);
            $initialBand = strtoupper((string) request('band', $bandKeys[0] ?? 'A'));
            $initialStep = array_search($initialBand, $bandKeys, true);
            if ($initialStep === false) {
                $initialStep = 0;
            }
        @endphp

        <div
            class="space-y-4"
            x-data="{
                step: {{ (int) $initialStep }},
                keys: @js($bandKeys),
                bandLabel(key) { return 'Columns ' + key; },
                goTo(i) { if (i >= 0 && i < this.keys.length) { this.step = i; window.scrollTo({ top: 0, behavior: 'smooth' }); } },
            }"
        >
            <nav class="flex flex-wrap gap-2 text-sm animate-tk-fade-in" aria-label="Workbook column bands">
                @foreach ($bandKeys as $i => $bandKey)
                    <button
                        type="button"
                        class="tk-wizard-tab"
                        :class="step === {{ $i }} ? 'tk-wizard-tab-active' : 'tk-wizard-tab-inactive'"
                        @click="goTo({{ $i }})"
                    >
                        {{ $bandKey }}
                    </button>
                @endforeach
            </nav>

            @foreach ($letterBands as $bandKey => $columns)
                @php
                    $summary = \App\Support\Tilkit\WorkbookColumnRegistry::columnBandSummary($bandKey, $columns);
                    $fieldCount = count(array_filter($columns, fn ($col) => ! \App\Support\Tilkit\WorkbookInputSpec::skipInForm($col)));
                @endphp
                <div
                    class="tk-card p-5 animate-tk-fade-in-up"
                    x-show="step === {{ $loop->index }}"
                    x-cloak
                >
                    <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                        <h3 class="tk-section-title text-base mb-0">Columns {{ $bandKey }}</h3>
                        <span class="text-xs text-tk-muted font-mono">{{ $summary }} · {{ $fieldCount }} fields</span>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach ($columns as $col)
                            @continue(\App\Support\Tilkit\WorkbookInputSpec::skipInForm($col))
                            @if (! $isEdit && in_array($col['column'], ['A', 'B', 'C', 'D'], true))
                                @continue
                            @endif
                            @php
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
                </div>
            @endforeach

            <div class="tk-card p-5" x-show="step === keys.length - 1" x-cloak>
                <h3 class="tk-section-title text-base mb-2">{{ \App\Support\Tilkit\WorkbookFormLabel::captionForLetter('Y') }}</h3>
                <p class="text-xs text-tk-muted mb-3">Upload at least three clear photos when possible.</p>
                <input type="file" name="workbook_photos[]" accept="image/*" multiple class="tk-input">
                @if ($isEdit && $record->workbookMedia->isNotEmpty())
                    <p class="text-xs text-tk-muted mt-2">{{ $record->workbookMedia->count() }} photo(s) stored.</p>
                @endif
            </div>

            @if ($isEdit && isset($derivedColumns) && $derivedColumns !== [])
                <x-tilkit.details-panel variant="card" x-show="step === keys.length - 1" x-cloak>
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

            <div class="flex flex-wrap gap-3 pt-2">
                <button
                    type="button"
                    class="tk-btn-secondary"
                    x-show="step > 0"
                    @click="goTo(step - 1)"
                >
                    Previous
                </button>
                <button
                    type="button"
                    class="tk-btn-primary"
                    x-show="step < keys.length - 1"
                    @click="goTo(step + 1)"
                >
                    Next
                </button>
                <button
                    type="submit"
                    class="tk-btn-primary"
                    x-show="step === keys.length - 1"
                    x-cloak
                >
                    {{ $isEdit ? 'Save workbook fields' : 'Create record' }}
                </button>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</x-app-layout>
