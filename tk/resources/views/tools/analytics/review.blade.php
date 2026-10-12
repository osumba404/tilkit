<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold">Review & quality</h2>
        <p class="text-sm text-tk-muted">{{ $tool->name }}</p>
    </x-slot>

    <x-analytics.acc-shell context="tool" :acc-section="$accSection" :tool="$tool" title="Review workflow" subtitle="Status distribution for the selected period">
        @include('analytics.partials.filter-bar', ['filters' => $filters, 'forms' => $forms, 'showFormFilter' => true])

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @foreach ($review as $status => $count)
                <a href="{{ route('tools.analytics.explorer', $tool).'?'.http_build_query(array_merge($filters->toQueryArray(), ['status' => $status])) }}" class="tk-card p-5 hover:border-tk-accent">
                    <p class="text-xs text-tk-muted uppercase">{{ str_replace('_', ' ', $status) }}</p>
                    <p class="text-2xl font-semibold mt-1">{{ number_format($count) }}</p>
                </a>
            @endforeach
        </div>

        <a href="{{ route('tools.submissions.review', $tool) }}" class="tk-btn-primary text-sm">Open review queue</a>
    </x-analytics.acc-shell>
</x-app-layout>
