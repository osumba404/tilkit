@if ($sites->isNotEmpty() && ($indicatorLegendTree ?? []) !== [])
    <div
        class="tk-card p-5 space-y-4 mb-6"
        data-tilkit-site-month-indicators
        data-legend='@json($indicatorLegendTree)'
        data-month-labels='@json($seasonMonths ?? [])'
        data-sites='@json($sites->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values())'
        data-load-url-template="{{ route('tools.tilkit.setup.site.month-indicators', [$tool, '__SITE__']) }}"
        data-save-url-template="{{ route('tools.tilkit.setup.site.month-indicators.update', [$tool, '__SITE__']) }}"
        data-draft-scope="tilkit:site-month-indicators:{{ $tool->id }}"
    >
        <div>
            <h3 class="tk-section-title text-base">Site monthly indicator catalogue</h3>
            <p class="text-sm text-tk-muted max-w-3xl mt-2">
                For each monitoring site under <strong>{{ $activeProgram->name }}</strong>, choose which standard legend codes
                (for example <span class="font-mono text-xs">01-ANI-BMG</span>) usually occur in each calendar month.
                Capture only allows signs whose code is listed for the site, season, and collection date.
                Months that share a season are treated as one set of codes when you record data (duplicates across those months count once).
                Selections are kept in this browser immediately and <strong>saved to the server automatically</strong> (including when you leave the page).
            </p>
        </div>

        <div class="flex flex-wrap gap-4 items-end max-w-xl">
            <div class="flex-1 min-w-[12rem]">
                <label class="text-sm font-medium text-tk-ink block mb-1" for="tk-site-indicator-site">Site</label>
                <select id="tk-site-indicator-site" class="tk-input" data-tk-site-indicator-select>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="tk-btn-secondary text-sm" data-tk-site-indicator-save>Save now</button>
        </div>

        <p class="text-xs text-tk-muted" data-tk-site-indicator-status aria-live="polite"></p>

        <div data-tk-site-indicator-seasons class="text-xs text-tk-muted hidden"></div>

        <div class="overflow-x-auto border border-tk-line rounded-lg" data-tk-site-indicator-grid>
            <p class="text-sm text-tk-muted p-4">Loading…</p>
        </div>
    </div>
@elseif ($sites->isNotEmpty())
    <div class="tk-card p-5 mb-6">
        <p class="text-sm text-amber-800">
            Import the indicator legend before assigning monthly codes:
            <code class="text-xs">php artisan tilkit:import-legend</code>
        </p>
    </div>
@endif
