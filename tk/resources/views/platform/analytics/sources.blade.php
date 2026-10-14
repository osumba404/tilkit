<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Data sources" eyebrow="Platform" />
        <p class="text-sm text-tk-muted">Platform integration health</p>
    </x-slot>

    <x-analytics.acc-shell context="platform" :acc-section="'sources'" title="Data source management" subtitle="Connection status and freshness (MVP: Tilkit-native sources)">
        @include('analytics.partials.freshness-panel', ['sources' => $freshness])

        <div class="tk-card p-5 mt-6">
            <h3 class="font-semibold mb-2">Upload your own data</h3>
            <p class="text-sm text-tk-muted mb-3">CSV/Excel ingestion with type mapping and natural-language charts is available now.</p>
            <a href="{{ route('platform.datasets.index') }}" class="tk-btn-primary text-sm">Open dataset workspace</a>
        </div>
    </x-analytics.acc-shell>
</x-app-layout>
