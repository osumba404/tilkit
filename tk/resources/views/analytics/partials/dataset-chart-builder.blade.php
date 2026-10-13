@php
    $filterAction = $filterAction ?? url()->current();
    $query = $filters->toQueryArray();
    $maxCharts = (int) config('tilkit.dataset_analysis.max_dashboard_charts', 12);
    $columnOptions = $dataset->columns->map(fn ($c) => ['name' => $c->name, 'type' => $c->inferred_type])->values();
    $initialRows = collect($filters->charts)->map(fn ($c) => [
        'type' => $c['type'],
        'column' => $c['column'] ?? '',
        'x_column' => $c['x_column'] ?? '',
        'y_column' => $c['y_column'] ?? '',
        'z_column' => $c['z_column'] ?? '',
        'metric' => $c['metric'] ?? 'count',
    ])->values();
@endphp
<form method="GET" action="{{ $filterAction }}" class="tk-card p-4 mb-6 space-y-4" id="acc-filter-form"
    x-data="{
        maxCharts: {{ $maxCharts }},
        rows: @js($initialRows),
        columns: @js($columnOptions),
        oneFieldTypes: ['pie', 'doughnut', 'bar', 'histogram', 'table', 'stats', 'top_list', 'kpi'],
        twoFieldTypes: ['line', 'area', 'scatter', 'crosstab'],
        threeFieldTypes: ['3d_bubble'],
        noFieldTypes: ['sample_rows'],
        chartTypes: ['pie', 'doughnut', 'bar', 'line', 'area', 'histogram', 'scatter', '3d_bubble'],
        viewTypes: ['table', 'stats', 'top_list', 'crosstab', 'sample_rows', 'kpi'],
        metrics: ['count', 'sum', 'average', 'min', 'max', 'distinct'],
        axisCount(type) {
            if (this.noFieldTypes.includes(type)) return 0;
            if (this.oneFieldTypes.includes(type)) return 1;
            if (this.twoFieldTypes.includes(type)) return 2;
            if (this.threeFieldTypes.includes(type)) return 3;
            return 1;
        },
        usesColumn(type) { return this.axisCount(type) === 1; },
        usesXY(type) { return this.axisCount(type) === 2; },
        usesXYZ(type) { return this.axisCount(type) === 3; },
        usesMetric(type) { return type === 'kpi'; },
        axisHint(type) {
            const n = this.axisCount(type);
            if (n === 0) return 'Uses all columns';
            if (n === 1) return 'Pick 1 column';
            if (n === 2) return 'Pick X and Y (2D)';
            return 'Pick X, Y, and Z (3D)';
        },
        onTypeChange(row) {
            const n = this.axisCount(row.type);
            if (n !== 1) row.column = '';
            if (n !== 2 && n !== 3) { row.x_column = ''; row.y_column = ''; }
            if (n !== 3) row.z_column = '';
            if (n === 2 || n === 3) {
                const first = this.columns[0]?.name ?? '';
                const numeric = this.columns.filter(c => ['integer','decimal','number'].includes(c.type));
                if (!row.x_column) row.x_column = first;
                if (!row.y_column) row.y_column = (numeric[0]?.name ?? this.columns[1]?.name ?? first);
            }
            if (n === 3 && !row.z_column) {
                const numeric = this.columns.filter(c => ['integer','decimal','number'].includes(c.type));
                row.z_column = numeric[2]?.name ?? numeric[1]?.name ?? numeric[0]?.name ?? '';
            }
        },
        addRow() {
            if (this.rows.length >= this.maxCharts) return;
            const first = this.columns[0]?.name ?? '';
            const numeric = this.columns.find(c => ['integer','decimal','number'].includes(c.type))?.name ?? first;
            this.rows.push({ type: 'bar', column: first, x_column: first, y_column: numeric, z_column: numeric, metric: 'count' });
        },
        removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
    }">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="font-semibold text-sm">Visualization builder</h3>
            <p class="text-xs text-tk-muted">1-column views (pie, bar, table…), 2-axis charts (line, scatter), and 3-axis 3D bubble charts each show only the fields they need.</p>
        </div>
        <button type="button" class="tk-btn-secondary text-sm" @click="addRow()" :disabled="rows.length >= maxCharts">+ Add panel</button>
    </div>

    <div class="space-y-3">
        <template x-for="(row, index) in rows" :key="index">
            <div class="border border-tk-line rounded-md p-3 bg-tk-soft/40 space-y-2">
                <div class="flex flex-wrap gap-2 items-end">
                    <div>
                        <label class="text-xs text-tk-muted block mb-1">Display type</label>
                        <select :name="'charts[' + index + '][type]'" x-model="row.type" @change="onTypeChange(row)" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[9rem]">
                            <optgroup label="2D / 3D charts">
                                <template x-for="t in chartTypes" :key="'c'+t">
                                    <option :value="t" x-text="t === '3d_bubble' ? '3D bubble (X,Y,Z)' : t"></option>
                                </template>
                            </optgroup>
                            <optgroup label="Tables & summaries">
                                <template x-for="t in viewTypes" :key="'v'+t">
                                    <option :value="t" x-text="t.replace('_', ' ')"></option>
                                </template>
                            </optgroup>
                        </select>
                    </div>
                    <p class="text-xs text-tk-accent pb-2" x-text="axisHint(row.type)"></p>
                </div>
                <div class="flex flex-wrap gap-2 items-end">
                    <div x-show="usesColumn(row.type)">
                        <label class="text-xs text-tk-muted block mb-1">Column</label>
                        <select :name="usesColumn(row.type) ? 'charts[' + index + '][column]' : null" :disabled="!usesColumn(row.type)" x-model="row.column" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[10rem]">
                            <template x-for="col in columns" :key="col.name">
                                <option :value="col.name" x-text="col.name + ' (' + col.type + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="usesXY(row.type) || usesXYZ(row.type)">
                        <label class="text-xs text-tk-muted block mb-1" x-text="row.type === 'crosstab' ? 'Row field (X)' : 'X axis'"></label>
                        <select :name="(usesXY(row.type) || usesXYZ(row.type)) ? 'charts[' + index + '][x_column]' : null" :disabled="!(usesXY(row.type) || usesXYZ(row.type))" x-model="row.x_column" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[10rem]">
                            <template x-for="col in columns" :key="'x'+col.name">
                                <option :value="col.name" x-text="col.name"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="usesXY(row.type) || usesXYZ(row.type)">
                        <label class="text-xs text-tk-muted block mb-1" x-text="row.type === 'crosstab' ? 'Column field (Y)' : 'Y axis'"></label>
                        <select :name="(usesXY(row.type) || usesXYZ(row.type)) ? 'charts[' + index + '][y_column]' : null" :disabled="!(usesXY(row.type) || usesXYZ(row.type))" x-model="row.y_column" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[10rem]">
                            <template x-for="col in columns" :key="'y'+col.name">
                                <option :value="col.name" x-text="col.name"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="usesXYZ(row.type)">
                        <label class="text-xs text-tk-muted block mb-1">Z axis (size)</label>
                        <select :name="usesXYZ(row.type) ? 'charts[' + index + '][z_column]' : null" :disabled="!usesXYZ(row.type)" x-model="row.z_column" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[10rem]">
                            <template x-for="col in columns" :key="'z'+col.name">
                                <option :value="col.name" x-text="col.name"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="usesMetric(row.type)">
                        <label class="text-xs text-tk-muted block mb-1">Metric</label>
                        <select :name="'charts[' + index + '][metric]'" x-model="row.metric" class="rounded-md border border-tk-line px-2 py-1.5 bg-white text-sm min-w-[8rem]">
                            <template x-for="m in metrics" :key="m">
                                <option :value="m" x-text="m"></option>
                            </template>
                        </select>
                    </div>
                    <button type="button" class="text-xs text-red-700 px-2 py-1.5" @click="removeRow(index)" x-show="rows.length > 1">Remove</button>
                </div>
            </div>
        </template>
    </div>

    @if ($filters->filterColumn)
        <input type="hidden" name="filter_column" value="{{ $filters->filterColumn }}">
        <input type="hidden" name="filter_value" value="{{ $filters->filterValue }}">
    @endif

    <div class="flex flex-wrap gap-2 items-center">
        <button type="submit" class="tk-btn-primary text-sm">Apply</button>
        <a href="{{ $filterAction }}" class="tk-btn-secondary text-sm">Reset all</a>
    </div>

    @if ($filters->hasActiveFilter())
        <div class="flex flex-wrap gap-2 text-xs items-center">
            <span class="text-tk-muted">Row filter:</span>
            <span class="bg-tk-soft px-2 py-0.5 rounded border border-tk-line">{{ $filters->filterColumn }} = {{ $filters->filterValue }}</span>
            <a href="{{ $filterAction }}?{{ http_build_query(array_diff_key($query, ['filter_column' => 1, 'filter_value' => 1])) }}" class="text-tk-accent">Clear filter ×</a>
        </div>
    @endif
</form>
