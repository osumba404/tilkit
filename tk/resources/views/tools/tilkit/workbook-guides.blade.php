<x-app-layout>
    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        @foreach ($sheets as $sheet)
            <div class="tk-card p-5">
                <h3 class="font-semibold text-sm">{{ $sheet['id'] }}</h3>
                <p class="text-xs text-tk-muted mt-2">{{ $sheet['title'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="tk-card p-6">
        <h3 class="tk-section-title mb-4">Community Data List - capture checklist</h3>
        <p class="text-sm text-tk-muted mb-6">Mirrors the <strong>Community Data List</strong> sheet: what to capture, when, and which Indicator Log columns it feeds.</p>
        @foreach ($communitySections as $section => $items)
            <x-tilkit.details-panel class="mb-4" :open="str_starts_with($section, 'A.')">
                <x-slot:summary>{{ $section }}</x-slot:summary>
                <ol class="space-y-2 px-4 pb-4 pt-3 text-sm list-decimal ps-5">
                    @foreach ($items as $item)
                        <li>
                            <span class="font-mono text-tk-accent text-xs">{{ $item['columns'] }}</span>
                            {{ $item['what'] }}
                            <span class="text-tk-muted text-xs block">Who: {{ $item['who'] }} · When: {{ $item['when'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </x-tilkit.details-panel>
        @endforeach
    </div>
</x-app-layout>
