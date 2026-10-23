<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Dashboard" subtitle="Your tools and latest collected data">
            <x-slot:actions>
                <a href="{{ route('platform.datasets.index') }}" class="tk-btn-secondary">Upload & analyze data</a>
                @if ($tilkitWeatherTool ?? null)
                    <a href="{{ route('tools.tilkit.overview', $tilkitWeatherTool) }}" class="tk-btn-primary">Weather log</a>
                @else
                    <a href="{{ route('tools.create') }}" class="tk-btn-primary">New tool</a>
                @endif
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if ($platformStats)
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            @foreach ([
                'users' => 'Platform users',
                'tools' => 'Tools',
                'forms' => 'Forms',
                'submissions' => 'All submissions',
                'submissions_7d' => 'Last 7 days',
            ] as $key => $label)
                <div class="tk-stat">
                    <p class="tk-stat-label">{{ $label }}</p>
                    <p class="tk-stat-value">{{ number_format($platformStats[$key] ?? 0) }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-8">
        <section>
            <h3 class="tk-section-title mb-4">Your tools</h3>
            @forelse ($tools as $tool)
                <a href="{{ route('tools.show', $tool) }}" class="tk-card-interactive block p-4 mb-3">
                    <div class="flex justify-between gap-4">
                        <div>
                            <p class="font-semibold">{{ $tool->name }}</p>
                            <p class="text-sm text-tk-muted">{{ $tool->code }} · {{ $tool->submissions_count }} submissions</p>
                        </div>
                        <span class="text-xs uppercase text-tk-muted">{{ $tool->status }}</span>
                    </div>
                </a>
            @empty
                @if ($tilkitWeatherTool ?? null)
                    <p class="text-tk-muted text-sm">Weather log is not available for your account yet. Ask an administrator for access.</p>
                @else
                    <p class="text-tk-muted text-sm">No tools yet. <a href="{{ route('tools.create') }}" class="text-tk-accent underline">Create your first tool</a>.</p>
                @endif
            @endforelse
        </section>
        <section>
            <h3 class="tk-section-title mb-4">Recent submissions</h3>
            @forelse ($recentSubmissions as $sub)
                <div class="tk-card p-4 mb-3 text-sm">
                    <p class="font-medium">{{ $sub->form?->title ?? 'Form' }}</p>
                    <p class="text-tk-muted">{{ $sub->project?->name }} · {{ $sub->received_at?->diffForHumans() }} · {{ $sub->status }}</p>
                    @if ($sub->project)
                        <a class="text-tk-accent text-xs mt-1 inline-block" href="{{ route('tools.submissions.show', [$sub->project, $sub]) }}">View record</a>
                    @endif
                </div>
            @empty
                <p class="text-tk-muted text-sm">Submissions will appear here once forms are published and filled.</p>
            @endforelse
        </section>
    </div>
</x-app-layout>
