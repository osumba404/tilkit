<x-app-layout :full-width="true">
    <x-slot name="header">
        @include('platform.datasets.partials.header')
    </x-slot>

    <x-analytics.acc-shell context="dataset" :acc-section="$accSection" :dataset="$dataset" :wide="true" title="Data explorer" subtitle="Paginated rows with dashboard filters applied">
        @include('analytics.partials.dataset-chart-builder', [
            'filters' => $filters,
            'dataset' => $dataset,
            'filterAction' => route('platform.datasets.explorer', $dataset),
        ])

        <div class="tk-card overflow-x-auto">
            <table class="w-full text-sm min-w-[40rem]">
                <thead class="bg-tk-soft text-left">
                    <tr>
                        @foreach ($headers as $header)
                            <th class="p-3 font-mono text-xs">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-tk-line">
                            @foreach ($headers as $header)
                                <td class="p-3 max-w-[14rem] truncate" title="{{ $row[$header] ?? '' }}">{{ $row[$header] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ max(count($headers), 1) }}" class="p-8 text-center text-tk-muted">No rows match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
    </x-analytics.acc-shell>
</x-app-layout>
