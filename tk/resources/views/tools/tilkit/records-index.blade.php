<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Indicator records" :subtitle="$tool->name">
            <x-slot:actions>
                <a href="{{ route('tools.tilkit.records.create', $tool) }}" class="tk-btn-primary">New record</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @include('tools.tilkit.partials.subnav', ['tilkitNav' => 'records'])

    <form id="tilkit-records-filter" method="GET" action="{{ route('tools.tilkit.records.index', $tool) }}" class="tk-card p-4 mb-6 flex flex-wrap gap-3 items-end text-sm" data-records-url="{{ route('tools.tilkit.records.index', $tool) }}">
        <div class="min-w-[10rem]">
            <label class="text-xs text-tk-muted block mb-1" for="records-filter-site">Site</label>
            <select id="records-filter-site" name="site_id" class="tk-input py-2 text-sm">
                <option value="">All sites</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}" @selected(request('site_id') == $site->id)>{{ $site->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[10rem]">
            <label class="text-xs text-tk-muted block mb-1" for="records-filter-season">Season</label>
            <select id="records-filter-season" name="season_id" class="tk-input py-2 text-sm">
                <option value="">All seasons</option>
                @foreach ($seasons as $season)
                    <option value="{{ $season->id }}" @selected(request('season_id') == $season->id)>{{ $season->monthRangeLabel() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[12rem]">
            <label class="text-xs text-tk-muted block mb-1" for="records-filter-q">Indicator ID</label>
            <input id="records-filter-q" type="search" name="q" value="{{ request('q') }}" placeholder="e.g. 01-ANI-FRO" class="tk-input py-2 text-sm" autocomplete="off">
        </div>
    </form>

    <div id="tilkit-records-results" class="transition-opacity duration-150">
        @include('tools.tilkit.partials.records-results')
    </div>

    @push('scripts')
        <script>
            (function () {
                const form = document.getElementById('tilkit-records-filter');
                const results = document.getElementById('tilkit-records-results');
                const tableHost = document.getElementById('tilkit-records-table');
                if (!form || !results) {
                    return;
                }

                const baseUrl = form.dataset.recordsUrl || form.action;
                const partialTable = 'table';
                const partialResults = 'results';
                let debounceTimer = null;
                let fetchController = null;
                let requestId = 0;

                function paramsFromForm() {
                    const data = new FormData(form);
                    const params = new URLSearchParams();
                    data.forEach(function (value, key) {
                        if (String(value).trim() !== '') {
                            params.set(key, value);
                        }
                    });
                    return params;
                }

                function syncUrl(params) {
                    const qs = params.toString();
                    const next = qs ? baseUrl + '?' + qs : baseUrl;
                    window.history.replaceState(null, '', next);
                }

                function afterResultsSwap() {
                    if (typeof window.tkInitTilkitGpsViews === 'function') {
                        window.tkInitTilkitGpsViews(results);
                    }
                }

                function loadResults(params, partialMode) {
                    const qs = params.toString();
                    const url = qs ? baseUrl + '?' + qs : baseUrl;
                    syncUrl(params);

                    const partial = partialMode === partialResults ? partialResults : partialTable;
                    const busyEl = partial === partialTable && tableHost ? tableHost : results;

                    if (fetchController) {
                        fetchController.abort();
                    }
                    fetchController = new AbortController();
                    const currentId = ++requestId;

                    busyEl.classList.add('opacity-60', 'pointer-events-none');
                    busyEl.setAttribute('aria-busy', 'true');

                    fetch(url, {
                        method: 'GET',
                        headers: {
                            Accept: 'text/html',
                            'X-Tilkit-Records-Partial': partial,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        signal: fetchController.signal,
                    })
                        .then(function (res) {
                            if (!res.ok) {
                                throw new Error('Records filter failed');
                            }
                            return res.text();
                        })
                        .then(function (html) {
                            if (currentId !== requestId) {
                                return;
                            }
                            if (partial === partialResults) {
                                results.innerHTML = html;
                                afterResultsSwap();
                            } else if (tableHost) {
                                tableHost.innerHTML = html;
                            }
                        })
                        .catch(function (err) {
                            if (err.name === 'AbortError') {
                                return;
                            }
                        })
                        .finally(function () {
                            if (currentId !== requestId) {
                                return;
                            }
                            busyEl.classList.remove('opacity-60', 'pointer-events-none');
                            busyEl.removeAttribute('aria-busy');
                        });
                }

                function scheduleLoad(partialMode) {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = window.setTimeout(function () {
                        loadResults(paramsFromForm(), partialMode || partialTable);
                    }, 320);
                }

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    scheduleLoad();
                });

                form.querySelector('[name="q"]')?.addEventListener('input', function () {
                    scheduleLoad(partialTable);
                });
                form.querySelectorAll('select').forEach(function (select) {
                    select.addEventListener('change', function () {
                        window.clearTimeout(debounceTimer);
                        loadResults(paramsFromForm(), partialResults);
                    });
                });

                results.addEventListener('click', function (e) {
                    const pagination = e.target.closest('[data-tilkit-records-pagination]');
                    if (!pagination) {
                        return;
                    }
                    const link = e.target.closest('a');
                    if (!link || !pagination.contains(link)) {
                        return;
                    }
                    e.preventDefault();
                    const url = new URL(link.href, window.location.origin);
                    loadResults(url.searchParams, partialTable);
                });
            })();
        </script>
    @endpush
</x-app-layout>
