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
                    <td class="p-3">{{ $rec->season?->monthRangeLabel() }}@if($rec->collection_year) · {{ $rec->collection_year }}@endif</td>
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

<div class="mt-4" data-tilkit-records-pagination>{{ $records->links() }}</div>
