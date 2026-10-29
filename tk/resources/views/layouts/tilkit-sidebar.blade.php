@php
    $tool = $tool ?? request()->route('tool');
@endphp
<aside
    class="tk-tilkit-sidebar tk-sidebar-closed fixed left-0 top-0 z-40 flex h-screen w-16 flex-col overflow-hidden border-r border-tk-line/80 bg-tk-surface/95 backdrop-blur-md shadow-[1px_0_0_0_rgba(15,20,25,0.04)] transition-[width] duration-300 ease-out"
    :class="open ? 'w-60 tk-sidebar-open' : 'w-16 tk-sidebar-closed'"
    aria-label="Site navigation"
    @mouseenter="hover = true"
    @mouseleave="hover = false"
>
    <div class="flex shrink-0 items-center gap-1 border-b border-tk-line/60 px-2 py-3 min-h-[4.25rem]">
        <a href="{{ route('home') }}" class="flex min-w-0 flex-1 items-center gap-2 rounded-lg p-1 hover:bg-tk-soft/80" aria-label="Tilkit home">
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-tk-accent text-sm font-bold text-white shadow-tk-sm" aria-hidden="true">T</span>
            <span class="min-w-0 flex-col leading-tight overflow-hidden" x-show="open" x-cloak>
                <span class="tk-nav-brand truncate text-base">Tilkit</span>
                <span class="tk-nav-tagline !inline truncate text-[0.65rem]">Data collection</span>
            </span>
        </a>
        <button
            type="button"
            class="tk-btn-ghost shrink-0 p-1.5"
            x-show="open"
            x-cloak
            @click="pinned = !pinned"
            :aria-pressed="pinned"
            aria-label="Keep sidebar open"
        >
            <svg class="h-5 w-5" :class="pinned ? 'text-tk-accent' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14M5 12h14M5 19h14"/>
            </svg>
        </button>
    </div>

    <nav class="flex min-h-0 flex-1 flex-col gap-1 overflow-hidden px-2 py-3" aria-label="Primary">
        @auth
            @if ($tilkitWeatherTool ?? null)
                <a
                    href="{{ route('tools.tilkit.overview', $tilkitWeatherTool) }}"
                    class="tk-tilkit-sidebar-link {{ request()->routeIs('tools.tilkit.*') || request()->routeIs('dashboard') ? 'is-active' : '' }}"
                    title="Weather log"
                >
                    <span class="tk-tilkit-sidebar-icon">@include('layouts.partials.tilkit-sidebar-icon', ['name' => 'weather'])</span>
                    <span class="truncate" x-show="open" x-cloak>Weather log</span>
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="tk-tilkit-sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" title="Dashboard">
                    <span class="tk-tilkit-sidebar-icon">@include('layouts.partials.tilkit-sidebar-icon', ['name' => 'home'])</span>
                    <span class="truncate" x-show="open" x-cloak>Dashboard</span>
                </a>
                <a href="{{ route('tools.index') }}" class="tk-tilkit-sidebar-link {{ request()->routeIs('tools.*') ? 'is-active' : '' }}" title="Tools">
                    <span class="tk-tilkit-sidebar-icon">@include('layouts.partials.tilkit-sidebar-icon', ['name' => 'platform'])</span>
                    <span class="truncate" x-show="open" x-cloak>Tools</span>
                </a>
            @endif
            @if (Auth::user()->isSuperAdmin())
                <a href="{{ route('platform.index') }}" class="tk-tilkit-sidebar-link {{ request()->routeIs('platform.*') ? 'is-active' : '' }}" title="Platform">
                    <span class="tk-tilkit-sidebar-icon">@include('layouts.partials.tilkit-sidebar-icon', ['name' => 'platform'])</span>
                    <span class="truncate" x-show="open" x-cloak>Platform</span>
                </a>
            @endif
        @endauth
        <a href="{{ route('user-guide') }}" class="tk-tilkit-sidebar-link {{ request()->routeIs('user-guide') ? 'is-active' : '' }}" title="User guide">
            <span class="tk-tilkit-sidebar-icon">@include('layouts.partials.tilkit-sidebar-icon', ['name' => 'guide'])</span>
            <span class="truncate" x-show="open" x-cloak>User guide</span>
        </a>
    </nav>

    <div class="mt-auto shrink-0 border-t border-tk-line/60 px-2 py-3">
        @auth
            <div class="flex items-center gap-2 rounded-lg px-1 py-1" :class="open ? '' : 'justify-center'">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-tk-soft text-sm font-semibold text-tk-ink" title="{{ Auth::user()->name }}">
                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="min-w-0 flex-1 truncate text-sm font-medium text-tk-ink" x-show="open" x-cloak>{{ Auth::user()->name }}</span>
                <span x-show="open" x-cloak><x-theme-toggle /></span>
            </div>
            <div class="mt-1 space-y-0.5" x-show="open" x-cloak>
                <a href="{{ route('profile.edit') }}" class="tk-tilkit-sidebar-link text-sm">
                    <span class="pl-11">Profile</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="tk-tilkit-sidebar-link w-full text-left text-sm">
                        <span class="pl-11">Log out</span>
                    </button>
                </form>
            </div>
            <div class="mt-2 flex justify-center" x-show="!open">
                <x-theme-toggle />
            </div>
        @else
            <div class="flex flex-col gap-2">
                <x-theme-toggle />
                <a href="{{ route('login') }}" class="tk-btn-ghost text-xs w-full justify-center">Log in</a>
            </div>
        @endauth
    </div>
</aside>
