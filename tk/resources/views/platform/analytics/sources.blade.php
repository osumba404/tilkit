<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Data sources" eyebrow="Platform" />
        <p class="text-sm text-tk-muted">Platform integration health</p>
    </x-slot>

    <x-analytics.acc-shell context="platform" :acc-section="'sources'" title="Data source management" subtitle="Connection status and freshness (MVP: Tilkit-native sources)">
        @include('analytics.partials.freshness-panel', ['sources' => $freshness])

    </x-analytics.acc-shell>
</x-app-layout>
