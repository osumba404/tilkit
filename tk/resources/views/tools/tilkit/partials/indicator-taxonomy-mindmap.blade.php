@php
    $analysis = $indicatorAnalysisMindmap ?? [
        'root' => 'TILKIT v4 hierarchy → workbook A–D',
        'has_codes' => false,
        'legend_levels' => [],
        'pillars' => [],
        'code_types' => [],
    ];
@endphp

<div class="tk-card p-5 space-y-4 mt-6">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="tk-section-title text-base">Indicator analysis mind map</h3>
        <span class="text-xs text-tk-muted">
            Tap <strong>Legend hierarchy</strong> to drill L1 categories → L2 type lines → L3 sub-types → legend codes.
            Same mapping as the <a href="{{ route('dictionary') }}" class="text-tk-accent underline">dictionary</a>. Drag · scroll to zoom ·
            <strong>Full screen</strong> · <strong>Copy image</strong> (clipboard, no OS screenshot).
        </span>
    </div>

    @if (($analysis['legend_levels'] ?? []) !== [])
        <ol class="text-xs text-tk-muted flex flex-wrap gap-x-4 gap-y-1 list-decimal list-inside max-w-4xl">
            @foreach ($analysis['legend_levels'] as $level)
                <li><span class="text-tk-ink font-medium">{{ $level['label'] }}</span> — {{ $level['detail'] }}</li>
            @endforeach
        </ol>
    @endif

    @if (! ($analysis['has_codes'] ?? false))
        <p class="text-sm text-amber-800">
            Import the master legend to fill the code branches:
            <code class="text-xs">php artisan tilkit:import-legend</code>
        </p>
    @endif

    <div class="tk-mindmap-panel flex flex-col gap-3" data-tk-mindmap-panel>
        <div
            class="flex flex-wrap gap-2 items-center justify-end"
            role="toolbar"
            aria-label="Mind map display and export"
        >
            <span class="text-xs text-tk-muted mr-auto min-h-[1.25rem]" data-tk-mindmap-status aria-live="polite"></span>
            <button type="button" class="tk-btn-secondary text-xs" data-tk-mindmap-copy-image>
                Copy image
            </button>
            <button type="button" class="tk-btn-secondary text-xs" data-tk-mindmap-download-image>
                Download PNG
            </button>
            <button
                type="button"
                class="tk-btn-secondary text-xs"
                data-tk-mindmap-fullscreen
                aria-pressed="false"
            >
                Full screen
            </button>
        </div>

        <div class="tk-mindmap-host flex-1 min-h-0" data-tk-indicator-mindmap role="region" aria-label="Draggable indicator mind map">
            <script type="application/json" data-tk-mindmap-json>{!! json_encode($analysis, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
            <div class="tk-mindmap-mount" data-tk-mindmap-mount>
                <p class="text-sm text-tk-muted p-4">Loading mind map…</p>
            </div>
        </div>
    </div>
</div>
