<x-app-layout>
    <x-slot name="header">
        @include('platform.datasets.partials.header')
    </x-slot>

    <x-analytics.acc-shell context="dataset" :acc-section="$accSection" :dataset="$dataset" title="Ask AI" subtitle="Natural language analysis with sandboxed Python (PHP fallback without API key)">
        <div class="tk-card p-5 mb-8">
            <h3 class="font-semibold mb-3">Ask a question</h3>
            <form method="POST" action="{{ route('platform.datasets.analyze.run', $dataset) }}" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="text" name="prompt" required placeholder="e.g. Draw a pie chart of expenses by category" class="flex-1 rounded-md border border-tk-line px-3 py-2 text-sm">
                <button type="submit" class="tk-btn-primary text-sm shrink-0">Run analysis</button>
            </form>
            <p class="text-xs text-tk-muted mt-2">
                {{ $llmConfigured ? 'LLM code generation is enabled.' : 'Set OPENAI_API_KEY for full Python charts; basic charts still work via PHP.' }}
                Try: line graph of amount over date, scatter plot of X vs Y, histogram of price, pie chart of category.
            </p>
        </div>

        <div class="space-y-6">
            @foreach ($dataset->analysisRuns as $run)
                <article class="tk-card p-5">
                    <header class="flex flex-wrap justify-between gap-2 mb-3">
                        <p class="font-medium">{{ $run->prompt }}</p>
                        <span class="text-xs text-tk-muted">{{ $run->created_at->format('Y-m-d H:i') }} · {{ $run->status }}</span>
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
                        <div class="mb-4 max-w-2xl">
                            <canvas id="run-chart-{{ $run->id }}" height="160" data-chart-spec='@json($run->chart_spec)'></canvas>
                        </div>
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
    <script>window.__tilkitAcc = { type: 'analysis-runs' };</script>
    @vite('resources/js/acc-dashboard.js')
    @endpush
</x-app-layout>
