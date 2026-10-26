@push('head')
    @vite('resources/css/user-guide.css')
@endpush

@guest
<x-layouts.site :title="$title" :meta-description="$metaDescription" :canonical="route('user-guide')">
    @include('user-guide.content')
</x-layouts.site>
@else
<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Documentation"
            title="User guide"
            subtitle="TILKIT Weather Indicator Log on Tilkit - setup, capture, validation, tally, and exports."
        >
            @if ($weatherTool ?? null)
                <x-slot name="actions">
                    <a href="{{ route('tools.tilkit.overview', $weatherTool) }}" class="tk-btn-primary">Open weather log</a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>
    @include('user-guide.content')
</x-app-layout>
@endguest
