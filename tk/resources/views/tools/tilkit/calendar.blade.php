<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Seasonal Indicator Calendar" :subtitle="$tool->name.' · CI–CP'">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">TILKIT home</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="tk-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-tk-soft text-xs uppercase text-tk-muted">
                <tr>
                    <th class="p-3 text-left">Indicator</th>
                    <th class="p-3 text-left">Site / season</th>
                    <th class="p-3 text-left">Date sighted (CI)</th>
                    <th class="p-3 text-left">Local period (CK)</th>
                    <th class="p-3 text-left">Expected month (CL)</th>
                    <th class="p-3 text-left">Shift (CM)</th>
                    <th class="p-3 text-left">Change (CN/CO)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $row)
                    <tr class="border-t border-tk-line">
                        <td class="p-3 font-mono text-xs">{{ $row['record']->indicator?->indicator_uid }}</td>
                        <td class="p-3">{{ $row['record']->site?->name }} · {{ $row['record']->season?->label }}</td>
                        <td class="p-3">{{ $row['ci'] ?? '—' }}</td>
                        <td class="p-3">{{ $row['ck'] ?? '—' }}</td>
                        <td class="p-3">{{ $row['cl'] ?? '—' }}</td>
                        <td class="p-3">{{ $row['cm'] ?? '—' }}</td>
                        <td class="p-3 text-xs">{{ $row['cn'] ?? '—' }} @if($row['co']) / {{ $row['co'] }} @endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
