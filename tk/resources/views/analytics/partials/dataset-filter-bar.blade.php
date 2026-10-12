@php
    $filterAction = $filterAction ?? url()->current();
    $query = $filters->toQueryArray();
@endphp
<form method="GET" action="{{ $filterAction }}" class="tk-card p-4 mb-6 space-y-3" id="acc-filter-form">
    <div class="flex flex-wrap gap-3 items-end text-sm">
        <div>
            <label class="text-xs text-tk-muted block mb-1">Chart dimension A</label>
            <select name="dimension_a" class="rounded-md border border-tk-line px-2 py-1.5 bg-white min-w-[10rem]">
                @foreach ($dataset->columns as $col)
                    <option value="{{ $col->name }}" @selected($filters->dimensionA === $col->name)>{{ $col->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-tk-muted block mb-1">Chart dimension B</label>
            <select name="dimension_b" class="rounded-md border border-tk-line px-2 py-1.5 bg-white min-w-[10rem]">
                @foreach ($dataset->columns as $col)
                    <option value="{{ $col->name }}" @selected($filters->dimensionB === $col->name)>{{ $col->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($filters->filterColumn)
            <input type="hidden" name="filter_column" value="{{ $filters->filterColumn }}">
            <input type="hidden" name="filter_value" value="{{ $filters->filterValue }}">
        @endif
        <button type="submit" class="tk-btn-primary text-sm">Apply</button>
        <a href="{{ $filterAction }}" class="tk-btn-secondary text-sm">Reset</a>
    </div>
    @if ($filters->hasActiveFilter())
        <div class="flex flex-wrap gap-2 text-xs items-center">
            <span class="text-tk-muted">Row filter:</span>
            <span class="bg-tk-soft px-2 py-0.5 rounded border border-tk-line">{{ $filters->filterColumn }} = {{ $filters->filterValue }}</span>
            <a href="{{ $filterAction }}?{{ http_build_query(array_diff_key($query, ['filter_column' => 1, 'filter_value' => 1])) }}" class="text-tk-accent">Clear filter ×</a>
        </div>
    @endif
    <p class="text-xs text-tk-muted">Click a doughnut segment to filter rows across this dataset dashboard.</p>
</form>
