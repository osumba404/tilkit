@php
    $active = $tilkitNav ?? 'overview';
    $canManageTeam = isset($capabilities) && $capabilities->canManageTool();
    $analysisHubTabs = ['insights', 'tally', 'desk'];
    $showDeepAnalysis = in_array($active, $analysisHubTabs, true)
        || request()->routeIs('tools.tilkit.deep-analysis');
    $onDeepAnalysis = request()->routeIs('tools.tilkit.deep-analysis');
@endphp
<div class="mb-8">
    <div class="tk-tilkit-subnav !mb-0 animate-tk-fade-in flex items-stretch min-w-0 gap-0 p-1" aria-label="Weather log sections">
        <div class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto">
            @foreach ([
                ['id' => 'overview', 'label' => 'Home', 'route' => 'tools.tilkit.overview'],
                ['id' => 'setup', 'label' => 'Setup', 'route' => 'tools.tilkit.setup'],
                ['id' => 'capture', 'label' => 'Capture', 'route' => 'tools.tilkit.records.create'],
                ['id' => 'records', 'label' => 'Records', 'route' => 'tools.tilkit.records.index'],
                ['id' => 'insights', 'label' => 'Analysis', 'route' => 'tools.tilkit.insights'],
                ['id' => 'tally', 'label' => 'Tally', 'route' => 'tools.tilkit.tally'],
                ['id' => 'desk', 'label' => 'Data desk', 'route' => 'tools.tilkit.data-desk'],
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
        </div>

        <div
            class="tk-subnav-da-slot flex shrink-0 items-center overflow-hidden border-l border-tk-line/70 pl-1 ml-0.5"
            x-data="{ show: @json($showDeepAnalysis) }"
            x-show="show"
            x-cloak
            x-transition:enter="transition-all ease-out duration-300"
            x-transition:enter-start="opacity-0 max-w-0 border-l-0 pl-0 ml-0"
            x-transition:enter-end="opacity-100 max-w-[13rem] border-l pl-1 ml-0.5"
            x-transition:leave="transition-all ease-in duration-200"
            x-transition:leave-start="opacity-100 max-w-[13rem]"
            x-transition:leave-end="opacity-0 max-w-0 border-l-0 pl-0 ml-0"
        >
            <a href="{{ route('tools.tilkit.deep-analysis', $tool) }}"
               @class([
                   'tk-tilkit-subnav-link tk-tilkit-subnav-link--deep whitespace-nowrap',
                   'is-active' => $onDeepAnalysis,
               ])>
                Deep Analysis
            </a>
        </div>
    </div>
</div>
@include('tools.tilkit.partials.program-switcher')
