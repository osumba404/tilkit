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
}
