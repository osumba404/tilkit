<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DcProject;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Http\RedirectResponse;

trait RedirectsTilkitWeatherModule
{
    protected function redirectIfTilkitWeatherModule(DcProject $tool, string $section = 'overview'): ?RedirectResponse
    {
        if (! WeatherTool::isWeatherModule($tool)) {
            return null;
        }

        $route = match ($section) {
            'records' => 'tools.tilkit.records.index',
            'insights' => 'tools.tilkit.insights',
            'team' => 'tools.tilkit.team',
            'export' => 'tools.tilkit.export.csv',
            'data-desk' => 'tools.tilkit.data-desk',
            default => 'tools.tilkit.overview',
        };

        return redirect()->route($route, $tool);
    }
}
