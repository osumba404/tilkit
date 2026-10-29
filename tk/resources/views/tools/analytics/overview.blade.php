<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$tool->name" eyebrow="Tool analytics" />
    </x-slot>

    <x-analytics.acc-shell context="tool" :acc-section="$accSection" :tool="$tool" title="Executive overview" subtitle="Performance at a glance for this collection tool">
        @include('analytics.partials.filter-bar', ['filters' => $filters, 'forms' => $forms, 'showFormFilter' => true])

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            @include('analytics.partials.kpi-card', [
                'label' => 'Submissions in period',
                'value' => number_format($overview['total_submissions']),
                'delta' => $totalDelta,
                'href' => route('tools.analytics.explorer', $tool).'?'.http_build_query($filters->toQueryArray()),
            ])
            @include('analytics.partials.kpi-card', [
                'label' => 'Today',
                'value' => number_format($overview['submissions_today']),
            ])
            @include('analytics.partials.kpi-card', [
                'label' => 'In review queue',
                'value' => number_format($overview['in_review']),
                'href' => route('tools.submissions.review', $tool),
            ])
            @include('analytics.partials.kpi-card', [
                'label' => 'Approval rate',
                'value' => $overview['approval_rate'] !== null ? $overview['approval_rate'].'%' : '-',
                'footnote' => 'Approved ÷ (approved + rejected) in period',
            ])
        </div>

        <div class="grid lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 tk-card p-5">
                <h3 class="font-semibold mb-1">Submission trend</h3>
                <p class="text-xs text-tk-muted mb-4">Click a point to drill (future). Source: dc_submissions</p>
                <canvas id="accTrendChart" height="200" data-acc-chart="trend"></canvas>
                <p class="text-xs text-tk-muted mt-2">Updated: {{ $overview['last_updated'] ? \Illuminate\Support\Carbon::parse($overview['last_updated'])->toDateTimeString() : '-' }}</p>
            </div>
            @include('analytics.partials.freshness-panel', ['sources' => $freshness])
        </div>

        <div class="grid lg:grid-cols-2 gap-6 mb-6">
            <div class="tk-card p-5">
                <h3 class="font-semibold mb-4">By channel</h3>
                <canvas id="accChannelChart" height="200" data-acc-chart="channel" data-filter-key="channel"></canvas>
            </div>
            <div class="tk-card p-5">
                <h3 class="font-semibold mb-4">By status</h3>
                <canvas id="accStatusChart" height="200" data-acc-chart="status" data-filter-key="status"></canvas>
            </div>
        </div>

        @if ($tercile)
            <div class="tk-card p-5 mb-6">
                <h3 class="font-semibold mb-2">Seasonal tercile tally</h3>
                <p class="text-xs text-tk-muted mb-4">{{ $tercile['eligible_count'] }} eligible records · site/season filters apply</p>
                <div class="grid sm:grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="font-medium mb-2">Simple counts</p>
                        <ul class="space-y-1">
                            <li>Above: {{ $tercile['simple']['above']['count'] }} ({{ $tercile['simple']['above']['pct'] }}%)</li>
                            <li>Normal: {{ $tercile['simple']['normal']['count'] }} ({{ $tercile['simple']['normal']['pct'] }}%)</li>
                            <li>Below: {{ $tercile['simple']['below']['count'] }} ({{ $tercile['simple']['below']['pct'] }}%)</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-medium mb-2">Weighted</p>
                        <ul class="space-y-1">
                            <li>Above: {{ number_format($tercile['weighted']['above']['pct'], 1) }}%</li>
                            <li>Normal: {{ number_format($tercile['weighted']['normal']['pct'], 1) }}%</li>
                            <li>Below: {{ number_format($tercile['weighted']['below']['pct'], 1) }}%</li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if (count($calibration))
            <div class="tk-card p-5 mb-6 overflow-x-auto">
                <h3 class="font-semibold mb-3">Calibration by indicator</h3>
                <table class="w-full text-sm">
                    <thead class="text-left text-tk-muted"><tr><th class="pb-2">ID</th><th class="pb-2">Records</th><th class="pb-2">Hit rate</th><th class="pb-2">Drift</th></tr></thead>
                    <tbody>
                        @foreach ($calibration as $row)
                            <tr class="border-t border-tk-line"><td class="py-2 font-mono text-xs">{{ $row['indicator_id'] }}</td><td class="py-2">{{ $row['submissions'] }}</td><td class="py-2">{{ $row['hit_rate'] ?? '-' }}</td><td class="py-2">{{ $row['reliability_drift'] ?? '-' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="tk-card p-5 text-sm text-tk-muted">
            <p class="font-medium text-tk-ink mb-1">Management summary</p>
            <p>
                {{ number_format($overview['total_submissions']) }} submissions in {{ $filters->periodLabel() }}.
                @if ($totalDelta['change_pct'] !== null)
                    Volume is {{ $totalDelta['change_pct'] >= 0 ? 'up' : 'down' }} {{ abs($totalDelta['change_pct']) }}% vs the prior equivalent period.
                @endif
                @if ($overview['in_review'] > 0)
                    {{ number_format($overview['in_review']) }} record(s) await review.
                @endif
            </p>
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
            baseUrl: @json(route('tools.analytics', $tool)),
            query: @json($filters->toQueryArray()),
        });
    </script>
    @endpush
</x-app-layout>
