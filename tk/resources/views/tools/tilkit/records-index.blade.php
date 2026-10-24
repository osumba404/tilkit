<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Indicator records" :subtitle="$tool->name">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.records.create', $tool) }}" class="tk-btn-primary">New record</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @include('tools.tilkit.partials.subnav')

    <form method="GET" class="tk-card p-4 mb-6 flex flex-wrap gap-3 items-end text-sm">
        <div>
            <label class="text-xs text-tk-muted">Site</label>
            <select name="site_id" class="rounded-md border border-tk-line px-2 py-1.5">
                <option value="">All sites</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}" @selected(request('site_id') == $site->id)>{{ $site->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-tk-muted">Season</label>
            <select name="season_id" class="rounded-md border border-tk-line px-2 py-1.5">
                <option value="">All seasons</option>
                @foreach ($seasons as $season)
                    <option value="{{ $season->id }}" @selected(request('season_id') == $season->id)>{{ $season->label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[12rem]">
            <label class="text-xs text-tk-muted">Indicator ID</label>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="e.g. 01-ANI-FRO" class="w-full rounded-md border border-tk-line px-2 py-1.5">
        </div>
        <button type="submit" class="tk-btn-secondary">Filter</button>
    </form>

    <div class="tk-card overflow-hidden animate-tk-scale-in">
        <table class="w-full text-sm tk-table-hover">
            <thead class="bg-tk-soft text-left text-xs uppercase tracking-tk-label text-tk-muted">
                <tr>
                    <th class="p-3">Indicator</th>
                    <th class="p-3">Site</th>
                    <th class="p-3">Season</th>
                    <th class="p-3">Sighted</th>
                    <th class="p-3">Tercile</th>
                    <th class="p-3">Validation</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $rec)
                    <tr class="border-t border-tk-line">
                        <td class="p-3 font-mono">{{ $rec->indicator?->indicator_uid }}</td>
                        <td class="p-3">{{ $rec->site?->name }}</td>
                        <td class="p-3">{{ $rec->season?->label }}</td>
                        <td class="p-3">{{ $rec->sighted ? 'Yes' : 'No' }}</td>
                        <td class="p-3">{{ $rec->tercile_forecast?->label() ?? '-' }}</td>
                        <td class="p-3">{{ $rec->validation_status?->excelLabel() ?? '-' }}</td>
                        <td class="p-3 text-right space-x-2">
                            <a href="{{ route('tools.tilkit.records.wizard', [$tool, $rec, 'A']) }}" class="text-tk-accent">Wizard</a>
                            <a href="{{ route('tools.tilkit.records.edit', [$tool, $rec]) }}" class="text-tk-accent">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-tk-muted">No records yet. <a href="{{ route('tools.tilkit.records.create', $tool) }}" class="text-tk-accent underline">Capture the first row</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</x-app-layout>
