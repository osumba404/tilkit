@php
    $legendTree = $legendTree ?? [];
    $codeCount = 0;
    foreach ($legendTree as $cat) {
        foreach ($cat['types'] ?? [] as $type) {
            $codeCount += count($type['codes'] ?? []);
        }
    }
@endphp

<div class="tk-card p-5 space-y-4">
    <div class="flex flex-wrap gap-2 items-baseline justify-between">
        <h3 class="tk-section-title text-base">Indicator code legend</h3>
        <span class="text-xs text-tk-muted">{{ $codeCount }} standard codes</span>
    </div>
    <p class="text-sm text-tk-muted max-w-3xl">
        Same hierarchy as
        <a href="{{ route('dictionary') }}" class="text-tk-accent underline">indicator dictionary</a>
        and capture: <strong>category (A)</strong> → <strong>type line (B)</strong> → <strong>legend code</strong> →
        allowed <strong>column D</strong> behaviours. Register unique signs at capture; this list is reference only.
    </p>

    @if ($legendTree === [])
        <p class="text-sm text-amber-800">
            No codes loaded yet. Import the master legend:
            <code class="text-xs">php artisan tilkit:import-legend</code>
        </p>
    @else
        <div class="tk-dictionary-tree border-t border-tk-line/80 pt-4 space-y-2">
            @foreach ($legendTree as $category)
                @php
                    $catCodeCount = collect($category['types'] ?? [])->sum(fn ($t) => count($t['codes'] ?? []));
                @endphp
                <details class="tk-details-panel tk-dict-summary-l0 mb-2" @if($loop->first) open @endif>
                    <summary class="flex flex-wrap items-center gap-2">
                        <span class="text-tk-muted text-xs font-mono">L1 · Cat {{ $category['key'] }}</span>
                        <span>{{ $category['label'] }}</span>
                        <span class="tk-details-panel-count">{{ $catCodeCount }} codes</span>
                    </summary>
                    <div class="tk-details-panel-body">
                        <div class="tk-details-panel-inner tk-dict-nest">
                            @foreach ($category['types'] ?? [] as $type)
                                <details class="tk-details-panel tk-dict-summary-l1">
                                    <summary class="flex flex-wrap items-center gap-2">
                                        <span class="text-tk-muted text-xs font-mono">L2 · {{ $type['key'] }}</span>
                                        <span>{{ $type['label'] }}</span>
                                        <span class="tk-details-panel-count">{{ count($type['codes'] ?? []) }} codes</span>
                                    </summary>
                                    <div class="tk-details-panel-body">
                                        <div class="tk-details-panel-inner tk-dict-nest tk-dict-nest-2">
                                            @foreach ($type['codes'] ?? [] as $codeRow)
                                                @php
                                                    $wb = $codeRow['workbook'] ?? [];
                                                    $behaviours = $codeRow['behaviours'] ?? [];
                                                    $classification = $codeRow['classification_label'] ?? $codeRow['label'] ?? '';
                                                @endphp
                                                <details class="tk-details-panel tk-dict-summary-l2">
                                                    <summary class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                        <span class="text-tk-muted text-xs font-mono">L3</span>
                                                        <span class="font-mono text-tk-ink">{{ $codeRow['code'] }}</span>
                                                        @if ($classification !== '')
                                                            <span class="text-tk-muted font-normal">{{ $classification }}</span>
                                                        @endif
                                                        <span class="tk-details-panel-count">{{ count($behaviours) }} D opts</span>
                                                    </summary>
                                                    <div class="tk-details-panel-body">
                                                        <div class="tk-details-panel-inner tk-dict-nest tk-dict-nest-3 space-y-2 pb-1">
                                                            <details class="tk-details-panel tk-dict-summary-l3">
                                                                <summary>Workbook A — category (column A)</summary>
                                                                <div class="tk-details-panel-body">
                                                                    <div class="tk-details-panel-inner px-3 py-2">
                                                                        <p class="font-mono text-xs text-tk-ink bg-tk-soft/80 rounded px-2 py-1.5">{{ $wb['A'] ?? '—' }}</p>
                                                                    </div>
                                                                </div>
                                                            </details>
                                                            <details class="tk-details-panel tk-dict-summary-l3">
                                                                <summary>Workbook B — type · sub-type (column B)</summary>
                                                                <div class="tk-details-panel-body">
                                                                    <div class="tk-details-panel-inner px-3 py-2">
                                                                        <p class="font-mono text-xs text-tk-ink bg-tk-soft/80 rounded px-2 py-1.5">{{ $wb['B'] ?? '—' }}</p>
                                                                    </div>
                                                                </div>
                                                            </details>
                                                            @if ($behaviours !== [])
                                                                <details class="tk-details-panel tk-dict-summary-l3">
                                                                    <summary>Column D behaviours ({{ count($behaviours) }})</summary>
                                                                    <div class="tk-details-panel-body">
                                                                        <div class="tk-details-panel-inner px-3 py-2">
                                                                            <ul class="space-y-1.5 text-xs text-tk-ink">
                                                                                @foreach ($behaviours as $behaviour)
                                                                                    <li class="rounded-md border border-tk-line/70 bg-tk-soft/50 px-2 py-1.5">{{ $behaviour }}</li>
                                                                                @endforeach
                                                                            </ul>
                                                                            <p class="text-[0.65rem] text-tk-muted mt-2">Capture as: behaviour text <code class="font-mono">| Intensity: …</code></p>
                                                                        </div>
                                                                    </div>
                                                                </details>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </details>
                                            @endforeach
                                        </div>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</div>
