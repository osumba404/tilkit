<?php

namespace App\Services\Datasets;

use App\Models\PlatformDataset;

class PhpDatasetAnalyzer
{
    /** @return array{summary: string, chart_spec: ?array, stats: array<string, mixed>} */
    public function analyze(PlatformDataset $dataset, string $prompt): array
    {
        $promptLower = strtolower($prompt);
        $columns = $dataset->columns->pluck('name')->all();
        $csvPath = $dataset->dataCsvPath();

        $targetColumn = $this->matchColumn($promptLower, $columns);
        if ($targetColumn === null && $columns !== []) {
            $targetColumn = $columns[0];
        }

        if (str_contains($promptLower, 'pie') || str_contains($promptLower, 'donut')) {
            $counts = $this->valueCounts($csvPath, $targetColumn, 12);

            return [
                'summary' => "Distribution of {$targetColumn}: ".count($counts).' categories shown. Largest bucket: '
                    .array_key_first($counts).' ('.reset($counts).' rows).',
                'chart_spec' => [
                    'type' => 'pie',
                    'labels' => array_keys($counts),
                    'values' => array_values($counts),
                    'title' => $targetColumn,
                ],
                'stats' => ['column' => $targetColumn, 'counts' => $counts],
            ];
        }

        if ($this->promptWantsLineGraph($promptLower) || str_contains($promptLower, 'histogram')) {
            $line = $this->buildLineOrHistogramChart($dataset, $promptLower, $columns, $csvPath);
            if ($line !== null) {
                return $line;
            }
        }

        if (str_contains($promptLower, 'scatter')) {
            $scatter = $this->buildScatterChart($dataset, $promptLower, $columns, $csvPath);
            if ($scatter !== null) {
                return $scatter;
            }
        }

        if (str_contains($promptLower, 'bar') || str_contains($promptLower, 'count')) {
            $counts = $this->valueCounts($csvPath, $targetColumn, 20);

            return [
                'summary' => "Counts by {$targetColumn}. Top category: ".array_key_first($counts).' ('.reset($counts).' rows).',
                'chart_spec' => [
                    'type' => 'bar',
                    'labels' => array_keys($counts),
                    'values' => array_values($counts),
                    'title' => 'Count by '.$targetColumn,
                ],
                'stats' => ['column' => $targetColumn, 'counts' => $counts],
            ];
        }

        if (str_contains($promptLower, 'mean') || str_contains($promptLower, 'average')) {
            $stats = $this->numericStats($csvPath, $targetColumn);

            return [
                'summary' => $stats
                    ? "Numeric summary for {$targetColumn}: mean {$stats['mean']}, min {$stats['min']}, max {$stats['max']} (n={$stats['count']})."
                    : "Column {$targetColumn} is not numeric enough for mean; try a category column.",
                'chart_spec' => null,
                'stats' => $stats ?? [],
            ];
        }

        $counts = $this->valueCounts($csvPath, $targetColumn, 10);

        return [
            'summary' => "Quick profile of {$targetColumn} across {$dataset->row_count} rows. "
                .count($counts).' distinct values in the top buckets.',
            'chart_spec' => [
                'type' => 'bar',
                'labels' => array_keys($counts),
                'values' => array_values($counts),
                'title' => $targetColumn,
            ],
            'stats' => ['column' => $targetColumn],
        ];
    }

    /** @return ?array{type: string, labels: list<string>, values: list<int>, title: string} */
    public function defaultDistributionChart(PlatformDataset $dataset, string $prompt): ?array
    {
        $columns = $dataset->columns->pluck('name')->all();
        $targetColumn = $this->matchColumn(strtolower($prompt), $columns)
            ?? $dataset->columns->firstWhere('inferred_type', 'category')?->name
            ?? ($columns[0] ?? null);

        if ($targetColumn === null) {
            return null;
        }

        $counts = $this->valueCounts($dataset->dataCsvPath(), $targetColumn, 12);
        if ($counts === []) {
            return null;
        }

        if ($this->promptWantsLineGraph(strtolower($prompt))) {
            $built = $this->buildLineOrHistogramChart($dataset, strtolower($prompt), $columns, $dataset->dataCsvPath());
            if ($built !== null) {
                return $built['chart_spec'];
            }
        }

        $type = str_contains(strtolower($prompt), 'pie') ? 'pie' : 'bar';

        return [
            'type' => $type,
            'labels' => array_keys($counts),
            'values' => array_values($counts),
            'title' => $targetColumn,
        ];
    }

