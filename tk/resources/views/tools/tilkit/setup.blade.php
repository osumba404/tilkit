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

    @include('tools.tilkit.partials.program-setup-wizard')

    <p class="text-sm text-tk-muted mb-6 max-w-3xl">
        Each <strong>program</strong> is an initiative with its own seasons (full calendar, month ranges in order) and monitoring sites.
        Use <strong>Create program</strong> or <strong>Edit</strong> to open the same three-step setup wizard.
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
                            'seasons' => $programSeasons->map(fn ($s) => [
                                'id' => $s->id,
                                'label' => $s->label,
                                'start_month' => (int) $s->start_month,
                                'end_month' => (int) $s->end_month,
                            ])->values()->all(),
                            'sites' => $programSites->map(fn ($site) => [
                                'id' => $site->id,
                                'name' => $site->name,
                            ])->values()->all(),
                        ];
                    @endphp
                    <li class="flex flex-wrap gap-2 items-start justify-between border-b border-tk-line/60 pb-2 last:border-0">
                        <div class="min-w-0">
                            @if ($program->id === $activeProgram->id)
                                <strong>{{ $program->name }}</strong>
                                <span class="text-tk-muted">· active</span>
                            @else
                                <span>{{ $program->name }}</span>
                            @endif
                            <span class="text-tk-muted block text-xs">
                                {{ $programSeasons->count() }} season{{ $programSeasons->count() === 1 ? '' : 's' }}
                                · {{ $programSites->count() }} site{{ $programSites->count() === 1 ? '' : 's' }}
                            </span>
                            @if ($programSeasons->isNotEmpty())
                                <span class="text-tk-muted block text-xs">
                                    {{ $programSeasons->map(fn ($s) => $s->monthRangeLabel())->join(', ') }}
                                </span>
                            @endif
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
                <h4 class="text-xs font-semibold uppercase tracking-wide text-tk-muted mb-2">Seasons</h4>
                <ul class="text-sm space-y-1">
                    @forelse ($seasons as $season)
                        <li>{{ $season->monthRangeLabel() }}</li>
                    @empty
                        <li class="text-tk-muted">No seasons - use Create program or add via a new program wizard.</li>
                    @endforelse
                </ul>
            </div>

            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide text-tk-muted mb-2">Sites</h4>
                <ul class="text-sm space-y-2">
                    @forelse ($sites as $site)
                        <li>
                            <strong>{{ $site->name }}</strong>
                            <span class="text-tk-muted block text-xs">
                                {{ $site->continent ?? '-' }} · {{ $site->country_name ?? $site->country }}
                                @if ($site->county) · {{ $site->county }} @endif
                                @if ($site->ward) · {{ $site->ward }} @endif
                            </span>
                        </li>
                    @empty
                        <li class="text-tk-muted">No sites for this program yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

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
</x-app-layout>
