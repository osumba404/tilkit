<x-app-layout>
    @include('tools.tilkit.partials.subnav')

    @include('tools.tilkit.partials.program-setup-wizard')

    <p class="text-sm text-tk-muted mb-6 max-w-3xl">
        Each <strong>program</strong> is an initiative. For each program you add <strong>sites</strong> (study areas), define
        <strong>seasons per site</strong> (month ranges can differ by location), outline each site on the map, then assign
        <strong>monthly indicator codes</strong> per site below. Use <strong>Create program</strong> or <strong>Edit</strong> for the four-step wizard.
    </p>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="tk-card p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="tk-section-title text-base">Programs / initiatives</h3>
                <button type="button" class="tk-btn-primary" data-tilkit-wizard-open="create">
                    Create program
                </button>
            </div>
            <ul class="text-sm space-y-2">
                @forelse ($programs as $program)
                    @php
                        $programSites = ($sitesByProgram[$program->id] ?? collect());
                        $programSeasons = ($seasonsByProgram[$program->id] ?? collect());
                        $wizardProgramPayload = [
                            'id' => $program->id,
                            'name' => $program->name,
                            'description' => $program->description ?? '',
                            'sites' => $programSites->map(function ($site) use ($programSeasons) {
                                $siteSeasons = $programSeasons->where('site_id', $site->id);

                                return [
                                    'id' => $site->id,
                                    'name' => $site->name,
                                    'continent' => $site->continent,
                                    'country' => $site->country,
                                    'country_name' => $site->country_name,
                                    'county' => $site->county,
                                    'ward' => $site->ward,
                                    'latitude' => $site->latitude,
                                    'longitude' => $site->longitude,
                                    'study_area_geojson' => $site->study_area_geojson,
                                    'has_study_area' => $site->hasStudyAreaMapped(),
                                    'seasons' => $siteSeasons->map(fn ($s) => [
                                        'id' => $s->id,
                                        'label' => $s->label,
                                        'start_month' => (int) $s->start_month,
                                        'end_month' => (int) $s->end_month,
                                    ])->values()->all(),
                                ];
                            })->values()->all(),
                        ];
                    @endphp
                    <li class="flex flex-wrap gap-2 items-start justify-between border-b border-tk-line/60 pb-2 last:border-0">
                        <div class="min-w-0">
                            @if ($program->id === $activeProgram->id)
                                <strong>{{ $program->name }}</strong>
                                <span class="tk-program-active-label">
                                    <span aria-hidden="true">·</span>
                                    <span>active</span>
                                    <svg class="tk-program-active-badge" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Active program">
                                        <path fill="currentColor" d="M11 0C4.925 0 0 4.925 0 11s4.925 11 11 11 11-4.925 11-11S17.075 0 11 0Z"/>
                                        <path fill="#fff" d="M9.47 14.53 6.58 11.64l1.06-1.06 1.83 1.83 4.24-4.24 1.06 1.06-5.3 5.3Z"/>
                                    </svg>
                                </span>
                            @else
                                <span>{{ $program->name }}</span>
                            @endif
                            <span class="text-tk-muted block text-xs">
                                {{ $programSites->count() }} site{{ $programSites->count() === 1 ? '' : 's' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="tk-btn-secondary text-xs py-1 px-2"
                                data-tilkit-wizard-open="edit"
                                data-program='@json($wizardProgramPayload)'
                            >
                                Edit
                            </button>
                            @if ($program->id !== $activeProgram->id)
                                <form method="POST" action="{{ route('tools.tilkit.program.switch', $tool) }}">
                                    @csrf
                                    <input type="hidden" name="program_id" value="{{ $program->id }}">
                                    <button type="submit" class="tk-btn-secondary text-xs py-1 px-2">Set active</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-tk-muted">No programs yet. Click Create program to begin.</li>
                @endforelse
            </ul>
        </div>

        <div class="tk-card p-5 space-y-4">
            <h3 class="tk-section-title text-base">Active program · {{ $activeProgram->name }}</h3>

            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide text-tk-muted mb-2">Sites & seasons</h4>
                <ul class="text-sm space-y-3">
                    @forelse ($sites as $site)
                        @php
                            $siteSeasons = $seasons->where('site_id', $site->id);
                        @endphp
                        <li>
                            <strong>{{ $site->name }}</strong>
                            <span class="text-tk-muted block text-xs">
                                {{ $site->continent ?? '-' }} · {{ $site->country_name ?? $site->country }}
                                @if ($site->county) · {{ $site->county }} @endif
                                @if ($site->ward) · {{ $site->ward }} @endif
                                @if ($site->hasStudyAreaMapped())
                                    · map set
                                @endif
                            </span>
                            <ul class="text-xs text-tk-muted mt-1 space-y-0.5">
                                @forelse ($siteSeasons as $season)
                                    <li>{{ $season->monthRangeLabel() }}</li>
                                @empty
                                    <li>No seasons for this site yet.</li>
                                @endforelse
                            </ul>
                        </li>
                    @empty
                        <li class="text-tk-muted">No sites for this program yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @include('tools.tilkit.partials.site-month-indicators')

    <div class="tk-card p-5 space-y-4">
        <div class="flex flex-wrap gap-2 items-baseline justify-between">
            <h3 class="tk-section-title text-base">Indicator code legend</h3>
            <span class="text-xs text-tk-muted">{{ $legendCodeCount }} standard codes</span>
        </div>
        <p class="text-sm text-tk-muted max-w-3xl">
            Reference only - you do not register indicators here. During capture (FGD / records), each sign gets a
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
                                                    <span class="text-tk-muted font-sans"> - {{ $codeRow['label'] }}</span>
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

    @include('tools.tilkit.partials.indicator-taxonomy-mindmap')
</x-app-layout>
