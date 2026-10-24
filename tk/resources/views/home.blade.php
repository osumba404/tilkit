<x-layouts.site title="Tilkit — institutional data collection">
    <section class="py-6 sm:py-12">
        <h1 class="font-display font-bold text-4xl sm:text-5xl leading-tight max-w-2xl text-tk-ink">Build collection tools. Analyse field data. Govern with purpose.</h1>
        @php($weatherTool = \App\Support\Tilkit\WeatherTool::singleToolMode()
            ? (auth()->user() ? \App\Support\Tilkit\WeatherTool::ensurePlatformTool(auth()->user()) : \App\Support\Tilkit\WeatherTool::resolve())
            : null)
        <p class="mt-6 text-lg text-tk-muted max-w-2xl">
            @if ($weatherTool)
                TILKIT Weather Indicator Log (v4 workbook): capture community signs, run eligibility and weighting, and publish seasonal tercile tallies—field definitions ship with the app, not in the database.
            @else
                Tilkit maps the full catalogue of data collection points—from XLSForm question types to sensors and channels—into a single platform. Create a tool, become its admin, and publish forms aligned with Kenya DPA governance.
            @endif
        </p>
        <div class="mt-10 flex flex-wrap gap-3">
            @auth
                @if ($weatherTool)
                    <a href="{{ route('tools.tilkit.overview', $weatherTool) }}" class="tk-btn-primary">Open weather log</a>
                @else
                    <a href="{{ route('tools.create') }}" class="tk-btn-primary">Create a tool</a>
                @endif
                <a href="{{ route('dashboard') }}" class="tk-btn-secondary">Open dashboard</a>
            @else
                <a href="{{ route('register') }}" class="tk-btn-primary">Get started free</a>
                <a href="{{ route('reference') }}" class="tk-btn-secondary">Browse field reference</a>
            @endauth
        </div>
        <dl class="mt-16 grid sm:grid-cols-3 gap-5">
            <div class="tk-card p-6">
                <dt class="font-display font-semibold text-tk-ink">200+ collection points</dt>
                <dd class="mt-2 text-sm text-tk-muted">Sixteen families from the field reference guide — text, geo, media, clinical, aggregate, and more.</dd>
            </div>
            <div class="tk-card p-6">
                <dt class="font-display font-semibold text-tk-ink">{{ $weatherTool ? 'Sites & seasons' : 'Tool-scoped admin' }}</dt>
                <dd class="mt-2 text-sm text-tk-muted">{{ $weatherTool ? 'One shared weather log for the platform. Add sites and seasons per community or study—no new tool per location.' : 'Every account can spin up a tool; the creator becomes administrator for forms, members, and exports.' }}</dd>
            </div>
            <div class="tk-card p-6">
                <dt class="font-display font-semibold text-tk-ink">Analysis dashboard</dt>
                <dd class="mt-2 text-sm text-tk-muted">Trends, breakdowns, CSV export, and typed answers for query-ready reporting.</dd>
            </div>
        </dl>
    </section>
</x-layouts.site>
