<?php

namespace App\Services\Datasets;

use App\Models\PlatformDataset;
use App\Support\DatasetAccFilters;
use App\Support\DatasetChartSchema;
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

    /**
     * @return list<array<string, mixed>>
     */
    public function materializeCharts(PlatformDataset $dataset, DatasetAccFilters $filters): array
    {
        $out = [];
        foreach ($filters->charts as $def) {
            $payload = $this->materializeChart($dataset, $def, $filters);
            if ($payload !== null) {
                $out[] = $payload;
            }
        }

        return $out;
    }

    /**
     * @param  array{id: string, type: string, column: ?string, x_column: ?string, y_column: ?string}  $def
     * @return ?array<string, mixed>
     */
    public function materializeChart(PlatformDataset $dataset, array $def, DatasetAccFilters $filters): ?array
    {
        $type = $def['type'];
        $id = $def['id'];

        $axisError = DatasetChartSchema::validate($def);
        if ($axisError !== null) {
            return [
                'id' => $id,
                'type' => $type,
                'title' => DatasetChartSchema::typeLabel($type),
                'empty' => true,
                'error' => $axisError,
            ];
        }

        if (in_array($type, ['pie', 'doughnut', 'bar'], true)) {
            $column = $def['column'];
            if ($column === null) {
                return null;
            }
            $limit = $type === 'bar' ? 24 : 14;
            $counts = $this->distribution($dataset, $column, $filters, $limit);
            if ($counts === []) {
                return [
                    'id' => $id,
                    'type' => $type,
                    'title' => $column,
                    'empty' => true,
                    'filter_column' => $column,
                ];
            }

            return [
                'id' => $id,
                'type' => $type,
                'render' => 'canvas',
                'title' => $column,
                'labels' => array_keys($counts),
                'values' => array_values($counts),
                'filter_column' => $column,
            ];
        }

        if ($type === 'area') {
            $line = $this->materializeLineSeries($dataset, $def, $filters, $id, 'area');

            return $line ? array_merge($line, ['render' => 'canvas']) : null;
        }

        if ($type === 'table') {
            $column = $def['column'] ?? $dataset->columns->first()?->name;
            if ($column === null) {
                return null;
            }
            $counts = $this->distribution($dataset, $column, $filters, 50);
            if ($counts === []) {
                return ['id' => $id, 'type' => 'table', 'render' => 'table', 'title' => "Frequency · {$column}", 'empty' => true];
            }
            $total = array_sum($counts);
            $rows = [];
            foreach ($counts as $label => $count) {
                $rows[] = [
                    'value' => $label,
                    'count' => $count,
                    'share' => $total > 0 ? round(($count / $total) * 100, 1).'%' : '0%',
                ];
            }

            return [
                'id' => $id,
                'type' => 'table',
                'render' => 'table',
                'title' => "Frequency · {$column}",
                'table_rows' => $rows,
                'filter_column' => $column,
            ];
        }

        if ($type === 'stats') {
            $column = $def['column'] ?? null;
            if ($column === null) {
                return null;
            }
            $stats = $this->numericColumnStats($dataset, $column, $filters);
            if ($stats === null) {
                return ['id' => $id, 'type' => 'stats', 'render' => 'stats', 'title' => "Statistics · {$column}", 'empty' => true];
            }

            return [
                'id' => $id,
                'type' => 'stats',
                'render' => 'stats',
                'title' => "Statistics · {$column}",
                'stats' => $stats,
            ];
        }

        if ($type === 'top_list') {
            $column = $def['column'] ?? null;
            if ($column === null) {
                return null;
            }
            $counts = $this->distribution($dataset, $column, $filters, 15);
            if ($counts === []) {
                return ['id' => $id, 'type' => 'top_list', 'render' => 'top_list', 'title' => "Top values · {$column}", 'empty' => true];
            }
            $max = max($counts);
            $items = [];
            foreach ($counts as $label => $count) {
                $items[] = [
                    'label' => $label,
                    'value' => $count,
                    'pct' => $max > 0 ? (int) round(($count / $max) * 100) : 0,
                ];
            }

            return [
                'id' => $id,
                'type' => 'top_list',
                'render' => 'top_list',
                'title' => "Top values · {$column}",
                'items' => $items,
                'filter_column' => $column,
            ];
        }

        if ($type === 'crosstab') {
            $rowCol = $def['x_column'] ?? $def['column'];
            $colCol = $def['y_column'];
            if ($rowCol === null || $colCol === null) {
                return null;
            }
            $grid = $this->crossTab($dataset, $rowCol, $colCol, $filters, 8);
            if ($grid === null) {
                return ['id' => $id, 'type' => 'crosstab', 'render' => 'crosstab', 'title' => "{$rowCol} × {$colCol}", 'empty' => true];
            }

            return [
                'id' => $id,
                'type' => 'crosstab',
                'render' => 'crosstab',
                'title' => "{$rowCol} × {$colCol}",
                'crosstab' => $grid,
            ];
        }

        if ($type === 'sample_rows') {
            $preview = $this->samplePreview($dataset, $filters, 8);

            return [
                'id' => $id,
                'type' => 'sample_rows',
                'render' => 'sample_rows',
                'title' => 'Data sample',
                'sample_headers' => $preview['headers'],
                'sample_rows' => $preview['rows'],
                'empty' => $preview['rows'] === [],
            ];
        }

        if ($type === 'kpi') {
            $column = $def['column'] ?? null;
            $metric = $def['metric'] ?? 'count';
            if ($column === null) {
                return null;
            }
            $kpi = $this->kpiValue($dataset, $column, $metric, $filters);
            if ($kpi === null) {
                return ['id' => $id, 'type' => 'kpi', 'render' => 'kpi', 'title' => $column, 'empty' => true];
            }

            return [
                'id' => $id,
                'type' => 'kpi',
                'render' => 'kpi',
                'title' => $kpi['label'],
                'kpi_value' => $kpi['value'],
                'kpi_detail' => $kpi['detail'] ?? null,
            ];
        }

        if ($type === '3d_bubble') {
            return $this->materialize3dBubble($dataset, $def, $filters, $id);
        }

        if ($type === 'histogram') {
            $column = $def['column'];
            if ($column === null) {
                return null;
            }
            $bins = $this->histogramBins($dataset, $column, $filters, 10);
            if ($bins === []) {
                return ['id' => $id, 'type' => 'bar', 'title' => "Histogram · {$column}", 'empty' => true];
            }

            return [
                'id' => $id,
                'type' => 'bar',
                'render' => 'canvas',
                'title' => "Histogram · {$column}",
                'labels' => array_keys($bins),
                'values' => array_values($bins),
            ];
        }

        if ($type === 'line') {
            $line = $this->materializeLineSeries($dataset, $def, $filters, $id, 'line');

            return $line ? array_merge($line, ['render' => 'canvas']) : null;
        }

        if ($type === 'scatter') {
            $xColumn = $def['x_column'];
            $yColumn = $def['y_column'];
            if ($xColumn === null || $yColumn === null) {
                return null;
            }
            $series = $this->readFilteredSeries($dataset, $xColumn, $yColumn, $filters, 250);
            $points = [];
            foreach ($series['values'] as $i => $y) {
                $xLabel = $series['labels'][$i] ?? '';
                $x = is_numeric(str_replace(',', '', $xLabel)) ? (float) str_replace(',', '', $xLabel) : (float) $i;
                $points[] = ['x' => $x, 'y' => $y];
            }
            if ($points === []) {
                return ['id' => $id, 'type' => 'scatter', 'title' => "{$yColumn} vs {$xColumn}", 'empty' => true];
            }

            return [
                'id' => $id,
                'type' => 'scatter',
                'render' => 'canvas',
                'title' => "{$yColumn} vs {$xColumn}",
                'points' => $points,
            ];
        }

        return null;
    }

    /**
     * @param  array{id: string, type: string, column: ?string, x_column: ?string, y_column: ?string, metric?: ?string}  $def
     * @return ?array<string, mixed>
     */
    /**
     * @param  array{id: string, type: string, x_column: ?string, y_column: ?string, z_column: ?string}  $def
     * @return array<string, mixed>
     */
    private function materialize3dBubble(PlatformDataset $dataset, array $def, DatasetAccFilters $filters, string $id): array
    {
        $xColumn = $def['x_column'];
        $yColumn = $def['y_column'];
        $zColumn = $def['z_column'];
        $points = $this->readXYZBubblePoints($dataset, $xColumn, $yColumn, $zColumn, $filters, 200);
        if ($points === []) {
            return [
                'id' => $id,
                'type' => '3d_bubble',
                'render' => 'canvas',
                'title' => "{$yColumn} vs {$xColumn} (size: {$zColumn})",
                'empty' => true,
                'error' => 'No numeric rows found for all three axes.',
            ];
        }

        return [
            'id' => $id,
            'type' => '3d_bubble',
            'render' => 'canvas',
            'title' => "{$yColumn} vs {$xColumn} · Z = {$zColumn}",
            'points' => $points,
            'axis_labels' => ['x' => $xColumn, 'y' => $yColumn, 'z' => $zColumn],
        ];
    }

    private function materializeLineSeries(PlatformDataset $dataset, array $def, DatasetAccFilters $filters, string $id, string $type): ?array
    {
        $xColumn = $def['x_column'];
        $yColumn = $def['y_column'];
        if ($xColumn === null || $yColumn === null) {
            return null;
        }
        $series = $this->readFilteredSeries($dataset, $xColumn, $yColumn, $filters, 120);
        if ($series['labels'] === []) {
            return ['id' => $id, 'type' => $type, 'title' => "{$yColumn} over {$xColumn}", 'empty' => true];
        }

        return [
            'id' => $id,
            'type' => $type,
            'title' => "{$yColumn} over {$xColumn}",
            'labels' => $series['labels'],
            'values' => $series['values'],
        ];
    }

    /** @return ?list<array{label: string, value: string}> */
    private function numericColumnStats(PlatformDataset $dataset, string $column, DatasetAccFilters $filters): ?array
    {
        $values = $this->numericValues($dataset, $column, $filters);
        if ($values === []) {
            return null;
        }

        sort($values);
        $count = count($values);
        $sum = array_sum($values);
        $mean = $sum / $count;
        $mid = (int) floor($count / 2);
        $median = $count % 2 === 0 ? ($values[$mid - 1] + $values[$mid]) / 2 : $values[$mid];
        $variance = 0.0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $std = sqrt($variance / $count);

        return [
            ['label' => 'Count', 'value' => number_format($count)],
            ['label' => 'Sum', 'value' => number_format($sum, 2)],
            ['label' => 'Mean', 'value' => number_format($mean, 4)],
            ['label' => 'Median', 'value' => number_format($median, 4)],
            ['label' => 'Min', 'value' => number_format(min($values), 4)],
            ['label' => 'Max', 'value' => number_format(max($values), 4)],
            ['label' => 'Std dev', 'value' => number_format($std, 4)],
        ];
    }

    /** @return list<float> */
    private function numericValues(PlatformDataset $dataset, string $column, DatasetAccFilters $filters): array
    {
        $csvPath = $dataset->dataCsvPath();
        if (! is_readable($csvPath)) {
            return [];
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $index = $this->columnIndex($headers, $column);
        $filterIndex = $this->columnIndex($headers, $filters->filterColumn);
        if ($index === null) {
            fclose($handle);

            return [];
        }

        $values = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (! $this->rowMatches($row, $filterIndex, $filters->filterValue)) {
                continue;
            }
            $raw = str_replace(',', '', trim((string) ($row[$index] ?? '')));
            if (is_numeric($raw)) {
                $values[] = (float) $raw;
            }
        }
        fclose($handle);

        return $values;
    }

    /** @return ?array{col_headers: list<string>, row_headers: list<string>, cells: list<list<int>>} */
    private function crossTab(PlatformDataset $dataset, string $rowCol, string $colCol, DatasetAccFilters $filters, int $limit): ?array
    {
        $csvPath = $dataset->dataCsvPath();
        if (! is_readable($csvPath)) {
            return null;
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $rowIndex = $this->columnIndex($headers, $rowCol);
        $colIndex = $this->columnIndex($headers, $colCol);
        $filterIndex = $this->columnIndex($headers, $filters->filterColumn);
        if ($rowIndex === null || $colIndex === null) {
            fclose($handle);

            return null;
        }

        $matrix = [];
        $rowKeys = [];
        $colKeys = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (! $this->rowMatches($row, $filterIndex, $filters->filterValue)) {
                continue;
            }
            $r = trim((string) ($row[$rowIndex] ?? '')) ?: '(empty)';
            $c = trim((string) ($row[$colIndex] ?? '')) ?: '(empty)';
            $rowKeys[$r] = true;
            $colKeys[$c] = true;
            $matrix[$r][$c] = ($matrix[$r][$c] ?? 0) + 1;
        }
        fclose($handle);

        if ($matrix === []) {
            return null;
        }

        $rowHeaders = array_slice(array_keys($rowKeys), 0, $limit);
        $colHeaders = array_slice(array_keys($colKeys), 0, $limit);
        $cells = [];
        foreach ($rowHeaders as $r) {
            $line = [];
            foreach ($colHeaders as $c) {
                $line[] = $matrix[$r][$c] ?? 0;
            }
            $cells[] = $line;
        }

        return ['row_headers' => $rowHeaders, 'col_headers' => $colHeaders, 'cells' => $cells];
    }

    /** @return array{headers: list<string>, rows: list<array<string, string>>} */
    private function samplePreview(PlatformDataset $dataset, DatasetAccFilters $filters, int $limit): array
    {
        $paginator = $this->paginateRows($dataset, $filters, 1, $limit);
        $rows = $paginator->items();
        if ($rows === []) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_keys($rows[0]);

        return ['headers' => $headers, 'rows' => $rows];
    }

    /** @return ?array{label: string, value: string, detail?: string} */
    private function kpiValue(PlatformDataset $dataset, string $column, string $metric, DatasetAccFilters $filters): ?array
    {
        if ($metric === 'distinct') {
            $counts = $this->distribution($dataset, $column, $filters, 100000);
            if ($counts === []) {
                return null;
            }

            return [
                'label' => "Distinct {$column}",
                'value' => number_format(count($counts)),
                'detail' => 'Unique non-empty values in filtered rows',
            ];
        }

        if ($metric === 'count') {
            $counts = $this->distribution($dataset, $column, $filters, 100000);
            $nonEmpty = array_sum($counts) - ($counts['(empty)'] ?? 0);

            return [
                'label' => "Non-empty {$column}",
                'value' => number_format($nonEmpty),
                'detail' => 'Rows with a value in this column',
            ];
        }

        $values = $this->numericValues($dataset, $column, $filters);
        if ($values === []) {
            return null;
        }

        return match ($metric) {
            'sum' => ['label' => "Sum of {$column}", 'value' => number_format(array_sum($values), 2)],
            'average' => ['label' => "Average {$column}", 'value' => number_format(array_sum($values) / count($values), 4)],
            'min' => ['label' => "Min {$column}", 'value' => number_format(min($values), 4)],
            'max' => ['label' => "Max {$column}", 'value' => number_format(max($values), 4)],
            default => null,
        };
    }

    /** @return array<string, int> */
    private function histogramBins(PlatformDataset $dataset, string $column, DatasetAccFilters $filters, int $binCount): array
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

        $values = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (! $this->rowMatches($row, $filterIndex, $filters->filterValue)) {
                continue;
            }
            $raw = str_replace(',', '', trim((string) ($row[$valueIndex] ?? '')));
            if (is_numeric($raw)) {
                $values[] = (float) $raw;
            }
        }
        fclose($handle);

        if ($values === []) {
            return [];
        }

        $min = min($values);
        $max = max($values);
        if ($min === $max) {
            return [sprintf('%.2f', $min) => count($values)];
        }

        $width = ($max - $min) / $binCount;
        $bins = array_fill(0, $binCount, 0);
        foreach ($values as $v) {
            $idx = (int) min($binCount - 1, floor(($v - $min) / $width));
            $bins[$idx]++;
        }

        $out = [];
        for ($i = 0; $i < $binCount; $i++) {
            $lo = $min + ($i * $width);
            $hi = $i === $binCount - 1 ? $max : $lo + $width;
            $out[sprintf('%.2f–%.2f', $lo, $hi)] = $bins[$i];
        }

        return $out;
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

    /**
     * @return list<array{x: float, y: float, r: float}>
     */
    private function readXYZBubblePoints(
        PlatformDataset $dataset,
        string $xColumn,
        string $yColumn,
        string $zColumn,
        DatasetAccFilters $filters,
        int $limit,
    ): array {
        $csvPath = $dataset->dataCsvPath();
        if (! is_readable($csvPath)) {
            return [];
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $xIndex = $this->columnIndex($headers, $xColumn);
        $yIndex = $this->columnIndex($headers, $yColumn);
        $zIndex = $this->columnIndex($headers, $zColumn);
        $filterIndex = $this->columnIndex($headers, $filters->filterColumn);

        if ($xIndex === null || $yIndex === null || $zIndex === null) {
            fclose($handle);

            return [];
        }

        $raw = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (! $this->rowMatches($row, $filterIndex, $filters->filterValue)) {
                continue;
            }
            $x = $this->parseNumericCell($row[$xIndex] ?? null);
            $y = $this->parseNumericCell($row[$yIndex] ?? null);
            $z = $this->parseNumericCell($row[$zIndex] ?? null);
            if ($x === null || $y === null || $z === null) {
                continue;
            }
            $raw[] = ['x' => $x, 'y' => $y, 'z' => abs($z)];
            if (count($raw) >= $limit) {
                break;
            }
        }
        fclose($handle);

        if ($raw === []) {
            return [];
        }

        $zMax = max(array_column($raw, 'z')) ?: 1;

        return array_map(function (array $p) use ($zMax): array {
            return [
                'x' => $p['x'],
                'y' => $p['y'],
                'r' => max(4.0, ($p['z'] / $zMax) * 24),
            ];
        }, $raw);
    }

    private function parseNumericCell(mixed $cell): ?float
    {
        $raw = str_replace(',', '', trim((string) ($cell ?? '')));

        return is_numeric($raw) ? (float) $raw : null;
    }
}
