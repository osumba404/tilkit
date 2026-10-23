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

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="tk-card p-5 space-y-4">
            <h3 class="tk-section-title text-base">Sites</h3>
            <form method="POST" action="{{ route('tools.tilkit.setup.site', $tool) }}" class="space-y-2">
                @csrf
                <input name="name" required placeholder="Site name" class="tk-input">
                <input name="ward" placeholder="Ward" class="tk-input">
                <input name="county" placeholder="County" class="tk-input">
                <button class="tk-btn-primary w-full">Add site</button>
            </form>
            <ul class="text-sm space-y-1">
                @foreach ($sites as $site)
                    <li>{{ $site->name }} @if($site->county)<span class="text-tk-muted">· {{ $site->county }}</span>@endif</li>
                @endforeach
            </ul>
        </div>

        <div class="tk-card p-5 space-y-4">
            <h3 class="tk-section-title text-base">Seasons</h3>
            <form method="POST" action="{{ route('tools.tilkit.setup.season', $tool) }}" class="space-y-2">
                @csrf
                <input name="label" required placeholder="e.g. OND 2026" class="tk-input">
                <button class="tk-btn-primary w-full">Add season</button>
            </form>
            <ul class="text-sm space-y-1">
                @foreach ($seasons as $season)
                    <li>{{ $season->label }}</li>
                @endforeach
            </ul>
        </div>

        <div class="tk-card p-5 space-y-4">
            <h3 class="tk-section-title text-base">Indicators</h3>
            <form method="POST" action="{{ route('tools.tilkit.setup.indicator', $tool) }}" class="space-y-2">
                @csrf
                <input name="indicator_uid" required placeholder="01-ANI-FRO-001" class="tk-input font-mono text-sm">
                <select name="indicator_code_id" required class="tk-input">
                    <option value="">Code prefix…</option>
                    @foreach ($codes as $code)
                        <option value="{{ $code->id }}">{{ $code->code }} — {{ $code->label }}</option>
                    @endforeach
                </select>
                <input name="local_name" placeholder="Local name" class="tk-input">
                <button class="tk-btn-primary w-full">Register indicator</button>
            </form>
            <p class="text-xs text-tk-muted">Import full legend: <code>php artisan tilkit:import-legend</code></p>
            <ul class="text-sm space-y-1 max-h-48 overflow-y-auto">
                @foreach ($indicators as $ind)
                    <li class="font-mono text-xs">{{ $ind->indicator_uid }} <span class="text-tk-muted">{{ $ind->local_name }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>
</x-app-layout>
