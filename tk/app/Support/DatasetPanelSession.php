<?php

namespace App\Support;

use App\Models\PlatformDataset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DatasetPanelSession
{
    /** @return list<array{id: string, type: string, column: ?string, x_column: ?string, y_column: ?string, z_column: ?string, metric: ?string}> */
    public static function charts(Request $request, PlatformDataset $dataset): array
    {
        $key = self::sessionKey($request->user(), $dataset);

        return $request->session()->get($key, []);
    }

    /** @param  list<array{id: string, type: string, column: ?string, x_column: ?string, y_column: ?string, z_column: ?string, metric: ?string}>  $charts */
    public static function save(Request $request, PlatformDataset $dataset, array $charts): void
    {
        $max = (int) config('tilkit.dataset_analysis.max_dashboard_charts', 12);
        $request->session()->put(self::sessionKey($request->user(), $dataset), array_slice($charts, 0, $max));
    }

    /** @param  array{id?: string, type: string, column: ?string, x_column: ?string, y_column: ?string, z_column: ?string, metric: ?string}  $definition */
    public static function append(Request $request, PlatformDataset $dataset, array $definition): bool
    {
        $charts = self::charts($request, $dataset);
        $max = (int) config('tilkit.dataset_analysis.max_dashboard_charts', 12);
        if (count($charts) >= $max) {
            return false;
        }

        $definition['id'] = $definition['id'] ?? ('acc_chart_'.Str::uuid());
        $charts[] = $definition;
        self::save($request, $dataset, $charts);

        return true;
    }

    public static function remove(Request $request, PlatformDataset $dataset, string $panelId): void
    {
        $charts = array_values(array_filter(
            self::charts($request, $dataset),
            fn (array $c) => ($c['id'] ?? '') !== $panelId,
        ));
        self::save($request, $dataset, $charts);
    }

    public static function clear(Request $request, PlatformDataset $dataset): void
    {
        $request->session()->forget(self::sessionKey($request->user(), $dataset));
    }

    public static function sessionKey(User $user, PlatformDataset $dataset): string
    {
        return "dataset_panels.{$user->id}.{$dataset->id}";
    }

    /** @return array{id: string, type: string, column: ?string, x_column: ?string, y_column: ?string, z_column: ?string, metric: ?string, colors: list<string>} */
    public static function definitionFromRequest(Request $request, PlatformDataset $dataset, ?string $preserveId = null): array
    {
        $columns = $dataset->columns->pluck('name')->all();
        $pick = static fn (?string $name) => $name && in_array($name, $columns, true) ? $name : null;

        $allowedTypes = DatasetChartSchema::allowedTypes();
        $type = in_array($request->input('type'), $allowedTypes, true) ? $request->input('type') : 'bar';

        $allowedMetrics = ['sum', 'average', 'count', 'min', 'max', 'distinct'];
        $metric = in_array($request->input('metric'), $allowedMetrics, true) ? $request->input('metric') : 'count';

        return [
            'id' => $preserveId ?? ('acc_chart_'.Str::uuid()),
            'type' => $type,
            'column' => $pick($request->input('column')),
            'x_column' => $pick($request->input('x_column')),
            'y_column' => $pick($request->input('y_column')),
            'z_column' => $pick($request->input('z_column')),
            'metric' => $metric,
            'colors' => DatasetPanelColors::fromRequest($request),
        ];
    }

    /** @param  array{id: string, type: string, column: ?string, x_column: ?string, y_column: ?string, z_column: ?string, metric: ?string, colors: list<string>}  $definition */
    public static function update(Request $request, PlatformDataset $dataset, string $panelId, array $definition): bool
    {
        $charts = self::charts($request, $dataset);
        $found = false;
        foreach ($charts as $i => $chart) {
            if (($chart['id'] ?? '') !== $panelId) {
                continue;
            }
            $definition['id'] = $panelId;
            $charts[$i] = $definition;
            $found = true;
            break;
        }
        if (! $found) {
            return false;
        }
        self::save($request, $dataset, $charts);

        return true;
    }

    public static function find(Request $request, PlatformDataset $dataset, string $panelId): ?array
    {
        foreach (self::charts($request, $dataset) as $chart) {
            if (($chart['id'] ?? '') === $panelId) {
                return $chart;
            }
        }

        return null;
    }
}
