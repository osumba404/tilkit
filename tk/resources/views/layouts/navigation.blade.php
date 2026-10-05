<nav x-data="{ open: false }" class="bg-tk-surface border-b border-tk-line sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <span class="font-serif font-bold text-xl text-tk-ink tracking-tight">Tilkit</span>
                    <span class="hidden sm:inline text-xs text-tk-muted uppercase tracking-wider">Data collection</span>
                </a>
                @auth
                <div class="hidden sm:flex gap-1">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('tools.index')" :active="request()->routeIs('tools.*')">Tools</x-nav-link>
                    <x-nav-link :href="route('reference')" :active="request()->routeIs('reference')">Field reference</x-nav-link>
                    @if (Auth::user()->isSuperAdmin())
                        <x-nav-link :href="route('platform.index')" :active="request()->routeIs('platform.*')">Platform</x-nav-link>
                    @endif
                </div>
                @endauth
            </div>
            <div class="flex items-center gap-3">
                @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm font-medium text-tk-muted hover:text-tk-ink">
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
                @else
                <a href="{{ route('login') }}" class="text-sm text-tk-muted hover:text-tk-accent">Log in</a>
                <a href="{{ route('register') }}" class="tk-btn-primary text-sm py-1.5">Register</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
