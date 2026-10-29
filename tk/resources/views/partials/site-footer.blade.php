<footer class="border-t border-tk-line/80 bg-tk-surface/60 backdrop-blur-sm mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-12">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-5">
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="inline-flex items-center gap-3 group">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-tk-accent text-white text-sm font-bold shadow-tk-sm" aria-hidden="true">T</span>
                    <span class="flex flex-col leading-tight">
                        <span class="tk-nav-brand">{{ config('app.name', 'Tilkit') }}</span>
                        <span class="tk-nav-tagline">Data collection</span>
                    </span>
                </a>
                <p class="mt-4 text-sm text-tk-muted leading-relaxed max-w-md">
                    Institutional field data, TILKIT weather indicator logs, and governed exports-aligned with community forecasting and Kenya DPA practice.
                </p>
            </div>

            <nav class="lg:col-span-3" aria-label="Site">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-tk-ink">Site</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-tk-muted hover:text-tk-accent transition-colors">Home</a></li>
                    <li><a href="{{ route('home') }}#about-tilkit" class="text-tk-muted hover:text-tk-accent transition-colors">About TILKIT</a></li>
                    <li><a href="{{ route('user-guide') }}" class="text-tk-muted hover:text-tk-accent transition-colors">User guide</a></li>
                </ul>
            </nav>

            <nav class="lg:col-span-4" aria-label="Account">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-tk-ink">Account</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="text-tk-muted hover:text-tk-accent transition-colors">Dashboard</a></li>
                        @if ($tilkitWeatherTool ?? null)
                            <li><a href="{{ route('tools.tilkit.overview', $tilkitWeatherTool) }}" class="text-tk-muted hover:text-tk-accent transition-colors">Weather log</a></li>
                        @endif
                        <li><a href="{{ route('profile.edit') }}" class="text-tk-muted hover:text-tk-accent transition-colors">Profile</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="text-tk-muted hover:text-tk-accent transition-colors">Log in</a></li>
                        <li><a href="{{ route('register') }}" class="text-tk-muted hover:text-tk-accent transition-colors">Register</a></li>
                    @endauth
                </ul>
            </nav>
        </div>

        <div class="mt-10 pt-6 border-t border-tk-line/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-tk-muted">
            <p>&copy; {{ now()->year }} {{ config('app.name', 'Tilkit') }}. All rights reserved.</p>
            <p>TILKIT - Traditional Indigenous and Local Knowledge Tool</p>
        </div>
    </div>
</footer>
