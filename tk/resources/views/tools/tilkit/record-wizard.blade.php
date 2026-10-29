<x-app-layout>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <p class="text-sm text-tk-muted">{{ $record->indicator?->indicator_uid }} · {{ $record->site?->name }}</p>
        <a href="{{ route('tools.tilkit.records.edit', [$tool, $record]) }}" class="tk-btn-secondary text-sm">Full grid</a>
    </div>

    <nav class="flex flex-wrap gap-2 mb-8 text-sm animate-tk-fade-in">
        @foreach ($sections as $s)
            <a href="{{ route('tools.tilkit.records.wizard', [$tool, $record, $s]) }}"
               class="tk-wizard-tab {{ $s === $section ? 'tk-wizard-tab-active' : 'tk-wizard-tab-inactive' }}">
                Section {{ $s }}
            </a>
        @endforeach
    </nav>

    @if ($section === 'B')
        <div class="tk-callout mb-6 text-sm">
            Column <span class="font-mono text-tk-accent">Y</span> (photos) is uploaded on the
            <a href="{{ route('tools.tilkit.records.edit', [$tool, $record]) }}" class="text-tk-accent underline">full workbook grid</a>
            - at least three images recommended per sign.
        </div>
        <div class="tk-card p-5 mb-6">
            <h3 class="font-semibold mb-3">Log a sighting (child event - does not create a new row)</h3>
            @include('tools.tilkit.partials.sighting-form', [
                'tool' => $tool,
                'record' => $record,
                'workbookValues' => $workbookValues,
            ])
        </div>
    @endif

    <div class="tk-card p-5 mb-6 bg-tk-soft/50">
        <h3 class="text-xs uppercase text-tk-muted mb-3">Checklist</h3>
        <ol class="list-decimal list-inside text-sm space-y-2 text-tk-muted">
            @foreach ($checklist as $item)
                <li><strong class="text-tk-ink">{{ $item['what'] ?? '' }}</strong> - cols {{ $item['columns'] ?? '' }}</li>
            @endforeach
        </ol>
    </div>

    <form method="POST" action="{{ route('tools.tilkit.records.wizard.update', [$tool, $record, $section]) }}" class="tk-card p-6 animate-tk-fade-in-up">
        @csrf
        @method('PATCH')
        <div class="grid md:grid-cols-2 gap-4">
        @foreach ($columns as $col)
            @continue(\App\Support\Tilkit\WorkbookInputSpec::skipInForm($col))
            @php
                $letter = $col['column'];
                $current = old("workbook.{$letter}", $workbookValues[$letter] ?? '');
            @endphp
            <x-tilkit.workbook-field
                :col="$col"
                :current="$current"
                :center-lat="$record->site?->latitude"
                :center-lng="$record->site?->longitude"
            />
        @endforeach
        </div>
        <div class="flex flex-wrap gap-3 pt-4">
            @php $idx = array_search($section, $sections, true); @endphp
            @if ($idx !== false && $idx < count($sections) - 1)
                <input type="hidden" name="next_section" value="{{ $sections[$idx + 1] }}">
                <button type="submit" class="tk-btn-primary">Save &amp; next section</button>
            @else
                <button type="submit" class="tk-btn-primary">Save &amp; finish</button>
            @endif
        </div>
    </form>
</x-app-layout>
