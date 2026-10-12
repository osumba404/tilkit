@php
    $filterAction = $filterAction ?? url()->current();
    $showFormFilter = $showFormFilter ?? false;
    $forms = $forms ?? collect();
    $query = $filters->toQueryArray();
@endphp
<form method="GET" action="{{ $filterAction }}" class="tk-card p-4 mb-6 space-y-3" id="acc-filter-form">
    <div class="flex flex-wrap gap-3 items-end text-sm">
        <div>
            <label class="text-xs text-tk-muted block mb-1">Period</label>
            <select name="period" class="rounded-md border border-tk-line px-2 py-1.5 bg-white">
                @foreach (['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', 'ytd' => 'Year to date'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('period', '30') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-tk-muted block mb-1">Compare</label>
            <select name="compare" class="rounded-md border border-tk-line px-2 py-1.5 bg-white">
                <option value="previous" @selected($filters->compare === 'previous')>Previous period</option>
                <option value="none" @selected($filters->compare === 'none')>No comparison</option>
            </select>
        </div>
        @if ($showFormFilter)
            <div>
                <label class="text-xs text-tk-muted block mb-1">Form</label>
                <select name="form_id" class="rounded-md border border-tk-line px-2 py-1.5 bg-white min-w-[10rem]">
                    <option value="">All forms</option>
                    @foreach ($forms as $f)
                        <option value="{{ $f->id }}" @selected($filters->formId === $f->id)>{{ $f->title }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label class="text-xs text-tk-muted block mb-1">Status</label>
            <select name="status" class="rounded-md border border-tk-line px-2 py-1.5 bg-white">
                <option value="">All</option>
                @foreach (['submitted', 'in_review', 'approved', 'rejected'] as $st)
                    <option value="{{ $st }}" @selected($filters->status === $st)>{{ str_replace('_', ' ', $st) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-tk-muted block mb-1">Channel</label>
            <select name="channel" class="rounded-md border border-tk-line px-2 py-1.5 bg-white">
                <option value="">All</option>
                <option value="web" @selected($filters->channel === 'web')>web</option>
            </select>
        </div>
        @if ($showFormFilter)
            <div>
                <label class="text-xs text-tk-muted block mb-1">Site (JSON)</label>
                <input type="text" name="site" value="{{ $filters->site }}" placeholder="Community site" class="rounded-md border border-tk-line px-2 py-1.5 w-36">
            </div>
            <div>
                <label class="text-xs text-tk-muted block mb-1">Season</label>
                <input type="text" name="season" value="{{ $filters->season }}" placeholder="Season/year" class="rounded-md border border-tk-line px-2 py-1.5 w-28">
            </div>
        @endif
        <button type="submit" class="tk-btn-primary text-sm">Apply</button>
        <a href="{{ $filterAction }}" class="tk-btn-secondary text-sm">Reset</a>
    </div>
    @if ($filters->status || $filters->channel || $filters->formId || $filters->site || $filters->season)
        <div class="flex flex-wrap gap-2 text-xs">
            <span class="text-tk-muted">Active:</span>
            @if ($filters->status)
                <a href="{{ $filterAction }}?{{ http_build_query(array_diff_key($query, ['status' => 1])) }}" class="bg-tk-soft px-2 py-0.5 rounded border border-tk-line">status: {{ $filters->status }} ×</a>
            @endif
            @if ($filters->channel)
                <a href="{{ $filterAction }}?{{ http_build_query(array_diff_key($query, ['channel' => 1])) }}" class="bg-tk-soft px-2 py-0.5 rounded border border-tk-line">channel: {{ $filters->channel }} ×</a>
            @endif
        </div>
    @endif
    <p class="text-xs text-tk-muted">{{ $filters->periodLabel() }} · Filters apply to compatible widgets on this page.</p>
</form>
