<x-app-layout>
    <x-slot name="header">
        <x-page-header title="v4 workbook (12 sheets)" :subtitle="$tool->name">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">TILKIT home</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

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
            <details class="mb-4 border border-tk-line rounded-lg p-4" @if(str_starts_with($section, 'A.')) open @endif>
                <summary class="cursor-pointer font-medium">{{ $section }}</summary>
                <ol class="mt-3 space-y-2 text-sm list-decimal ps-5">
                    @foreach ($items as $item)
                        <li>
                            <span class="font-mono text-tk-accent text-xs">{{ $item['columns'] }}</span>
                            {{ $item['what'] }}
                            <span class="text-tk-muted text-xs block">Who: {{ $item['who'] }} · When: {{ $item['when'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </details>
        @endforeach
    </div>
</x-app-layout>
