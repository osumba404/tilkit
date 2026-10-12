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
