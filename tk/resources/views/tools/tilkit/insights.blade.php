<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Analysis" subtitle="Workbook metrics, tallies, and validation status">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.tally', $tool) }}" class="tk-btn-primary">Run tally</a>
                <a href="{{ route('tools.tilkit.calendar', $tool) }}" class="tk-btn-secondary">Calendar</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @include('tools.tilkit.partials.subnav')

    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8 tk-stagger">
        @foreach ([
            'total_records' => 'Total records',
            'sighted' => 'Sighted',
            'confirmed' => 'Confirmed',
            'approved' => 'Approved',
            'contested' => 'Contested',
        ] as $key => $label)
            <div class="tk-stat">
                <p class="tk-stat-label">{{ $label }}</p>
                <p class="tk-stat-value">{{ number_format($summary[$key] ?? 0) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-8 mb-8">
        <div class="tk-card p-5">
            <h3 class="font-semibold mb-3">Records by site</h3>
            <ul class="text-sm space-y-1 text-tk-muted">
                @forelse ($summary['by_site'] as $row)
                    <li class="flex justify-between"><span>{{ $row['site'] }}</span><span class="font-mono text-tk-ink">{{ $row['count'] }}</span></li>
                @empty
                    <li>Add sites under Setup to organize data by location.</li>
                @endforelse
            </ul>
        </div>
        <div class="tk-card p-5">
            <h3 class="font-semibold mb-3">Records by season</h3>
            <ul class="text-sm space-y-1 text-tk-muted">
                @forelse ($summary['by_season'] as $row)
                    <li class="flex justify-between"><span>{{ $row['season'] }}</span><span class="font-mono text-tk-ink">{{ $row['count'] }}</span></li>
                @empty
                    <li>Add seasons under Setup for each monitoring period.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        <div class="tk-card p-5">
            <h3 class="font-semibold mb-3">Analysis tools</h3>
            <ul class="text-sm space-y-2">
                <li><a href="{{ route('tools.tilkit.tally', $tool) }}" class="text-tk-accent font-medium">Seasonal tercile tally</a> - simple, weighted, and priority-adjusted forecasts.</li>
                <li><a href="{{ route('tools.tilkit.data-desk', $tool) }}" class="text-tk-accent font-medium">Data desk</a> - validation queues and climate weight reviews.</li>
                <li><a href="{{ route('tools.tilkit.settings', $tool) }}" class="text-tk-accent font-medium">Season settings</a> - KMSA, NDMA, and weighting options per site × season.</li>
                <li><a href="{{ route('tools.tilkit.calendar', $tool) }}" class="text-tk-accent font-medium">Indicator calendar</a> - CI–CP timing and climate flags.</li>
            </ul>
            @if ($capabilities->canExportData())
                <p class="text-xs text-tk-muted mt-4">Exports: CSV, JSON, and xlsx from the weather log home.</p>
            @endif
        </div>
        <div class="tk-card p-5">
            <h3 class="font-semibold mb-3">Recent activity</h3>
            <ul class="text-sm space-y-2 text-tk-muted">
                @forelse ($recentRecords as $rec)
                    <li>
                        <a href="{{ route('tools.tilkit.records.edit', [$tool, $rec]) }}" class="text-tk-accent font-mono">{{ $rec->indicator?->indicator_uid }}</a>
                        · {{ $rec->site?->name }} · {{ $rec->season?->label }}
                    </li>
                @empty
                    <li>No workbook rows yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
