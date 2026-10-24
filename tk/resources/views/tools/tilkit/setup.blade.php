<x-app-layout>
    <x-slot name="header">
        <x-page-header title="TILKIT setup" :subtitle="$tool->name">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">TILKIT home</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (session('status'))
        <div class="tk-card p-4 mb-4 text-sm">{{ session('status') }}</div>
    @endif

    @include('tools.tilkit.partials.subnav')

    <p class="text-sm text-tk-muted mb-6 max-w-3xl">
        Use one <strong>program</strong> per initiative or geography (for example East Africa 2016–2021, Ghana pilot 2019–2026, or Kenya research 2024).
        Sites and seasons belong to the active program. Continents and countries are loaded live from a public API (not stored in our database).
    </p>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="tk-card p-5 space-y-4">
            <h3 class="tk-section-title text-base">Programs / initiatives</h3>
            <form method="POST" action="{{ route('tools.tilkit.setup.program', $tool) }}" class="space-y-2">
                @csrf
                <input name="name" required placeholder="Program name (required)" class="tk-input" maxlength="160">
                <textarea name="description" rows="2" placeholder="Optional note (years, funders, scope…)" class="tk-input"></textarea>
                <button class="tk-btn-primary w-full">Create program</button>
            </form>
            <ul class="text-sm space-y-1">
                @foreach ($programs as $program)
                    <li>
                        @if ($program->id === $activeProgram->id)
                            <strong>{{ $program->name }}</strong> <span class="text-tk-muted">(active)</span>
                        @else
                            {{ $program->name }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="tk-card p-5 space-y-4">
            <h3 class="tk-section-title text-base">Seasons · {{ $activeProgram->name }}</h3>
            <form method="POST" action="{{ route('tools.tilkit.setup.season', $tool) }}" class="space-y-2">
                @csrf
                <input name="label" required placeholder="Season label (e.g. OND 2026, MAM 2019)" class="tk-input" maxlength="120">
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="starts_on" class="tk-input" aria-label="Season starts">
                    <input type="date" name="ends_on" class="tk-input" aria-label="Season ends">
                </div>
                <button class="tk-btn-primary w-full">Add season</button>
            </form>
            <ul class="text-sm space-y-1">
                @forelse ($seasons as $season)
                    <li>{{ $season->label }}</li>
                @empty
                    <li class="text-tk-muted">No seasons yet for this program.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="tk-card p-5 space-y-4">
            <h3 class="tk-section-title text-base">Sites · {{ $activeProgram->name }}</h3>
            <form id="tilkit-site-form" method="POST" action="{{ route('tools.tilkit.setup.site', $tool) }}" class="space-y-2">
                @csrf
                <div class="grid sm:grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-tk-muted block mb-1" for="site-continent">Continent</label>
                        <select id="site-continent" name="continent" required class="tk-input">
                            <option value="">Loading continents…</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-tk-muted block mb-1" for="site-country">Country</label>
                        <select id="site-country" name="country" required class="tk-input" disabled>
                            <option value="">Select continent first</option>
                        </select>
                    </div>
                </div>
                <input name="county" placeholder="County / district / region" class="tk-input">
                <input name="ward" placeholder="Ward / sub-county / locality (optional)" class="tk-input">
                <input name="name" required placeholder="Site name (required)" class="tk-input">
                <input name="primary_language" placeholder="Primary language (optional)" class="tk-input">
                <button class="tk-btn-primary w-full">Add site</button>
            </form>
            <ul class="text-sm space-y-2">
                @forelse ($sites as $site)
                    <li>
                        <strong>{{ $site->name }}</strong>
                        <span class="text-tk-muted block text-xs">
                            {{ $site->continent ?? '—' }} · {{ $site->country_name ?? $site->country }}
                            @if ($site->county) · {{ $site->county }} @endif
                            @if ($site->ward) · {{ $site->ward }} @endif
                        </span>
                    </li>
                @empty
                    <li class="text-tk-muted">No sites yet for this program.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="tk-card p-5 space-y-4">
        <div class="flex flex-wrap gap-2 items-baseline justify-between">
            <h3 class="tk-section-title text-base">Indicator code legend</h3>
            <span class="text-xs text-tk-muted">{{ $legendCodeCount }} standard codes</span>
        </div>
        <p class="text-sm text-tk-muted max-w-3xl">
            Reference only — you do not register indicators here. During capture (FGD / records), each sign gets a
            <strong>Unique Indicator ID</strong> from this legend. Codes are grouped by v4 category and type
            (01-PLT, 01-ANI, 02-ATM, …).
        </p>
        @if ($legendGroups === [])
            <p class="text-sm text-amber-800">
                No codes loaded yet. An administrator can import the master legend:
                <code class="text-xs">php artisan tilkit:import-legend</code>
            </p>
        @else
            <div class="space-y-3">
                @foreach ($legendGroups as $category)
                    <x-tilkit.details-panel :open="$loop->first" class="mb-0">
                        <x-slot:summary>
                            <span class="min-w-0 flex-1 text-left">
                                {{ $category['key'] }} · {{ $category['label'] }}
                            </span>
                            <span class="tk-details-panel-count">
                                {{ collect($category['types'])->sum(fn ($t) => count($t['codes'])) }} codes
                            </span>
                        </x-slot:summary>
                        <div class="space-y-4 px-1 pb-1 pt-3">
                            @foreach ($category['types'] as $type)
                                <div>
                                    <h4 class="text-xs font-semibold uppercase tracking-wide text-tk-muted mb-2">
                                        {{ $type['key'] }} · {{ $type['label'] }}
                                    </h4>
                                    <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-1 text-sm">
                                        @foreach ($type['codes'] as $codeRow)
                                            <li class="font-mono text-xs leading-relaxed">
                                                <span class="text-tk-ink">{{ $codeRow['code'] }}</span>
                                                @if ($codeRow['label'])
                                                    <span class="text-tk-muted font-sans"> — {{ $codeRow['label'] }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </x-tilkit.details-panel>
                @endforeach
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            (function () {
                const continentsUrl = @json($geographyContinentsUrl);
                const countriesUrl = @json($geographyCountriesUrl);
                const continentSelect = document.getElementById('site-continent');
                const countrySelect = document.getElementById('site-country');

                async function loadContinents() {
                    continentSelect.innerHTML = '<option value="">Loading…</option>';
                    try {
                        const res = await fetch(continentsUrl, { headers: { Accept: 'application/json' } });
                        const json = await res.json();
                        const items = json.data || [];
                        continentSelect.innerHTML = '<option value="">Select continent</option>';
                        items.forEach(function (row) {
                            const opt = document.createElement('option');
                            opt.value = row.name;
                            opt.textContent = row.name;
                            continentSelect.appendChild(opt);
                        });
                    } catch (e) {
                        continentSelect.innerHTML = '<option value="">Could not load continents</option>';
                    }
                }

                async function loadCountries(continent) {
                    countrySelect.disabled = true;
                    countrySelect.innerHTML = '<option value="">Loading…</option>';
                    if (!continent) {
                        countrySelect.innerHTML = '<option value="">Select continent first</option>';
                        return;
                    }
                    try {
                        const url = countriesUrl + '?continent=' + encodeURIComponent(continent);
                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                        const json = await res.json();
                        const items = json.data || [];
                        countrySelect.innerHTML = '<option value="">Select country</option>';
                        items.forEach(function (row) {
                            const opt = document.createElement('option');
                            opt.value = row.code;
                            opt.textContent = row.name;
                            countrySelect.appendChild(opt);
                        });
                        countrySelect.disabled = items.length === 0;
                    } catch (e) {
                        countrySelect.innerHTML = '<option value="">Could not load countries</option>';
                    }
                }

                continentSelect?.addEventListener('change', function () {
                    loadCountries(this.value);
                });

                loadContinents();
            })();
        </script>
    @endpush
</x-app-layout>
