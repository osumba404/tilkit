@php
    $active = $tilkitNav ?? 'overview';
    $canManageTeam = isset($capabilities) && $capabilities->canManageTool();
@endphp
<nav class="tk-tilkit-subnav animate-tk-fade-in" aria-label="Weather log sections">
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
@include('tools.tilkit.partials.program-switcher')
