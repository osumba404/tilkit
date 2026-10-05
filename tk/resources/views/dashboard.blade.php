<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-serif text-2xl font-bold text-tk-ink">Dashboard</h2>
                <p class="text-sm text-tk-muted mt-1">Your tools and latest collected data</p>
            </div>
            <a href="{{ route('tools.create') }}" class="tk-btn-primary">New tool</a>
        </div>
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
            <h3 class="font-semibold mb-4">Your tools</h3>
            @forelse ($tools as $tool)
                <a href="{{ route('tools.show', $tool) }}" class="tk-card block p-4 mb-3 hover:border-tk-accent transition">
                    <div class="flex justify-between gap-4">
                        <div>
                            <p class="font-semibold">{{ $tool->name }}</p>
                            <p class="text-sm text-tk-muted">{{ $tool->code }} · {{ $tool->submissions_count }} submissions</p>
                        </div>
                        <span class="text-xs uppercase text-tk-muted">{{ $tool->status }}</span>
                    </div>
                </a>
            @empty
                <p class="text-tk-muted text-sm">No tools yet. <a href="{{ route('tools.create') }}" class="text-tk-accent underline">Create your first tool</a>.</p>
            @endforelse
        </section>
        <section>
            <h3 class="font-semibold mb-4">Recent submissions</h3>
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
