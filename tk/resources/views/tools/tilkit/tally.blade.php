@php
    $tercileLabel = fn (?int $t) => match ($t) {
        3 => 'Above normal',
        2 => 'Normal',
        1 => 'Below normal',
        default => '-',
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

    @include('tools.tilkit.partials.subnav', ['tilkitNav' => 'tally'])

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
            @php
                $onset = $result->timingSummary['onset'] ?? [];
                $cessation = $result->timingSummary['cessation'] ?? [];
                $distLabel = fn (?int $d) => match ($d) {
                    3 => 'Good',
                    2 => 'Fair',
                    1 => 'Poor',
                    0 => 'Not sure',
                    default => '-',
                };
            @endphp
            @if (! empty($onset['earliest_mean_simple']))
                <div class="text-xs text-tk-muted space-y-1">
                    <p>
                        Onset window (simple mean): {{ $onset['earliest_mean_simple'] }} – {{ $onset['latest_mean_simple'] ?? '-' }}
                        @if (! empty($onset['earliest_mean_weighted']))
                            · weighted: {{ $onset['earliest_mean_weighted'] }} – {{ $onset['latest_mean_weighted'] ?? '-' }}
                        @endif
                    </p>
                    @if (! empty($onset['single_earliest']))
                        <p>Earliest / latest single sign: {{ $onset['single_earliest'] }} – {{ $onset['single_latest'] ?? '-' }} ({{ $onset['signs_with_dates'] ?? 0 }} signs with dates)</p>
                    @endif
                    @if (! empty($cessation['earliest_mean_simple']))
                        <p>
                            Cessation window (simple): {{ $cessation['earliest_mean_simple'] }} – {{ $cessation['latest_mean_simple'] ?? '-' }}
                            @if (! empty($cessation['earliest_mean_weighted']))
                                · weighted: {{ $cessation['earliest_mean_weighted'] }} – {{ $cessation['latest_mean_weighted'] ?? '-' }}
                            @endif
                        </p>
                    @endif
                </div>
            @endif
            @if ($result->distributionOutlook)
                <div class="text-sm border-t border-tk-line pt-3">
                    <h4 class="font-semibold mb-2">Distribution outlook (column T)</h4>
                    <table class="w-full text-xs">
                        <thead class="text-tk-muted uppercase">
                            <tr>
                                <th class="text-left p-1">Rating</th>
                                <th class="text-right p-1">Simple %</th>
                                <th class="text-right p-1">Weighted %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ([3, 2, 1, 0] as $k)
                                <tr class="border-t border-tk-line">
                                    <td class="p-1">{{ $distLabel($k) }}</td>
                                    <td class="p-1 text-right">{{ $result->distributionOutlook['simple'][$k]['percent'] ?? 0 }}%</td>
                                    <td class="p-1 text-right">{{ $result->distributionOutlook['weighted'][$k]['percent'] ?? 0 }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-xs text-tk-muted mt-1">
                        Leading: simple {{ $distLabel($result->distributionOutlook['leading_simple'] ?? null) }},
                        weighted {{ $distLabel($result->distributionOutlook['leading_weighted'] ?? null) }}
                    </p>
                </div>
            @endif
            <div class="border-t border-tk-line pt-4 text-sm space-y-1">
                <h4 class="font-semibold">Data check (matches Tally tab)</h4>
                <p>Rows logged with ID: {{ $result->rowsLoggedWithId }}</p>
                <p>Counted in tally: {{ $result->countedRows }}</p>
                <p>Counted without weight (rating 0): {{ $result->countedWithoutWeight }}</p>
                <p>Leading (simple): {{ $result->leadingSimpleLabel }}</p>
                <p>Leading (weighted): {{ $result->leadingWeightedLabel }}</p>
                <p>Simple and weighted agree? {{ $result->simpleWeightedAgreeLabel }}</p>
            </div>
            @if ($result->exclusionCounts !== [])
                <div class="text-xs">
                    <strong>Exclusions:</strong>
                    @foreach ($result->exclusionCounts as $reason => $count)
                        {{ $reason }} ({{ $count }})@if(!$loop->last), @endif
                    @endforeach
                </div>
            @endif
            @if (! empty($result->integration))
                <div class="border-t border-tk-line pt-4 text-sm space-y-2">
                    <h4 class="font-semibold">v4 integration (KMSA / NDMA / triggers)</h4>
                    <p>Mean confidence (DQ): {{ $result->integration['mean_confidence_dq'] ?? '-' }}</p>
                    <p>Community vegetation index: {{ $result->integration['vegetation_index'] ?? '-' }}</p>
                    <p>KMSA agreement (DK): {{ $result->integration['kmsa_agreement'] ?? '-' }}</p>
                    <p class="text-xs text-tk-muted">{{ $result->integration['integration_note'] ?? '' }}</p>
                    @if (! empty($result->integration['triggers']))
                        <ul class="list-disc list-inside text-amber-800">
                            @foreach ($result->integration['triggers'] as $trigger)
                                <li>{{ $trigger }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
            <form method="POST" action="{{ route('tools.tilkit.integration.publish', $tool) }}" class="border-t border-tk-line pt-4 space-y-3 max-w-xl">
                @csrf
                <input type="hidden" name="site_id" value="{{ $siteId }}">
                <input type="hidden" name="season_id" value="{{ $seasonId }}">
                <h4 class="font-semibold text-sm">Publish consensus forecast (BF)</h4>
                <select name="integrated_tercile" class="tk-input" required>
                    <option value="3">Above normal</option>
                    <option value="2">Normal</option>
                    <option value="1">Below normal</option>
                </select>
                <textarea name="advisory_message" class="tk-input" rows="3" required placeholder="Joint advisory message…"></textarea>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="publish_snapshot" value="1"> Snapshot tally</label>
                <button class="tk-btn-secondary text-sm">Save integration session</button>
            </form>
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
