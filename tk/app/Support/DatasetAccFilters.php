<?php

namespace App\Support;

use App\Models\PlatformDataset;
use Illuminate\Http\Request;

class DatasetAccFilters
{
    /** @param  list<array{id: string, type: string, column: ?string, x_column: ?string, y_column: ?string, z_column: ?string, metric: ?string}>  $charts */
    public function __construct(
        public ?string $filterColumn = null,
        public ?string $filterValue = null,
        public array $charts = [],
    ) {}

    public static function fromRequest(Request $request, PlatformDataset $dataset): self
    {
        $columns = $dataset->columns->pluck('name')->all();
        $pick = static fn (?string $name) => $name && in_array($name, $columns, true) ? $name : null;

        $charts = DatasetPanelSession::charts($request, $dataset);

        return new self(
            filterColumn: $pick($request->string('filter_column')->toString() ?: null),
            filterValue: $request->filled('filter_value') ? (string) $request->input('filter_value') : null,
            charts: $charts,
        );
    }

    /** @return list<array{id: string, type: string, column: ?string, x_column: ?string, y_column: ?string}> */
    public static function defaultCharts(PlatformDataset $dataset): array
    {
        $names = $dataset->columns->pluck('name')->all();
        if ($names === []) {
            return [];
        }

        $dateCol = $dataset->columns->first(fn ($c) => in_array($c->inferred_type, ['date', 'datetime', 'timestamp'], true))?->name;
        $numericCol = $dataset->columns->first(fn ($c) => in_array($c->inferred_type, ['integer', 'decimal', 'number'], true))?->name;
        $categoryCol = $dataset->columns->firstWhere('inferred_type', 'category')?->name ?? $names[0];

        $charts = [
            [
                'id' => 'acc_chart_0',
                'type' => 'pie',
                'column' => $categoryCol,
                'x_column' => null,
                'y_column' => null,
                'metric' => 'count',
            ],
        ];

        $second = collect($names)->first(fn ($n) => $n !== $categoryCol) ?? $names[0];
        $charts[] = [
            'id' => 'acc_chart_1',
            'type' => 'bar',
            'column' => $second,
            'x_column' => null,
            'y_column' => null,
            'metric' => 'count',
        ];

        if ($dateCol && $numericCol) {
            $charts[] = [
                'id' => 'acc_chart_2',
                'type' => 'line',
                'column' => null,
                'x_column' => $dateCol,
                'y_column' => $numericCol,
                'metric' => 'count',
            ];
        }

        return $charts;
    }

    /** @return array<string, mixed> */
    public function toQueryArray(): array
    {
        $query = [];
        if ($this->filterColumn) {
            $query['filter_column'] = $this->filterColumn;
        }
        if ($this->filterValue !== null && $this->filterValue !== '') {
            $query['filter_value'] = $this->filterValue;
        }
        return $query;
    }

    public function hasActiveFilter(): bool
    {
        return $this->filterColumn !== null && $this->filterValue !== null;
    }
}
