<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Data desk" :subtitle="$tool->name.' · validation queue'">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">TILKIT home</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="tk-card p-5 mb-6">
        <p class="text-sm text-tk-muted">{{ $pending->count() }} record(s) need confirm, approve, or contested review.</p>
    </div>

    <div class="tk-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-tk-soft text-xs uppercase text-tk-muted">
                <tr>
                    <th class="p-3 text-left">Indicator</th>
                    <th class="p-3 text-left">Site / season</th>
                    <th class="p-3 text-left">Tally status (CE)</th>
                    <th class="p-3 text-left">Flags</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $row)
                    @php $rec = $row['record']; @endphp
                    <tr class="border-t border-tk-line">
                        <td class="p-3 font-mono text-xs">{{ $rec->indicator?->indicator_uid }}</td>
                        <td class="p-3">{{ $rec->site?->name }} · {{ $rec->season?->label }}</td>
                        <td class="p-3">{{ $row['tally_status'] }}</td>
                        <td class="p-3 text-xs">
                            @if ($row['needs_confirm']) <span class="text-amber-700">confirm</span> @endif
                            @if ($row['needs_approve']) <span class="text-amber-700">approve</span> @endif
                            @if ($row['contested']) <span class="text-red-700">contested</span> @endif
                        </td>
                        <td class="p-3 text-right">
                            <a href="{{ route('tools.tilkit.records.edit', [$tool, $rec]) }}" class="text-tk-accent">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
