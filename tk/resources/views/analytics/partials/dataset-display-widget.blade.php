@php
    $render = $chart['render'] ?? 'canvas';
    $filterAction = $filterAction ?? null;
    $query = $query ?? [];
@endphp

@if (!empty($chart['empty']))
    <p class="text-sm text-tk-muted py-8 text-center flex-1">
        {{ $chart['error'] ?? 'No data for this view.' }}
    </p>
@elseif ($render === 'table' && !empty($chart['table_rows']))
    <div class="overflow-x-auto flex-1 text-sm w-full min-h-[10rem] max-h-[28rem] lg:max-h-none lg:min-h-[12rem] overflow-y-auto">
        <table class="w-full">
            <thead class="bg-tk-soft text-left sticky top-0">
                <tr>
                    <th class="p-2">Value</th>
                    <th class="p-2">Count</th>
                    <th class="p-2">Share</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($chart['table_rows'] as $row)
                    <tr class="border-t border-tk-line">
                        <td class="p-2">
                            @if ($filterAction && !empty($chart['filter_column']))
                                <a href="{{ $filterAction }}?{{ http_build_query(array_merge($query, ['filter_column' => $chart['filter_column'], 'filter_value' => $row['value']])) }}" class="text-tk-accent">{{ $row['value'] }}</a>
                            @else
                                {{ $row['value'] }}
                            @endif
                        </td>
                        <td class="p-2 font-mono">{{ number_format($row['count']) }}</td>
                        <td class="p-2 text-tk-muted">{{ $row['share'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif ($render === 'stats' && !empty($chart['stats']))
    <dl class="grid grid-cols-2 gap-3 text-sm flex-1">
        @foreach ($chart['stats'] as $stat)
            <div class="bg-tk-soft rounded-md p-3">
                <dt class="text-xs text-tk-muted uppercase">{{ $stat['label'] }}</dt>
                <dd class="font-semibold text-lg mt-1">{{ $stat['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@elseif ($render === 'top_list' && !empty($chart['items']))
    <ul class="space-y-2 flex-1 text-sm w-full min-h-[10rem] overflow-y-auto">
        @foreach ($chart['items'] as $item)
            <li>
                <div class="flex justify-between gap-2 mb-1">
                    @if ($filterAction && !empty($chart['filter_column']))
                        <a href="{{ $filterAction }}?{{ http_build_query(array_merge($query, ['filter_column' => $chart['filter_column'], 'filter_value' => $item['label']])) }}" class="text-tk-accent truncate">{{ $item['label'] }}</a>
                    @else
                        <span class="truncate">{{ $item['label'] }}</span>
                    @endif
                    <span class="font-mono shrink-0">{{ number_format($item['value']) }}</span>
                </div>
                <div class="h-2 bg-tk-soft rounded-full overflow-hidden">
                    <div class="h-full bg-tk-accent rounded-full" style="width: {{ $item['pct'] }}%"></div>
                </div>
            </li>
        @endforeach
    </ul>
@elseif ($render === 'crosstab' && !empty($chart['crosstab']))
    @php $ct = $chart['crosstab']; @endphp
    <div class="overflow-x-auto flex-1 text-xs w-full min-h-[10rem] lg:min-h-[12rem] overflow-y-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-tk-soft">
                    <th class="p-2 border border-tk-line"></th>
                    @foreach ($ct['col_headers'] as $col)
                        <th class="p-2 border border-tk-line font-mono">{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($ct['row_headers'] as $ri => $rowLabel)
                    <tr>
                        <th class="p-2 border border-tk-line text-left font-mono bg-tk-soft">{{ $rowLabel }}</th>
                        @foreach ($ct['cells'][$ri] ?? [] as $cell)
                            <td class="p-2 border border-tk-line text-center font-mono">{{ $cell ?: '·' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif ($render === 'sample_rows' && !empty($chart['sample_rows']))
    <div class="overflow-x-auto flex-1 text-xs w-full min-h-[10rem] lg:min-h-[12rem] overflow-y-auto">
        <table class="w-full">
            <thead class="bg-tk-soft sticky top-0">
                <tr>
                    @foreach ($chart['sample_headers'] as $h)
                        <th class="p-2 text-left font-mono whitespace-nowrap">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($chart['sample_rows'] as $row)
                    <tr class="border-t border-tk-line">
                        @foreach ($chart['sample_headers'] as $h)
                            <td class="p-2 max-w-[8rem] truncate" title="{{ $row[$h] ?? '' }}">{{ $row[$h] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif ($render === 'kpi' && isset($chart['kpi_value']))
    <div class="flex-1 flex flex-col items-center justify-center py-6 text-center">
        <p class="text-4xl font-bold text-tk-accent tracking-tight">{{ $chart['kpi_value'] }}</p>
        @if (!empty($chart['kpi_detail']))
            <p class="text-xs text-tk-muted mt-2 max-w-xs">{{ $chart['kpi_detail'] }}</p>
        @endif
    </div>
@else
    <div class="flex-1 min-h-[14rem] w-full relative">
        <canvas id="{{ $chart['id'] }}" class="!max-h-none w-full" height="220"></canvas>
    </div>
@endif
