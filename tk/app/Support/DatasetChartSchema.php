<?php

namespace App\Support;

class DatasetChartSchema
{
    /** @return list<string> */
    public static function allowedTypes(): array
    {
        return [
            'pie', 'doughnut', 'bar', 'line', 'area', 'histogram', 'scatter', '3d_bubble',
            'table', 'stats', 'top_list', 'crosstab', 'sample_rows', 'kpi',
        ];
    }

    /** 0 = none, 1 = single column, 2 = X+Y, 3 = X+Y+Z */
    public static function axisCount(string $type): int
    {
        return match ($type) {
            'sample_rows' => 0,
            'pie', 'doughnut', 'bar', 'histogram', 'table', 'stats', 'top_list', 'kpi' => 1,
            'line', 'area', 'scatter', 'crosstab' => 2,
            '3d_bubble' => 3,
            default => 1,
        };
    }

    public static function usesMetric(string $type): bool
    {
        return $type === 'kpi';
    }

    /**
     * @param  array{type: string, column?: ?string, x_column?: ?string, y_column?: ?string, z_column?: ?string}  $def
     */
    public static function validate(array $def): ?string
    {
        $type = $def['type'] ?? 'bar';
        $count = self::axisCount($type);

        if ($count === 0) {
            return null;
        }

        if ($count === 1) {
            if (empty($def['column'])) {
                return 'Pick one column for this view.';
            }

            return null;
        }

        if ($count === 2) {
            if (empty($def['x_column']) || empty($def['y_column'])) {
                return 'Pick both X and Y fields for this 2D chart.';
            }
            if ($def['x_column'] === $def['y_column']) {
                return 'X and Y must be different columns.';
            }

            return null;
        }

        if (empty($def['x_column']) || empty($def['y_column']) || empty($def['z_column'])) {
            return 'Pick X, Y, and Z fields for this 3D chart.';
        }
        $unique = array_unique([$def['x_column'], $def['y_column'], $def['z_column']]);
        if (count($unique) < 3) {
            return 'X, Y, and Z must be three different columns.';
        }

        return null;
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            '3d_bubble' => '3D bubble (X, Y, Z)',
            'sample_rows' => 'Data sample',
            default => str_replace('_', ' ', $type),
        };
    }
}
