<x-layouts.site
    :title="$title"
    :meta-description="$metaDescription"
    :og-description="$ogDescription"
    :meta-keywords="$metaKeywords"
    :canonical="route('home')"
    :json-ld="$jsonLd"
>
    <article aria-labelledby="home-hero-heading">
        <header class="py-6 sm:py-12">
            <p class="text-sm font-medium uppercase tracking-wide text-tk-accent">Institutional data collection</p>
            <h1 id="home-hero-heading" class="mt-3 font-display font-bold text-4xl sm:text-5xl leading-tight max-w-2xl text-tk-ink">
                Build collection tools. Analyse field data. Govern with purpose.
            </h1>
            <p class="mt-6 text-lg text-tk-muted max-w-2xl">
                @if ($weatherTool)
                    TILKIT Weather Indicator Log (v4 workbook): capture community signs, run eligibility and weighting, and publish seasonal tercile tallies—field definitions ship with the app, not in the database.
                @else
                    Tilkit maps the full catalogue of data collection points—from XLSForm question types to sensors and channels—into a single platform. Create a tool, become its admin, and publish forms aligned with Kenya DPA governance.
                @endif
            </p>
            <nav class="mt-10 flex flex-wrap gap-3" aria-label="Get started">
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
            </nav>
        </header>

        <section aria-labelledby="home-features-heading" class="pb-6 sm:pb-12">
            <h2 id="home-features-heading" class="sr-only">Platform capabilities</h2>
            <dl class="grid sm:grid-cols-3 gap-5">
                <div class="tk-card p-6">
                    <dt class="font-display font-semibold text-tk-ink">200+ collection points</dt>
                    <dd class="mt-2 text-sm text-tk-muted">Sixteen families from the field reference guide—text, geo, media, clinical, aggregate, and more.</dd>
                </div>
                <div class="tk-card p-6">
                    <dt class="font-display font-semibold text-tk-ink">{{ $weatherTool ? 'Sites & seasons' : 'Tool-scoped admin' }}</dt>
                    <dd class="mt-2 text-sm text-tk-muted">
                        @if ($weatherTool)
                            One shared weather log for the platform. Add sites and seasons per community or study—no new tool per location.
                        @else
                            Every account can spin up a tool; the creator becomes administrator for forms, members, and exports.
                        @endif
                    </dd>
                </div>
                <div class="tk-card p-6">
                    <dt class="font-display font-semibold text-tk-ink">Analysis dashboard</dt>
                    <dd class="mt-2 text-sm text-tk-muted">Trends, breakdowns, CSV export, and typed answers for query-ready reporting.</dd>
                </div>
            </dl>
        </section>

        @include('partials.home-about-tilkit', ['about' => $about, 'weatherTool' => $weatherTool])
    </article>
</x-layouts.site>