    private function promptWantsLineGraph(string $promptLower): bool
    {
        return str_contains($promptLower, 'line')
            || str_contains($promptLower, 'graph')
            || str_contains($promptLower, 'trend')
            || str_contains($promptLower, 'time series')
            || str_contains($promptLower, 'over time');
    }

    /** @param list<string> $columns @return ?array{summary: string, chart_spec: array, stats: array} */
    private function buildLineOrHistogramChart(PlatformDataset $dataset, string $promptLower, array $columns, string $csvPath): ?array
    {
        $xColumn = $this->matchColumn($promptLower, $columns);
        $dateCol = $dataset->columns->first(fn ($c) => in_array($c->inferred_type, ['date', 'datetime', 'timestamp'], true));
        if ($xColumn === null) {
            $xColumn = $dateCol?->name ?? ($columns[0] ?? null);
        }

        $yColumn = $this->matchNumericColumn($dataset, $promptLower, $columns, $xColumn);
        if ($xColumn === null || $yColumn === null) {
            return null;
        }

        if (str_contains($promptLower, 'histogram')) {
            $bins = $this->histogramBins($csvPath, $yColumn, 10);
            if ($bins === []) {
                return null;
            }

            return [
                'summary' => "Histogram of {$yColumn} in ".count($bins).' bins.',
                'chart_spec' => [
                    'type' => 'bar',
                    'labels' => array_keys($bins),
                    'values' => array_values($bins),
                    'title' => "Histogram · {$yColumn}",
                ],
                'stats' => ['column' => $yColumn, 'bins' => $bins],
            ];
        }

        $series = $this->readXYSeries($csvPath, $xColumn, $yColumn, 120);
        if ($series['labels'] === []) {
            return null;
        }

        return [
            'summary' => "Line graph of {$yColumn} across {$xColumn} (".count($series['labels']).' points).',
            'chart_spec' => [
                'type' => 'line',
                'labels' => $series['labels'],
                'values' => $series['values'],
                'title' => "{$yColumn} over {$xColumn}",
            ],
            'stats' => ['x' => $xColumn, 'y' => $yColumn, 'points' => count($series['labels'])],
        ];
    }

    /** @param list<string> $columns @return ?array{summary: string, chart_spec: array, stats: array} */
    private function buildScatterChart(PlatformDataset $dataset, string $promptLower, array $columns, string $csvPath): ?array
    {
        $numeric = $dataset->columns
            ->filter(fn ($c) => in_array($c->inferred_type, ['integer', 'decimal', 'number'], true))
            ->pluck('name')
            ->all();

        if (count($numeric) < 2) {
            return null;
        }

        $xColumn = $this->matchColumn($promptLower, $numeric) ?? $numeric[0];
        $yColumn = null;
        foreach ($numeric as $name) {
            if ($name !== $xColumn && str_contains($promptLower, strtolower($name))) {
                $yColumn = $name;
                break;
            }
        }
        $yColumn ??= ($numeric[1] ?? null);
        if ($yColumn === null || $yColumn === $xColumn) {
            $yColumn = collect($numeric)->first(fn ($n) => $n !== $xColumn);
        }

        $series = $this->readXYSeries($csvPath, $xColumn, $yColumn, 200);
        $points = [];
        foreach ($series['values'] as $i => $y) {
            $xLabel = $series['labels'][$i] ?? '';
            $x = is_numeric(str_replace(',', '', $xLabel)) ? (float) str_replace(',', '', $xLabel) : $i;
            $points[] = ['x' => $x, 'y' => $y];
        }

        if ($points === []) {
            return null;
        }

        return [
            'summary' => "Scatter plot of {$yColumn} vs {$xColumn} (".count($points).' points).',
            'chart_spec' => [
                'type' => 'scatter',
                'points' => $points,
                'title' => "{$yColumn} vs {$xColumn}",
            ],
            'stats' => ['x' => $xColumn, 'y' => $yColumn],
        ];
    }

