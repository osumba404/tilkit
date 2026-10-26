<nav x-data="{ open: false }" class="tk-nav-shell sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-[4.25rem]">
            <div class="flex items-center gap-8">
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="flex items-center gap-3 group" aria-label="Tilkit home">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-tk-accent text-white text-sm font-bold shadow-tk-sm transition-transform duration-300 group-hover:scale-105 group-hover:shadow-tk-md" aria-hidden="true">T</span>
                    <span class="flex flex-col leading-tight">
                        <span class="tk-nav-brand">Tilkit</span>
                        <span class="tk-nav-tagline">Data collection</span>
                    </span>
                </a>
                <div class="hidden sm:flex gap-1">
                @auth
                    @if ($tilkitWeatherTool ?? null)
                        <x-nav-link :href="route('tools.tilkit.overview', $tilkitWeatherTool)" :active="request()->routeIs('tools.tilkit.*') || request()->routeIs('dashboard')">Weather log</x-nav-link>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                        <x-nav-link :href="route('tools.index')" :active="request()->routeIs('tools.*')">Tools</x-nav-link>
                    @endif
                    <x-nav-link :href="route('platform.datasets.index')" :active="request()->routeIs('platform.datasets.*')">My datasets</x-nav-link>
                    <x-nav-link :href="route('reference')" :active="request()->routeIs('reference')">Field reference</x-nav-link>
                    @if (Auth::user()->isSuperAdmin())
                        <x-nav-link :href="route('platform.index')" :active="request()->routeIs('platform.*')">Platform</x-nav-link>
                    @endif
                @else
                    <x-nav-link :href="route('reference')" :active="request()->routeIs('reference')">Field reference</x-nav-link>
                @endauth
                    <x-nav-link :href="route('user-guide')" :active="request()->routeIs('user-guide')">User guide</x-nav-link>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @auth
                <div class="flex items-center gap-1">
                    <x-dropdown align="right" width="48" contentClasses="tk-dropdown-panel">
                        <x-slot name="trigger">
                            <button type="button" class="tk-btn-ghost text-sm px-3 py-2 rounded-lg border border-transparent hover:border-tk-line">
                                {{ Auth::user()->name }}
                            </button>
                        </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Log out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                    </x-dropdown>
                    <x-theme-toggle />
                </div>
                @else
                <x-theme-toggle />
                <a href="{{ route('login') }}" class="tk-btn-ghost text-sm px-3">Log in</a>
                <a href="{{ route('register') }}" class="tk-btn-primary text-sm py-2">Register</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
