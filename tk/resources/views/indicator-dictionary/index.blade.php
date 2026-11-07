@php
    $d = $dictionary;
@endphp

@guest
<x-layouts.site :title="$title" :meta-description="$metaDescription" :canonical="route('dictionary')">
    <div class="max-w-[96rem] mx-auto -mx-2 sm:mx-0">
        <header class="mb-8">
            <p class="text-xs font-medium uppercase tracking-wide text-tk-muted">Reference</p>
            <h1 class="text-2xl font-bold text-tk-ink mt-1">Indicator dictionary</h1>
            <p class="text-sm text-tk-muted mt-2 max-w-2xl">v4 taxonomy, legend codes, workbook columns, and allowed behaviours.</p>
            <p class="mt-4">
                <a href="{{ route('user-guide') }}" class="tk-btn-secondary text-sm">User guide</a>
            </p>
        </header>
        @include('indicator-dictionary.content')
    </div>
</x-layouts.site>
@else
<x-app-layout :full-width="true">
    <x-slot name="header">
        <x-page-header
            eyebrow="Reference"
            title="Indicator dictionary"
            subtitle="v4 taxonomy, legend codes, workbook columns, and allowed behaviours — for setup, capture, and analysis."
        >
            <x-slot name="actions">
                <a href="{{ route('user-guide') }}" class="tk-btn-secondary text-sm">User guide</a>
                @if ($weatherTool ?? null)
                    <a href="{{ route('tools.tilkit.setup', $weatherTool) }}" class="tk-btn-primary text-sm">Open setup</a>
                @endif
            </x-slot>
        </x-page-header>
    </x-slot>
    @include('indicator-dictionary.content')
</x-app-layout>
@endguest
