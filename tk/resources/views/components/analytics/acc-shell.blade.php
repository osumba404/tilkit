@props([
    'context',
    'accSection',
    'title',
    'subtitle' => null,
    'tool' => null,
])

<div class="flex flex-col lg:flex-row gap-6 min-h-[60vh]">
    <aside class="acc-sidebar w-full lg:w-56 shrink-0 rounded-lg p-4 text-sm text-slate-200">
        <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Analytics Command Center</p>
        <p class="font-semibold text-white mb-4">{{ $context === 'platform' ? 'Platform' : $tool?->name }}</p>
        <nav class="space-y-1" aria-label="Analytics sections">
            @if ($context === 'platform')
                <a href="{{ route('platform.analytics.overview') }}" class="acc-nav-link @if($accSection === 'overview') acc-nav-active @endif">Executive overview</a>
                <a href="{{ route('platform.analytics.sources') }}" class="acc-nav-link @if($accSection === 'sources') acc-nav-active @endif">Data sources</a>
                <a href="{{ route('platform.index') }}" class="acc-nav-link">Administration</a>
            @else
                <a href="{{ route('tools.analytics', $tool) }}" class="acc-nav-link @if($accSection === 'overview') acc-nav-active @endif">Overview</a>
                <a href="{{ route('tools.analytics.explorer', $tool) }}" class="acc-nav-link @if($accSection === 'explorer') acc-nav-active @endif">Submissions explorer</a>
                <a href="{{ route('tools.analytics.review', $tool) }}" class="acc-nav-link @if($accSection === 'review') acc-nav-active @endif">Review & quality</a>
                <a href="{{ route('tools.analytics.reports', $tool) }}" class="acc-nav-link @if($accSection === 'reports') acc-nav-active @endif">Reports</a>
                <a href="{{ route('tools.show', $tool) }}" class="acc-nav-link mt-4 border-t border-slate-600 pt-3">← Tool home</a>
            @endif
        </nav>
    </aside>
    <div class="flex-1 min-w-0">
        <div class="mb-4">
            <h2 class="text-xl font-bold">{{ $title }}</h2>
            @if ($subtitle)
                <p class="text-sm text-tk-muted">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $slot }}
    </div>
</div>
