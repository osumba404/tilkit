<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between gap-2">
            <div>
                <h2 class="text-2xl font-bold">{{ $dataset->name }}</h2>
                <p class="text-sm text-tk-muted font-mono">{{ $dataset->original_filename }} · {{ $dataset->row_count }} rows</p>
            </div>
            <a href="{{ route('platform.datasets.index') }}" class="tk-btn-secondary text-sm">All datasets</a>
        </div>
    </x-slot>

    <x-analytics.acc-shell context="platform" acc-section="datasets" title="Analyze with natural language" subtitle="LLM-generated Python in a sandbox (with PHP fallback)">
        <div class="grid lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 tk-card p-5">
                <h3 class="font-semibold mb-3">Column mapping</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-tk-muted bg-tk-soft">
                            <tr><th class="p-2">Column</th><th class="p-2">Inferred type</th><th class="p-2">Samples</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($dataset->columns as $col)
                                <tr class="border-t border-tk-line">
                                    <td class="p-2 font-mono text-xs">{{ $col->name }}</td>
                                    <td class="p-2">{{ $col->inferred_type }}</td>
                                    <td class="p-2 text-xs text-tk-muted">{{ implode(', ', $col->sample_values ?? []) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tk-card p-5 text-sm">
                <h3 class="font-semibold mb-2">Pipeline status</h3>
                <ul class="space-y-2 text-tk-muted">
                    <li>✓ Ingestion & header parse</li>
                    <li>✓ Type inference</li>
                    <li>✓ Workspace: <code class="text-xs">storage/app/{{ $dataset->workspace_path }}</code></li>
                    <li>{{ $llmConfigured ? '✓' : '○' }} LLM code generation (OPENAI_API_KEY)</li>
                    <li>✓ Sandboxed run + auto-retry on errors</li>
                    <li>✓ Chart + summary rendering</li>
                </ul>
            </div>
        </div>

        <div class="tk-card p-5 mb-8">
            <h3 class="font-semibold mb-3">Ask a question</h3>
            <form method="POST" action="{{ route('platform.datasets.analyze', $dataset) }}" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="text" name="prompt" required placeholder="e.g. Draw a pie chart of expenses by category" class="flex-1 rounded-md border border-tk-line px-3 py-2 text-sm">
                <button type="submit" class="tk-btn-primary text-sm shrink-0">Run analysis</button>
            </form>
            <p class="text-xs text-tk-muted mt-2">Examples: bar chart of status, mean of amount, count by region, pie chart of department.</p>
        </div>

        <div class="space-y-6">
            @foreach ($dataset->analysisRuns as $run)
                <article class="tk-card p-5">
                    <header class="flex flex-wrap justify-between gap-2 mb-3">
                        <p class="font-medium">{{ $run->prompt }}</p>
                        <span class="text-xs text-tk-muted">{{ $run->created_at->format('Y-m-d H:i') }} · {{ $run->user?->name }} · {{ $run->status }}</span>
                    </header>

                    @if ($run->summary)
                        <div class="text-sm bg-tk-soft rounded-md p-4 mb-4">
                            <p class="text-xs font-semibold text-tk-muted uppercase mb-1">Summary</p>
                            <p>{{ $run->summary }}</p>
                        </div>
                    @endif

                    @if ($run->chart_image_path)
                        <img src="{{ route('platform.datasets.chart', [$dataset, $run]) }}" alt="Analysis chart" class="max-w-full rounded border border-tk-line mb-4">
                    @elseif ($run->chart_spec)
                        <canvas id="run-chart-{{ $run->id }}" height="120" class="max-h-64 mb-4" data-spec="{{ json_encode($run->chart_spec) }}"></canvas>
                    @endif

                    @if ($run->error_message && $run->status === 'failed')
                        <pre class="text-xs text-red-700 bg-red-50 p-3 rounded overflow-x-auto">{{ $run->error_message }}</pre>
                    @endif

                    @if ($run->generated_code)
                        <details class="text-xs mt-2">
                            <summary class="cursor-pointer text-tk-muted">Generated Python (sandbox)</summary>
                            <pre class="mt-2 bg-slate-900 text-slate-100 p-3 rounded overflow-x-auto">{{ $run->generated_code }}</pre>
                        </details>
                    @endif
                </article>
            @endforeach
        </div>
    </x-analytics.acc-shell>

    @push('scripts')
    @vite('resources/js/acc-dashboard.js')
    <script type="module">
        import { Chart, BarController, BarElement, CategoryScale, LinearScale, ArcElement, DoughnutController, Legend, Tooltip } from 'chart.js';
        Chart.register(BarController, BarElement, CategoryScale, LinearScale, ArcElement, DoughnutController, Legend, Tooltip);
        document.querySelectorAll('[data-spec]').forEach((canvas) => {
            const spec = JSON.parse(canvas.dataset.spec);
            if (!spec?.labels) return;
            const type = spec.type === 'pie' ? 'doughnut' : 'bar';
            new Chart(canvas, {
                type,
                data: {
                    labels: spec.labels,
                    datasets: [{ data: spec.values, backgroundColor: ['#0891b2', '#64748b', '#94a3b8', '#334155', '#0e7490'] }],
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
            });
        });
    </script>
    @endpush
</x-app-layout>
