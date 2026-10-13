@php
    $columnOptions = $dataset->columns->map(fn ($c) => ['name' => $c->name, 'type' => $c->inferred_type])->values();
    $formDefaults = $defaults ?? [
        'type' => 'bar',
        'column' => $dataset->columns->first()?->name ?? '',
        'x_column' => $dataset->columns->first()?->name ?? '',
        'y_column' => $dataset->columns->firstWhere('inferred_type', 'category')?->name ?? $dataset->columns->skip(1)->first()?->name ?? '',
        'z_column' => $dataset->columns->first(fn ($c) => in_array($c->inferred_type, ['integer', 'decimal', 'number'], true))?->name ?? '',
        'metric' => 'count',
    ];
    $panelColors = \App\Support\DatasetPanelColors::resolve($formDefaults['colors'] ?? null);
    $submitLabel = $submitLabel ?? 'Run this panel';
@endphp
<div
    x-data="{
        columns: @js($columnOptions),
        type: @js($formDefaults['type']),
        column: @js($formDefaults['column']),
        x_column: @js($formDefaults['x_column']),
        y_column: @js($formDefaults['y_column']),
        z_column: @js($formDefaults['z_column']),
        metric: @js($formDefaults['metric']),
        oneFieldTypes: ['pie', 'doughnut', 'bar', 'histogram', 'table', 'stats', 'top_list', 'kpi'],
        twoFieldTypes: ['line', 'area', 'scatter', 'crosstab'],
        threeFieldTypes: ['3d_bubble'],
        noFieldTypes: ['sample_rows'],
        axisCount(t) {
            if (this.noFieldTypes.includes(t)) return 0;
            if (this.oneFieldTypes.includes(t)) return 1;
            if (this.twoFieldTypes.includes(t)) return 2;
            if (this.threeFieldTypes.includes(t)) return 3;
            return 1;
        },
        usesColumn(t) { return this.axisCount(t) === 1; },
        usesXY(t) { return this.axisCount(t) === 2; },
        usesXYZ(t) { return this.axisCount(t) === 3; },
        axisHint(t) {
            const n = this.axisCount(t);
            if (n === 0) return 'Uses all columns';
            if (n === 1) return 'Pick 1 column';
            if (n === 2) return 'Pick X and Y (2D)';
            return 'Pick X, Y, and Z (3D)';
        },
    }"
    class="space-y-3 dataset-panel-form-fields">
    @if ($filters->filterColumn ?? null)
        <input type="hidden" name="filter_column" value="{{ $filters->filterColumn }}">
        <input type="hidden" name="filter_value" value="{{ $filters->filterValue }}">
    @endif

    <div class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="text-xs text-tk-muted block mb-1">Display type</label>
            <select name="type" x-model="type" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[10rem]">
                <optgroup label="2D / 3D charts">
                    <option value="pie">pie</option>
                    <option value="doughnut">doughnut</option>
                    <option value="bar">bar</option>
                    <option value="line">line</option>
                    <option value="area">area</option>
                    <option value="histogram">histogram</option>
                    <option value="scatter">scatter</option>
                    <option value="3d_bubble">3D bubble (X,Y,Z)</option>
                </optgroup>
                <optgroup label="Tables & summaries">
                    <option value="table">table</option>
                    <option value="stats">stats</option>
                    <option value="top_list">top list</option>
                    <option value="crosstab">crosstab</option>
                    <option value="sample_rows">sample rows</option>
                    <option value="kpi">kpi</option>
                </optgroup>
            </select>
        </div>
        <p class="text-xs text-tk-accent pb-2" x-text="axisHint(type)"></p>
    </div>

    <div class="flex flex-wrap gap-2 items-end">
        <div x-show="usesColumn(type)">
            <label class="text-xs text-tk-muted block mb-1">Column</label>
            <select name="column" x-model="column" :disabled="!usesColumn(type)" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[10rem]">
                @foreach ($dataset->columns as $col)
                    <option value="{{ $col->name }}">{{ $col->name }} ({{ $col->inferred_type }})</option>
                @endforeach
            </select>
        </div>
        <div x-show="usesXY(type) || usesXYZ(type)">
            <label class="text-xs text-tk-muted block mb-1">X axis</label>
            <select name="x_column" x-model="x_column" :disabled="!(usesXY(type) || usesXYZ(type))" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[10rem]">
                @foreach ($dataset->columns as $col)
                    <option value="{{ $col->name }}">{{ $col->name }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="usesXY(type) || usesXYZ(type)">
            <label class="text-xs text-tk-muted block mb-1">Y axis</label>
            <select name="y_column" x-model="y_column" :disabled="!(usesXY(type) || usesXYZ(type))" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[10rem]">
                @foreach ($dataset->columns as $col)
                    <option value="{{ $col->name }}">{{ $col->name }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="usesXYZ(type)">
            <label class="text-xs text-tk-muted block mb-1">Z axis</label>
            <select name="z_column" x-model="z_column" :disabled="!usesXYZ(type)" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[10rem]">
                @foreach ($dataset->columns as $col)
                    <option value="{{ $col->name }}">{{ $col->name }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="type === 'kpi'">
            <label class="text-xs text-tk-muted block mb-1">Metric</label>
            <select name="metric" x-model="metric" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[8rem]">
                @foreach (['count', 'sum', 'average', 'min', 'max', 'distinct'] as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="text-xs text-tk-muted block mb-1">Chart colors</label>
        <div class="flex flex-wrap gap-2 items-center">
            @foreach (range(0, 3) as $i)
                <input type="color" name="colors[]" value="{{ $panelColors[$i] ?? '#0891b2' }}"
                    class="h-9 w-12 rounded border border-tk-line cursor-pointer bg-white p-0.5"
                    title="Color {{ $i + 1 }}">
            @endforeach
            <span class="text-xs text-tk-muted">Used for bars, slices, lines, and list bars.</span>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 items-center pt-1">
        <button type="submit" class="tk-btn-primary text-sm">{{ $submitLabel }}</button>
        @if (!empty($cancelEdit))
            <button type="button" class="tk-btn-secondary text-sm" @click="{{ $cancelEdit }}">Cancel</button>
        @endif
    </div>
</div>
