<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="'TILKIT weather — '.$tool->name"
            subtitle="Community indicator monitoring aligned to workbook v4 (protocol {{ $protocol }})">
            <x-slot:actions>
                <a href="{{ route('tools.show', $tool) }}" class="tk-btn-secondary">Back to tool</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="grid md:grid-cols-3 gap-4 mb-6">
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
    </div>

    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('tools.tilkit.records.create', $tool) }}" class="tk-btn-primary">New indicator record</a>
        <a href="{{ route('tools.tilkit.setup', $tool) }}" class="tk-btn-secondary">Setup</a>
        <a href="{{ route('tools.tilkit.tally', $tool) }}" class="tk-btn-secondary">Tercile tally</a>
        <a href="{{ route('tools.tilkit.data-desk', $tool) }}" class="tk-btn-secondary">Data desk</a>
        <a href="{{ route('tools.tilkit.settings', $tool) }}" class="tk-btn-secondary">Season settings</a>
        <a href="{{ route('tools.tilkit.export.csv', $tool) }}" class="tk-btn-secondary">Export CSV</a>
        <a href="{{ route('tools.tilkit.export.json', $tool) }}" class="tk-btn-secondary">Export JSON</a>
        <a href="{{ route('tools.tilkit.workbook', $tool) }}" class="tk-btn-secondary">12-sheet guide</a>
        <a href="{{ route('tools.tilkit.calendar', $tool) }}" class="tk-btn-secondary">Indicator calendar</a>
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
        <div class="tk-card overflow-hidden">
            <table class="w-full text-sm">
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
