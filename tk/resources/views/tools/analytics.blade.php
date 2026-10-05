<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <h2 class="font-serif text-2xl font-bold">Analytics — {{ $tool->name }}</h2>
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
                datasets: [{ label: 'Submissions', data: trend.map(r => r.count), borderColor: '#0e7c86', tension: 0.25, fill: false }],
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });

        const channels = @json($overview['by_channel']);
        new Chart(document.getElementById('channelChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(channels),
                datasets: [{ data: Object.values(channels), backgroundColor: ['#0e7c86', '#506274', '#d6eef0', '#12263a'] }],
            },
            options: { responsive: true },
        });
    </script>
    @endpush
</x-app-layout>
