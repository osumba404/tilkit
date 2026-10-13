<?php

namespace App\Services\Datasets;

use App\Models\PlatformDataset;
use App\Support\DatasetAccFilters;
use Illuminate\Pagination\LengthAwarePaginator;

class DatasetAccService
{
    /** @return array<string, mixed> */
    public function overview(PlatformDataset $dataset, DatasetAccFilters $filters): array
    {
        $filteredRows = $this->countMatchingRows($dataset, $filters);
        $totalNulls = $dataset->columns->sum('null_count');
        $runCount = $dataset->analysisRuns()->count();

        $numericColumns = $dataset->columns->whereIn('inferred_type', ['integer', 'decimal', 'number'])->count();
        $categoryColumns = $dataset->columns->where('inferred_type', 'category')->count();

        return [
            'filtered_rows' => $filteredRows,
            'total_rows' => $dataset->row_count,
            'columns' => $dataset->column_count,
            'missing_cells' => $totalNulls,
            'analysis_runs' => $runCount,
            'numeric_columns' => $numericColumns,
            'category_columns' => $categoryColumns,
            'uploaded_at' => $dataset->created_at,
        ];
    }

    /** @return array<string, int> */
    public function distribution(PlatformDataset $dataset, ?string $column, DatasetAccFilters $filters, int $limit = 12): array
    {
        if ($column === null) {
            return [];
        }

        return $this->valueCounts($dataset, $column, $filters, $limit);
    }

    /** @return ?array{type: string, labels: list<string>, values: list<float>, title: string, x_column: string, y_column: string} */
    public function lineGraphSeries(PlatformDataset $dataset, DatasetAccFilters $filters, int $limit = 80): ?array
    {
        $xColumn = $dataset->columns->first(fn ($c) => in_array($c->inferred_type, ['date', 'datetime', 'timestamp'], true))?->name
            ?? $dataset->columns->firstWhere('inferred_type', 'category')?->name
            ?? $dataset->columns->first()?->name;

        $yColumn = $dataset->columns->first(fn ($c) => in_array($c->inferred_type, ['integer', 'decimal', 'number'], true)
            && $c->name !== $xColumn)?->name;

        if ($xColumn === null || $yColumn === null) {
            return null;
        }

        $series = $this->readFilteredSeries($dataset, $xColumn, $yColumn, $filters, $limit);
        if ($series['labels'] === []) {
            return null;
        }

        return [
            'type' => 'line',
            'labels' => $series['labels'],
            'values' => $series['values'],
            'title' => "{$yColumn} over {$xColumn}",
            'x_column' => $xColumn,
            'y_column' => $yColumn,
        ];
    }

    public function paginateRows(PlatformDataset $dataset, DatasetAccFilters $filters, int $page = 1, int $perPage = 25): LengthAwarePaginator
    {
        $csvPath = $dataset->dataCsvPath();
        if (! is_readable($csvPath)) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $filterIndex = $this->columnIndex($headers, $filters->filterColumn);

        $matching = [];
        while (($row = fgetcsv($handle)) !== false) {
            if ($this->rowMatches($row, $filterIndex, $filters->filterValue)) {
                $matching[] = $this->rowAssoc($headers, $row);
            }
        }
        fclose($handle);

        $total = count($matching);
        $slice = array_slice($matching, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    private function countMatchingRows(PlatformDataset $dataset, DatasetAccFilters $filters): int
    {
        $csvPath = $dataset->dataCsvPath();
        if (! is_readable($csvPath)) {
            return 0;
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $filterIndex = $this->columnIndex($headers, $filters->filterColumn);

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if ($this->rowMatches($row, $filterIndex, $filters->filterValue)) {
                $count++;
            }
        }
        fclose($handle);

        return $count;
    }

    /** @return array<string, int> */
    private function valueCounts(PlatformDataset $dataset, string $column, DatasetAccFilters $filters, int $limit): array
    {
        $csvPath = $dataset->dataCsvPath();
        if (! is_readable($csvPath)) {
            return [];
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $valueIndex = $this->columnIndex($headers, $column);
        $filterIndex = $this->columnIndex($headers, $filters->filterColumn);

        if ($valueIndex === null) {
            fclose($handle);

            return [];
        }

        $counts = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (! $this->rowMatches($row, $filterIndex, $filters->filterValue)) {
                continue;
            }
            $val = trim((string) ($row[$valueIndex] ?? ''));
            if ($val === '') {
                $val = '(empty)';
            }
            $counts[$val] = ($counts[$val] ?? 0) + 1;
        }
        fclose($handle);

        arsort($counts);

        return array_slice($counts, 0, $limit, true);
    }

    /** @param list<string> $headers */
    private function columnIndex(array $headers, ?string $column): ?int
    {
        if ($column === null) {
            return null;
        }
        $index = array_search($column, $headers, true);

        return $index === false ? null : (int) $index;
    }

    /** @param list<string|null> $row */
    private function rowMatches(array $row, ?int $filterIndex, ?string $filterValue): bool
    {
        if ($filterIndex === null || $filterValue === null) {
            return true;
        }

        $cell = trim((string) ($row[$filterIndex] ?? ''));
        if ($cell === '' && $filterValue === '(empty)') {
            return true;
        }

        return $cell === $filterValue;
    }

    /** @param list<string> $headers @param list<string|null> $row @return array<string, string> */
    private function rowAssoc(array $headers, array $row): array
    {
        $out = [];
        foreach ($headers as $i => $header) {
            $out[$header] = (string) ($row[$i] ?? '');
        }

        return $out;
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function readFilteredSeries(
        PlatformDataset $dataset,
        string $xColumn,
        string $yColumn,
        DatasetAccFilters $filters,
        int $limit,
    ): array {
        $csvPath = $dataset->dataCsvPath();
        if (! is_readable($csvPath)) {
            return ['labels' => [], 'values' => []];
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $xIndex = $this->columnIndex($headers, $xColumn);
        $yIndex = $this->columnIndex($headers, $yColumn);
        $filterIndex = $this->columnIndex($headers, $filters->filterColumn);

        if ($xIndex === null || $yIndex === null) {
            fclose($handle);

            return ['labels' => [], 'values' => []];
        }

        $labels = [];
        $values = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (! $this->rowMatches($row, $filterIndex, $filters->filterValue)) {
                continue;
            }
            $yRaw = str_replace(',', '', trim((string) ($row[$yIndex] ?? '')));
            if (! is_numeric($yRaw)) {
                continue;
            }
            $labels[] = trim((string) ($row[$xIndex] ?? '')) ?: '(empty)';
            $values[] = (float) $yRaw;
            if (count($labels) >= $limit) {
                break;
            }
        }
        fclose($handle);

        return ['labels' => $labels, 'values' => $values];
    }
}
