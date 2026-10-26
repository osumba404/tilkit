<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Analysis" subtitle="Collection volume, validation, and forecast signals from workbook rows">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.records.index', $tool) }}" class="tk-btn-secondary">Browse records</a>
                <a href="{{ route('tools.tilkit.tally', $tool) }}" class="tk-btn-primary">Run tally</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @include('tools.tilkit.partials.subnav')

    <div class="tk-card p-6 mb-8 animate-tk-fade-in-up">
        <h3 class="tk-section-title text-base mb-1">Collection map</h3>
        <p class="text-sm text-tk-muted mb-4">Workbook GPS (column Z), field sightings, and site anchors across the dataset.</p>
        <x-tilkit.gps-map-view :points="$mapPoints" height="h-[22rem]" />
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-8 tk-stagger">
        @foreach ([
            'total_records' => 'Workbook rows',
            'field_sightings' => 'Field sightings',
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
            <h3 class="tk-section-title text-base mb-4">Collection funnel</h3>
            <ul class="space-y-3">
                @foreach ($summary['collection_funnel'] ?? [] as $step)
                    <x-tilkit.insight-bar
                        :label="$step['label']"
                        :count="$step['count']"
                        :max="$summary['total_records'] ?? 1"
                    />
                @endforeach
            </ul>
        </div>
        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-4">Forecast tercile (column N)</h3>
            <ul class="space-y-3">
                @forelse ($summary['tercile_breakdown'] ?? [] as $row)
                    @php
                        $tercileMax = collect($summary['tercile_breakdown'] ?? [])->max('count') ?: 1;
                    @endphp
                    <x-tilkit.insight-bar :label="$row['label']" :count="$row['count']" :max="$tercileMax" />
                @empty
                    <li class="text-sm text-tk-muted">No forecast values yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8 mb-8">
        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-4">Records by site</h3>
            <ul class="space-y-3">
                @forelse ($summary['by_site'] as $row)
                    <x-tilkit.insight-bar
                        :label="$row['site']"
                        :count="$row['count']"
                        :max="$summary['site_max'] ?? 1"
                    />
                @empty
                    <li class="text-sm text-tk-muted">Add sites under Setup to organize data by location.</li>
                @endforelse
            </ul>
        </div>
        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-4">Records by season</h3>
            <ul class="space-y-3">
                @forelse ($summary['by_season'] as $row)
                    <x-tilkit.insight-bar
                        :label="$row['season']"
                        :count="$row['count']"
                        :max="$summary['season_max'] ?? 1"
                    />
                @empty
                    <li class="text-sm text-tk-muted">Add seasons under Setup for each monitoring period.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8 mb-8">
        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-4">Validation status (column AP)</h3>
            <ul class="space-y-3">
                @forelse ($summary['validation_breakdown'] ?? [] as $row)
                    @php
                        $validationMax = collect($summary['validation_breakdown'] ?? [])->max('count') ?: 1;
                    @endphp
                    <x-tilkit.insight-bar :label="$row['label']" :count="$row['count']" :max="$validationMax" />
                @empty
                    <li class="text-sm text-tk-muted">Validation labels appear after workshop capture.</li>
                @endforelse
            </ul>
        </div>
        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-4">Published tally snapshots</h3>
            <ul class="text-sm space-y-2 text-tk-muted">
                @forelse ($summary['recent_snapshots'] as $snap)
                    <li class="flex flex-wrap justify-between gap-2 border-b border-tk-line/60 pb-2">
                        <span>{{ $snap->site?->name }} · {{ $snap->season?->label }}</span>
                        <span class="text-tk-ink">{{ $snap->published_at?->format('Y-m-d H:i') ?? '-' }}</span>
                    </li>
                @empty
                    <li>Run tally with “publish snapshot” to store immutable forecasts for comparison.</li>
                @endforelse
            </ul>
            <p class="text-xs text-tk-muted mt-4">
                <a href="{{ route('tools.tilkit.tally', $tool) }}" class="text-tk-accent underline">Open tally</a>
                to reconcile simple, weighted, and priority-adjusted shares.
            </p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-3">Analysis tools</h3>
            <ul class="text-sm space-y-2">
                <li><a href="{{ route('tools.tilkit.tally', $tool) }}" class="text-tk-accent font-medium">Seasonal tercile tally</a> - simple, weighted, and priority-adjusted forecasts.</li>
                <li><a href="{{ route('tools.tilkit.data-desk', $tool) }}" class="text-tk-accent font-medium">Data desk</a> - validation queues and climate weight reviews.</li>
                <li><a href="{{ route('tools.tilkit.settings', $tool) }}" class="text-tk-accent font-medium">Season settings</a> - KMSA, NDMA, and weighting options per site × season.</li>
                <li><a href="{{ route('tools.tilkit.calendar', $tool) }}" class="text-tk-accent font-medium">Indicator calendar</a> - CI–CP timing and climate flags.</li>
                <li><a href="{{ route('tools.tilkit.deep-analysis', $tool) }}" class="text-tk-accent font-medium">Deep Analysis</a> - notebook EDA, charts, statistics, and time series on any columns.</li>
            </ul>
            @if ($capabilities->canExportData())
                <p class="text-xs text-tk-muted mt-4">Exports: CSV, JSON, and xlsx from the weather log home.</p>
            @endif
        </div>
        <div class="tk-card p-5">
            <h3 class="tk-section-title text-base mb-3">Recent workbook rows</h3>
            <ul class="text-sm space-y-2 text-tk-muted">
                @forelse ($recentRecords as $rec)
                    <li>
                        <a href="{{ route('tools.tilkit.records.edit', [$tool, $rec]) }}" class="text-tk-accent font-mono">{{ $rec->indicator?->indicator_uid }}</a>
                        · {{ $rec->site?->name }} · {{ $rec->season?->label }}
                        @if ($rec->tercile_forecast)
                            · <span class="text-tk-ink">{{ $rec->tercile_forecast->label() }}</span>
                        @endif
                    </li>
                @empty
                    <li>No workbook rows yet.</li>
                @endforelse
            </ul>
            <a href="{{ route('tools.tilkit.records.index', $tool) }}" class="inline-block mt-4 text-sm text-tk-accent">View all records</a>
        </div>
    </div>
</x-app-layout>
