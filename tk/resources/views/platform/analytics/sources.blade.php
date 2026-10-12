<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold">Data sources</h2>
        <p class="text-sm text-tk-muted">Platform integration health</p>
    </x-slot>

    <x-analytics.acc-shell context="platform" :acc-section="'sources'" title="Data source management" subtitle="Connection status and freshness (MVP: Tilkit-native sources)">
        @include('analytics.partials.freshness-panel', ['sources' => $freshness])

        <div class="tk-card p-5 mt-6 text-sm text-tk-muted">
            <p class="font-medium text-tk-ink mb-2">Coming soon</p>
            <ul class="list-disc ml-5 space-y-1">
                <li>GA4, advertising APIs, and warehouse connectors</li>
                <li>Sync job history and retry controls</li>
                <li>Field mapping for CSV/Excel imports</li>
            </ul>
        </div>
    </x-analytics.acc-shell>
</x-app-layout>
