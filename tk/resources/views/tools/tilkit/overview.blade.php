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
    </div>

    <div class="tk-card p-6 space-y-4 mb-6">
        <h3 class="tk-section-title">Workbook capture</h3>
        <p class="text-sm text-tk-muted">
            All <strong class="text-tk-ink">{{ $rawFieldCount }}</strong> raw columns from the v4 Indicator Log (A–DT) are stored per record in
            <code class="text-xs">tilkit_workbook_values</code>, keyed by Excel column letter. Derived columns (34) are computed server-side in Phase 2.
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
