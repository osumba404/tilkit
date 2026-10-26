@php
    $active = $tilkitNav ?? 'overview';
    $canManageTeam = isset($capabilities) && $capabilities->canManageTool();
@endphp
<div class="flex flex-col gap-3 sm:flex-row sm:items-stretch sm:justify-between mb-8">
<nav class="tk-tilkit-subnav animate-tk-fade-in flex-1 mb-0 min-w-0" aria-label="Weather log sections">
    @foreach ([
        ['id' => 'overview', 'label' => 'Home', 'route' => 'tools.tilkit.overview'],
        ['id' => 'capture', 'label' => 'Capture', 'route' => 'tools.tilkit.records.create'],
        ['id' => 'records', 'label' => 'Records', 'route' => 'tools.tilkit.records.index'],
        ['id' => 'insights', 'label' => 'Analysis', 'route' => 'tools.tilkit.insights'],
        ['id' => 'tally', 'label' => 'Tally', 'route' => 'tools.tilkit.tally'],
        ['id' => 'desk', 'label' => 'Data desk', 'route' => 'tools.tilkit.data-desk'],
        ['id' => 'setup', 'label' => 'Setup', 'route' => 'tools.tilkit.setup'],
    ] as $link)
        <a href="{{ route($link['route'], $tool) }}"
           class="tk-tilkit-subnav-link {{ $active === $link['id'] ? 'is-active' : '' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
    @if ($canManageTeam)
        <a href="{{ route('tools.tilkit.team', $tool) }}"
           class="tk-tilkit-subnav-link {{ $active === 'team' ? 'is-active' : '' }}">
            Team
        </a>
    @endif
</nav>
    <a href="{{ route('tools.tilkit.deep-analysis', $tool) }}"
       class="tk-btn-primary shrink-0 self-end sm:self-center text-sm whitespace-nowrap shadow-tk-sm">
        Deep Analysis
    </a>
</div>
@include('tools.tilkit.partials.program-switcher')
