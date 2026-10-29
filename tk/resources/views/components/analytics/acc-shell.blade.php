@props([
    'context',
    'accSection',
    'title',
    'subtitle' => null,
    'tool' => null,
    'wide' => false,
])

@php
    use App\Support\Tilkit\WeatherTool;
    $accWeatherTool = $tool ?? (WeatherTool::singleToolMode() ? WeatherTool::resolve() : null);
@endphp

<div @class(['flex flex-col lg:flex-row gap-6 min-h-[60vh] w-full', 'dataset-acc-wide' => $wide])>
    <aside @class(['acc-sidebar shrink-0 rounded-tk p-5 text-sm text-slate-200', 'w-full lg:w-52 xl:w-56' => $wide, 'w-full lg:w-56' => ! $wide])>
        <p class="text-[0.65rem] uppercase tracking-tk-label text-slate-400 mb-1 font-medium">Analytics Command Center</p>
        <p class="font-display font-semibold text-white mb-5 text-base leading-snug">
            @if ($context === 'platform')
                Platform
            @else
                {{ $tool?->name }}
            @endif
        </p>
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
                @if (WeatherTool::isWeatherModule($accWeatherTool))
                    <a href="{{ route('tools.tilkit.insights', $accWeatherTool) }}" class="acc-nav-link mt-4 border-t border-slate-600 pt-3">← Analysis hub</a>
                @elseif ($tool)
                    <a href="{{ route('tools.show', $tool) }}" class="acc-nav-link mt-4 border-t border-slate-600 pt-3">← Tool home</a>
                @else
                    <a href="{{ route('dashboard') }}" class="acc-nav-link mt-4 border-t border-slate-600 pt-3">← Dashboard</a>
                @endif
            @endif
        </nav>
    </aside>
    <div class="flex-1 min-w-0 w-full max-w-none">
        <div class="mb-6">
            <h2 class="acc-page-title">{{ $title }}</h2>
            @if ($subtitle)
                <p class="acc-page-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $slot }}
    </div>
</div>
