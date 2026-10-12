<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Analytics — {{ $tool->name }}</h2>
                <p class="text-sm text-tk-muted">Submission trends and distributions</p>
            </div>
            <a href="{{ route('tools.export', $tool) }}" class="tk-btn-secondary">Download CSV</a>
        </div>
    </x-slot>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="tk-stat"><p class="tk-stat-label">Total submissions</p><p class="tk-stat-value">{{ number_format($overview['total_submissions']) }}</p></div>
        <div class="tk-stat"><p class="tk-stat-label">Today</p><p class="tk-stat-value">{{ number_format($overview['submissions_today']) }}</p></div>
        <div class="tk-stat"><p class="tk-stat-label">Published forms</p><p class="tk-stat-value">{{ number_format($overview['published_forms']) }}</p></div>
        <div class="tk-stat"><p class="tk-stat-label">Forms</p><p class="tk-stat-value">{{ number_format($overview['forms_count']) }}</p></div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        <div class="tk-card p-5">
            <h3 class="font-semibold mb-4">Submissions (30 days)</h3>
            <canvas id="trendChart" height="200"></canvas>
        </div>
        <div class="tk-card p-5">
            <h3 class="font-semibold mb-4">By channel</h3>
            <canvas id="channelChart" height="200"></canvas>
        </div>
    </div>

    @if ($tercile)
        <div class="tk-card p-5 mb-8">
            <h3 class="font-semibold mb-2">Seasonal tercile tally (live)</h3>
            <p class="text-xs text-tk-muted mb-4">{{ $tercile['eligible_count'] }} protocol-eligible indicator records</p>
            <div class="grid sm:grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="font-medium mb-2">Simple counts</p>
                    <ul class="space-y-1">
                        <li>Above normal: {{ $tercile['simple']['above']['count'] }} ({{ $tercile['simple']['above']['pct'] }}%)</li>
                        <li>Normal: {{ $tercile['simple']['normal']['count'] }} ({{ $tercile['simple']['normal']['pct'] }}%)</li>
                        <li>Below normal: {{ $tercile['simple']['below']['count'] }} ({{ $tercile['simple']['below']['pct'] }}%)</li>
                    </ul>
                </div>
                <div>
                    <p class="font-medium mb-2">Weighted (final weight)</p>
                    <ul class="space-y-1">
                        <li>Above normal: {{ number_format($tercile['weighted']['above']['pct'], 1) }}%</li>
                        <li>Normal: {{ number_format($tercile['weighted']['normal']['pct'], 1) }}%</li>
                        <li>Below normal: {{ number_format($tercile['weighted']['below']['pct'], 1) }}%</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if (count($calibration))
        <div class="tk-card p-5 mb-8 overflow-x-auto">
            <h3 class="font-semibold mb-3">Calibration by indicator ID</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-tk-muted">
                    <tr><th class="pb-2">Indicator</th><th class="pb-2">Records</th><th class="pb-2">Hit rate</th><th class="pb-2">Rating drift</th></tr>
                </thead>
                <tbody>
                    @foreach ($calibration as $row)
                        <tr class="border-t border-tk-line">
                            <td class="py-2 font-mono text-xs">{{ $row['indicator_id'] }}</td>
                            <td class="py-2">{{ $row['submissions'] }}</td>
                            <td class="py-2">{{ $row['hit_rate'] ?? '—' }}</td>
                            <td class="py-2">{{ $row['reliability_drift'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="tk-card p-5">
        <h3 class="font-semibold mb-2">Status breakdown</h3>
        <ul class="text-sm space-y-1">
            @forelse ($overview['by_status'] as $status => $count)
                <li class="flex justify-between border-b border-tk-line py-2"><span>{{ $status }}</span><span class="font-mono">{{ $count }}</span></li>
            @empty
                <li class="text-tk-muted">No submissions yet.</li>
            @endforelse
        </ul>
    </div>

    @push('scripts')
    <script type="module">
        import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, BarController, BarElement, ArcElement, DoughnutController, Legend, Tooltip } from 'chart.js';
        Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, BarController, BarElement, ArcElement, DoughnutController, Legend, Tooltip);

        const trend = @json($trend);
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trend.map(r => r.date),
                datasets: [{ label: 'Submissions', data: trend.map(r => r.count), borderColor: '#0891b2', tension: 0.25, fill: false }],
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });

        const channels = @json($overview['by_channel']);
        new Chart(document.getElementById('channelChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(channels),
                datasets: [{ data: Object.values(channels), backgroundColor: ['#0891b2', '#64748b', '#94a3b8', '#334155'] }],
            },
            options: { responsive: true },
        });
    </script>
    @endpush
</x-app-layout>
