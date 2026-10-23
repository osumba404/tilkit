<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Community Data List · Section {{ $section }}" :subtitle="$record->indicator?->indicator_uid.' · '.$record->site?->name">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.records.edit', [$tool, $record]) }}" class="tk-btn-secondary">Full grid</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (session('status'))
        <div class="tk-card p-4 mb-4 text-sm">{{ session('status') }}</div>
    @endif

    <nav class="flex flex-wrap gap-2 mb-6 text-sm">
        @foreach ($sections as $s)
            <a href="{{ route('tools.tilkit.records.wizard', [$tool, $record, $s]) }}"
               class="px-3 py-1 rounded-full border {{ $s === $section ? 'bg-tk-accent text-white border-tk-accent' : 'border-tk-line text-tk-muted' }}">
                {{ $s }}
            </a>
        @endforeach
    </nav>

    @if ($section === 'B')
        <div class="tk-card p-5 mb-6">
            <h3 class="font-semibold mb-3">Log a sighting (child event — does not create a new row)</h3>
            @include('tools.tilkit.partials.sighting-form', ['tool' => $tool, 'record' => $record])
        </div>
    @endif

    <div class="tk-card p-5 mb-6 bg-tk-soft/50">
        <h3 class="text-xs uppercase text-tk-muted mb-3">Checklist</h3>
        <ol class="list-decimal list-inside text-sm space-y-2 text-tk-muted">
            @foreach ($checklist as $item)
                <li><strong class="text-tk-ink">{{ $item['what'] ?? '' }}</strong> — cols {{ $item['columns'] ?? '' }}</li>
            @endforeach
        </ol>
    </div>

    <form method="POST" action="{{ route('tools.tilkit.records.wizard.update', [$tool, $record, $section]) }}" class="tk-card p-6 space-y-4">
        @csrf
        @method('PATCH')
        @foreach ($columns as $col)
            @php
                $letter = $col['column'];
                $current = old("workbook.{$letter}", $workbookValues[$letter] ?? '');
            @endphp
            <div>
                <label class="text-xs text-tk-muted block mb-1">
                    <span class="font-mono">{{ $letter }}</span> — {{ $col['field'] ?? '' }}
                </label>
                @php $kind = \App\Support\Tilkit\WorkbookColumnRegistry::inputKind($col); @endphp
                @if ($kind === 'yes_no')
                    <select name="workbook[{{ $letter }}]" class="tk-input">
                        <option value="">—</option>
                        <option value="1" @selected((string) $current === '1')>Yes</option>
                        <option value="0" @selected((string) $current === '0')>No</option>
                    </select>
                @elseif ($kind === 'rating')
                    <select name="workbook[{{ $letter }}]" class="tk-input">
                        @foreach ([3 => 'High', 2 => 'Medium', 1 => 'Low', 0 => 'Not sure'] as $v => $label)
                            <option value="{{ $v }}" @selected((string) $current === (string) $v)>{{ $label }}</option>
                        @endforeach
                    </select>
                @elseif ($kind === 'date')
                    <input type="date" name="workbook[{{ $letter }}]" value="{{ $current }}" class="tk-input">
                @else
                    <input type="text" name="workbook[{{ $letter }}]" value="{{ $current }}" class="tk-input">
                @endif
            </div>
        @endforeach
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
