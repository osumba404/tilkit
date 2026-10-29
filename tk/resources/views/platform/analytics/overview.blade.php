<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Analytics Command Center" eyebrow="Platform" />
    </x-slot>

    <x-analytics.acc-shell context="platform" :acc-section="'overview'" title="Executive overview" subtitle="Organization-wide field data performance">
        @include('analytics.partials.filter-bar', ['filters' => $filters, 'showFormFilter' => false, 'filterAction' => route('platform.analytics.overview')])

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            @include('analytics.partials.kpi-card', ['label' => 'Submissions (period)', 'value' => number_format($overview['submissions']), 'delta' => $overview['submissions_delta']])
            @include('analytics.partials.kpi-card', ['label' => 'Submissions today', 'value' => number_format($overview['submissions_today'])])
            @include('analytics.partials.kpi-card', ['label' => 'Active tools', 'value' => number_format($overview['tools'])])
            @include('analytics.partials.kpi-card', ['label' => 'Users', 'value' => number_format($overview['users'])])
        </div>

        <div class="grid lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 tk-card p-5">
                <h3 class="font-semibold mb-4">Platform submission trend ({{ $filters->periodDays }}d)</h3>
                <canvas id="accTrendChart" height="200"></canvas>
                <p class="text-xs text-tk-muted mt-2">Updated: {{ $overview['last_updated'] ? \Illuminate\Support\Carbon::parse($overview['last_updated'])->toDateTimeString() : '-' }}</p>
            </div>
            @include('analytics.partials.freshness-panel', ['sources' => $freshness])
        </div>

        <div class="grid lg:grid-cols-3 gap-6 mb-6">
            <div class="tk-card p-5">
                <h3 class="font-semibold mb-4">By channel</h3>
                <canvas id="accChannelChart" height="200"></canvas>
            </div>
            <div class="tk-card p-5">
                <h3 class="font-semibold mb-4">By status</h3>
                <canvas id="accStatusChart" height="200"></canvas>
            </div>
            <div class="tk-card p-5">
                <h3 class="font-semibold mb-3">Top tools by volume</h3>
                <ul class="text-sm space-y-2">
                    @forelse ($topTools as $row)
                        <li class="flex justify-between border-b border-tk-line py-2">
                            <a href="{{ route('tools.analytics', $row['tool']) }}" class="text-tk-accent font-medium">{{ $row['tool']->name }}</a>
                            <span class="font-mono">{{ number_format($row['count']) }}</span>
                        </li>
                    @empty
                        <li class="text-tk-muted">No submissions in this period.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </x-analytics.acc-shell>

    @push('scripts')
    @vite('resources/js/acc-dashboard.js')
    <script type="module">
        import { initAccCharts } from '{{ Vite::asset('resources/js/acc-dashboard.js') }}';
        initAccCharts({
            trend: @json($trend),
            channels: @json($overview['by_channel']),
            statuses: @json($overview['by_status']),
            baseUrl: @json(route('platform.analytics.overview')),
            query: @json($filters->toQueryArray()),
        });
    </script>
    @endpush
</x-app-layout>
