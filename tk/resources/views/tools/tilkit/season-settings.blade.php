<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Season settings" :subtitle="$tool->name.' · Weighting Options'">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">TILKIT home</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (session('status'))
        <div class="tk-card p-4 mb-4 text-sm">{{ session('status') }}</div>
    @endif

    <form method="GET" class="tk-card p-4 flex gap-4 mb-6">
        <select name="site_id" class="tk-input" onchange="this.form.submit()">
            @foreach ($sites as $site)
                <option value="{{ $site->id }}" @selected($siteId == $site->id)>{{ $site->name }}</option>
            @endforeach
        </select>
        <select name="season_id" class="tk-input" onchange="this.form.submit()">
            @foreach ($seasons as $season)
                <option value="{{ $season->id }}" @selected($seasonId == $season->id)>{{ $season->label }}</option>
            @endforeach
        </select>
    </form>

    @if ($settings)
        <form method="POST" action="{{ route('tools.tilkit.settings.update', $tool) }}" class="tk-card p-6 space-y-4 max-w-lg">
            @csrf
            <input type="hidden" name="site_id" value="{{ $siteId }}">
            <input type="hidden" name="season_id" value="{{ $seasonId }}">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="use_prior_multiplier" value="1" @checked($settings->use_prior_multiplier)>
                Apply global literature prior (rating-based indicators only)
            </label>
            <div>
                <label class="text-xs text-tk-muted">KMSA county outlook tercile (DJ)</label>
                <select name="kmsa_outlook_tercile" class="tk-input">
                    <option value="">-</option>
                    @foreach ([3 => 'Above normal', 2 => 'Normal', 1 => 'Below normal'] as $v => $l)
                        <option value="{{ $v }}" @selected($settings->kmsa_outlook_tercile == $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-tk-muted">NDMA drought phase (DL)</label>
                <input type="text" name="ndma_drought_phase" value="{{ $settings->ndma_drought_phase }}" class="tk-input" placeholder="Normal, Alert, …">
            </div>
            <div>
                <label class="text-xs text-tk-muted">NDMA VCI3M ward value (CR ingest)</label>
                <input type="number" step="0.01" name="ndma_vci3m" value="{{ $settings->ndma_vci3m }}" class="tk-input" min="0" max="100">
            </div>
            <button class="tk-btn-primary">Save &amp; recalculate records</button>
        </form>
    @endif
</x-app-layout>
