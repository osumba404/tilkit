<x-app-layout>
    @include('tools.tilkit.partials.subnav')

    <div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-8 tk-stagger">
        <div class="tk-stat">
            <p class="tk-stat-label">Workbook rows</p>
            <p class="tk-stat-value">{{ number_format($summary['total_records'] ?? 0) }}</p>
        </div>
        <div class="tk-stat">
            <p class="tk-stat-label">Sites</p>
            <p class="tk-stat-value">{{ $tool->tilkit_sites_count }}</p>
        </div>
        <div class="tk-stat">
            <p class="tk-stat-label">Seasons</p>
            <p class="tk-stat-value">{{ $tool->tilkit_seasons_count }}</p>
        </div>
        <div class="tk-stat">
            <p class="tk-stat-label">Indicators</p>
            <p class="tk-stat-value">{{ $tool->tilkit_indicators_count }}</p>
        </div>
        <div class="tk-stat">
            <p class="tk-stat-label">Approved</p>
            <p class="tk-stat-value">{{ number_format($summary['approved'] ?? 0) }}</p>
        </div>
        <div class="tk-stat">
            <p class="tk-stat-label">Sightings</p>
            <p class="tk-stat-value">{{ number_format($summary['field_sightings'] ?? 0) }}</p>
        </div>
    </div>

    @if (($summary['total_records'] ?? 0) > 0)
        <div class="tk-card p-6 mb-8 animate-tk-fade-in-up">
            <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
                <div>
                    <h3 class="tk-section-title">Dataset at a glance</h3>
                    <p class="text-sm text-tk-muted mt-1">Workbook capture across sites and seasons - open Analysis for charts and validation breakdown.</p>
                </div>
                <a href="{{ route('tools.tilkit.insights', $tool) }}" class="tk-btn-primary text-sm">Open analysis hub</a>
            </div>
            <x-tilkit.gps-map-view :points="$mapPoints" height="h-64" class="mb-6" />
            <div class="grid md:grid-cols-2 gap-6">
                <ul class="space-y-2">
                    @foreach (collect($summary['by_site'] ?? [])->take(5) as $row)
                        <x-tilkit.insight-bar
                            :label="$row['site']"
                            :count="$row['count']"
                            :max="$summary['site_max'] ?? 1"
                        />
                    @endforeach
                </ul>
                <ul class="space-y-2">
                    @foreach (collect($summary['by_season'] ?? [])->take(5) as $row)
                        <x-tilkit.insight-bar
                            :label="$row['season']"
                            :count="$row['count']"
                            :max="$summary['season_max'] ?? 1"
                        />
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (\App\Support\Tilkit\WeatherTool::singleToolMode())
        <div class="tk-callout mb-8 animate-tk-fade-in-up">
            One shared weather log for the platform. Use
            <a href="{{ route('tools.tilkit.setup', $tool) }}" class="text-tk-accent font-medium underline underline-offset-2">Setup</a>
            to add sites and seasons per community or study-not a separate tool per location.
        </div>
    @endif

    <div class="tk-action-grid tk-stagger">
        <a href="{{ route('tools.tilkit.records.create', $tool) }}" class="tk-action-tile">
            <span class="tk-action-tile-title">New indicator record</span>
            <span class="tk-action-tile-desc">Workshop capture and v4 workbook fields</span>
        </a>
        <a href="{{ route('tools.tilkit.records.index', $tool) }}" class="tk-action-tile">
            <span class="tk-action-tile-title">Browse records</span>
            <span class="tk-action-tile-desc">Filter by site, season, and indicator ID</span>
        </a>
        <a href="{{ route('tools.tilkit.tally', $tool) }}" class="tk-action-tile">
            <span class="tk-action-tile-title">Tercile tally</span>
            <span class="tk-action-tile-desc">Simple, weighted, and priority-adjusted forecasts</span>
        </a>
        <a href="{{ route('tools.tilkit.data-desk', $tool) }}" class="tk-action-tile">
            <span class="tk-action-tile-title">Data desk</span>
            <span class="tk-action-tile-desc">Validation queues and climate reviews</span>
        </a>
        <a href="{{ route('tools.tilkit.insights', $tool) }}" class="tk-action-tile">
            <span class="tk-action-tile-title">Analysis hub</span>
            <span class="tk-action-tile-desc">Counts, calendar, and season settings</span>
        </a>
        <a href="{{ route('tools.tilkit.workbook', $tool) }}" class="tk-action-tile">
            <span class="tk-action-tile-title">12-sheet guide</span>
            <span class="tk-action-tile-desc">Community Data List and workbook reference</span>
        </a>
    </div>

    <div class="flex flex-wrap gap-2 mb-8">
        <a href="{{ route('tools.tilkit.settings', $tool) }}" class="tk-btn-secondary text-xs">Season settings</a>
        <a href="{{ route('tools.tilkit.export.csv', $tool) }}" class="tk-btn-secondary text-xs">Export CSV</a>
        <a href="{{ route('tools.tilkit.export.json', $tool) }}" class="tk-btn-secondary text-xs">Export JSON</a>
        <a href="{{ route('tools.tilkit.export.xlsx', $tool) }}" class="tk-btn-secondary text-xs">Export xlsx</a>
        <a href="{{ route('tools.tilkit.calendar', $tool) }}" class="tk-btn-secondary text-xs">Calendar</a>
    </div>

    <div class="tk-card p-6 space-y-4 mb-6">
        <h3 class="tk-section-title">Workbook capture &amp; engine</h3>
        <p class="text-sm text-tk-muted">
            All <strong class="text-tk-ink">{{ $rawFieldCount }}</strong> raw columns are stored in
            <code class="text-xs">tilkit_workbook_values</code>. On each save the server recalculates derived fields (CE, weights, CG, DS, …) and supports
            simple / weighted / priority-adjusted tallies with reconciliation. Use the <strong>Community Data List wizard</strong> (sections A–E) on each record for workshop capture.
            Exports omit rows without consent (BC ≥ 2) and do-no-harm (BB) clearance. Publish snapshots and consensus (BF) from the tally screen before sharing forecasts externally.
        </p>
    </div>

    @if ($records->isNotEmpty())
        <div class="flex justify-between items-center mb-3">
            <h3 class="tk-section-title">Recent records</h3>
            <a href="{{ route('tools.tilkit.records.index', $tool) }}" class="text-sm text-tk-accent">View all</a>
        </div>
        <div class="tk-card overflow-hidden animate-tk-scale-in">
            <table class="w-full text-sm tk-table-hover">
                <thead class="bg-tk-soft text-left text-xs uppercase tracking-tk-label text-tk-muted">
                    <tr>
                        <th class="p-3">Indicator</th>
                        <th class="p-3">Site</th>
                        <th class="p-3">Season</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $rec)
                        <tr class="border-t border-tk-line">
                            <td class="p-3 font-mono">{{ $rec->indicator?->indicator_uid }}</td>
                            <td class="p-3">{{ $rec->site?->name }}</td>
                            <td class="p-3">{{ $rec->season?->label }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('tools.tilkit.records.edit', [$tool, $rec]) }}" class="text-tk-accent">Edit workbook</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-app-layout>
