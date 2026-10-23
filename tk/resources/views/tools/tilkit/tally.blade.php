@php
    $tercileLabel = fn (?int $t) => match ($t) {
        3 => 'Above normal',
        2 => 'Normal',
        1 => 'Below normal',
        default => '—',
    };
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Seasonal tercile tally" :subtitle="$tool->name">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.overview', $tool) }}" class="tk-btn-secondary">TILKIT home</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('tools.tilkit.tally', $tool) }}" class="tk-card p-5 flex flex-wrap gap-4 items-end mb-6">
        @csrf
        <div>
            <label class="text-xs text-tk-muted block mb-1">Site</label>
            <select name="site_id" class="tk-input" required>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}" @selected($siteId == $site->id)>{{ $site->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-tk-muted block mb-1">Season</label>
            <select name="season_id" class="tk-input" required>
                @foreach ($seasons as $season)
                    <option value="{{ $season->id }}" @selected($seasonId == $season->id)>{{ $season->label }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="publish" value="1"> Publish snapshot
        </label>
        <button type="submit" class="tk-btn-primary">Run tally</button>
    </form>

    @if ($result)
        <div class="tk-card p-6 space-y-4">
            <p class="text-sm">
                <strong>{{ $result->countedRows }}</strong> counted rows · Reconciliation: <strong>{{ $result->reconciliation }}</strong>
                @if ($result->isTieWeighted)
                    · <span class="text-amber-700">Weighted tie reported</span>
                @endif
            </p>
            <table class="w-full text-sm">
                <thead class="text-xs text-tk-muted uppercase">
                    <tr>
                        <th class="text-left p-2">Tercile</th>
                        <th class="text-right p-2">Simple %</th>
                        <th class="text-right p-2">Weighted %</th>
                        <th class="text-right p-2">Priority-adj. %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([3, 2, 1] as $k)
                        <tr class="border-t border-tk-line">
                            <td class="p-2">{{ $tercileLabel($k) }}</td>
                            <td class="p-2 text-right">{{ $result->simple[$k]['percent'] ?? 0 }}%</td>
                            <td class="p-2 text-right">{{ $result->weighted[$k]['percent'] ?? 0 }}%</td>
                            <td class="p-2 text-right">{{ $result->priorityAdjusted[$k]['percent'] ?? 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($result->onsetEarliestMean)
                <p class="text-xs text-tk-muted">Mean onset window: {{ $result->onsetEarliestMean }} – {{ $result->onsetLatestMean ?? '—' }}</p>
            @endif
            @if ($result->exclusionCounts !== [])
                <div class="text-xs">
                    <strong>Exclusions:</strong>
                    @foreach ($result->exclusionCounts as $reason => $count)
                        {{ $reason }} ({{ $count }})@if(!$loop->last), @endif
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($snapshots->isNotEmpty())
        <div class="tk-card p-5 mt-6">
            <h3 class="tk-section-title text-base mb-2">Published snapshots</h3>
            <ul class="text-sm space-y-1">
                @foreach ($snapshots as $snap)
                    <li>{{ $snap->published_at?->format('Y-m-d H:i') }} · {{ $snap->reconciliation }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-app-layout>
