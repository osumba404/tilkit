@php
    $active = $tilkitNav ?? 'overview';
    $canManageTeam = isset($capabilities) && $capabilities->canManageTool();
    $canCaptureRecords = $canCaptureRecords ?? (($capabilities ?? null)?->canFillForms() ?? true);
    $canAccessDataDesk = $canAccessDataDesk ?? false;
    $canAccessDeepAnalysis = $canAccessDeepAnalysis ?? (($capabilities ?? null)?->canViewAnalytics() ?? false);
    $analysisHubTabs = ['insights', 'tally', 'desk'];
    $showDeepAnalysis = $canAccessDeepAnalysis
        && (in_array($active, $analysisHubTabs, true) || request()->routeIs('tools.tilkit.deep-analysis'));
    $onDeepAnalysis = request()->routeIs('tools.tilkit.deep-analysis');

    $navLinks = [
        ['id' => 'overview', 'label' => 'Home', 'route' => 'tools.tilkit.overview'],
        ['id' => 'setup', 'label' => 'Setup', 'route' => 'tools.tilkit.setup'],
    ];
    if ($canCaptureRecords) {
        $navLinks[] = ['id' => 'capture', 'label' => 'Capture', 'route' => 'tools.tilkit.records.create'];
    }
    $navLinks = array_merge($navLinks, [
        ['id' => 'records', 'label' => 'Records', 'route' => 'tools.tilkit.records.index'],
        ['id' => 'insights', 'label' => 'Analysis', 'route' => 'tools.tilkit.insights'],
        ['id' => 'tally', 'label' => 'Tally', 'route' => 'tools.tilkit.tally'],
    ]);
    if ($canAccessDataDesk) {
        $navLinks[] = ['id' => 'desk', 'label' => 'Data desk', 'route' => 'tools.tilkit.data-desk'];
    }
@endphp
<div class="mb-8">
    <div class="tk-tilkit-subnav !mb-0 animate-tk-fade-in flex items-stretch min-w-0 gap-0 p-1" aria-label="Weather log sections">
        <div class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto">
            @foreach ($navLinks as $link)
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
        </div>

        @if ($showDeepAnalysis)
            <div class="tk-subnav-da-slot flex shrink-0 items-center overflow-hidden border-l border-tk-line/70 pl-1 ml-0.5">
                <a href="{{ route('tools.tilkit.deep-analysis', $tool) }}"
                   @class([
                       'tk-tilkit-subnav-link tk-tilkit-subnav-link--deep whitespace-nowrap',
                       'is-active' => $onDeepAnalysis,
                   ])>
                    Deep Analysis
                </a>
            </div>
        @endif
    </div>
</div>
@if ($showProgramSwitcher ?? true)
    @include('tools.tilkit.partials.program-switcher')
@endif