    /** @param list<string> $columns */
    private function matchNumericColumn(PlatformDataset $dataset, string $promptLower, array $columns, ?string $exclude): ?string
    {
        foreach ($columns as $col) {
            if ($col === $exclude) {
                continue;
            }
            if (str_contains($promptLower, strtolower($col))) {
                $meta = $dataset->columns->firstWhere('name', $col);
                if ($meta && in_array($meta->inferred_type, ['integer', 'decimal', 'number'], true)) {
                    return $col;
                }
            }
        }

        return $dataset->columns
            ->first(fn ($c) => $c->name !== $exclude && in_array($c->inferred_type, ['integer', 'decimal', 'number'], true))
            ?->name;
    }

    /** @return array{labels: list<string>, values: list<float>} */
    private function readXYSeries(string $csvPath, string $xColumn, string $yColumn, int $limit): array
    {
        if (! is_readable($csvPath)) {
            return ['labels' => [], 'values' => []];
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $xIndex = array_search($xColumn, $headers, true);
        $yIndex = array_search($yColumn, $headers, true);
        if ($xIndex === false || $yIndex === false) {
            fclose($handle);

            return ['labels' => [], 'values' => []];
        }

        $labels = [];
        $values = [];
        while (($row = fgetcsv($handle)) !== false) {
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

    /** @return array<string, int> */
    private function histogramBins(string $csvPath, string $column, int $binCount): array
    {
        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $index = array_search($column, $headers, true);
        if ($index === false) {
            fclose($handle);

            return [];
        }

        $values = [];
        while (($row = fgetcsv($handle)) !== false) {
            $raw = str_replace(',', '', trim((string) ($row[$index] ?? '')));
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
            $key = sprintf('%.2f–%.2f', $lo, $hi);
            $out[$key] = $bins[$i];
        }

        return $out;
    }

    /** @param list<string> $columns */
    private function matchColumn(string $prompt, array $columns): ?string
    {
        foreach ($columns as $col) {
            if (str_contains($prompt, strtolower($col))) {
                return $col;
            }
        }

        return null;
    }

    /** @return array<string, int> */
    private function valueCounts(string $csvPath, ?string $column, int $limit): array
    {
        if ($column === null || ! is_readable($csvPath)) {
            return [];
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $index = array_search($column, $headers, true);
        if ($index === false) {
            fclose($handle);

            return [];
        }

        $counts = [];
        while (($row = fgetcsv($handle)) !== false) {
            $val = trim((string) ($row[$index] ?? ''));
            if ($val === '') {
                $val = '(empty)';
            }
            $counts[$val] = ($counts[$val] ?? 0) + 1;
        }
        fclose($handle);

        arsort($counts);

        return array_slice($counts, 0, $limit, true);
    }

    /** @return ?array{mean: float, min: float, max: float, count: int} */
    private function numericStats(string $csvPath, ?string $column): ?array
    {
        if ($column === null) {
            return null;
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle) ?: [];
        $index = array_search($column, $headers, true);
        if ($index === false) {
            fclose($handle);

            return null;
        }

        $values = [];
        while (($row = fgetcsv($handle)) !== false) {
            $raw = str_replace(',', '', (string) ($row[$index] ?? ''));
            if (is_numeric($raw)) {
                $values[] = (float) $raw;
            }
        }
        fclose($handle);

        if ($values === []) {
            return null;
        }

        return [
            'mean' => round(array_sum($values) / count($values), 4),
            'min' => min($values),
            'max' => max($values),
            'count' => count($values),
        ];
    }
}
