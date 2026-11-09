<?php

namespace Tests\Feature;

use App\Services\Tilkit\TilkitDeepAnalysisDataset;
use App\Support\Tilkit\WeatherTool;
use Database\Seeders\KenyaGhanaPilotSeeder;
use Database\Seeders\TilkitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TilkitDeepAnalysisDatasetTest extends TestCase
{
    use RefreshDatabase;

    public function test_dataset_returns_rows_for_kenya_program_site_and_season(): void
    {
        $this->seed(TilkitSeeder::class);
        config(['tilkit.weather.demo.owner_email' => 'superadmin@tilkit.local']);
        $this->seed(KenyaGhanaPilotSeeder::class);

        $tool = WeatherTool::resolve();
        $dataset = app(TilkitDeepAnalysisDataset::class);
        $schema = $dataset->schema($tool);

        $kenya = collect($schema['filters']['programs'])->firstWhere('name', 'Kenya NEO');
        $isiolo = collect($schema['filters']['sites'])->first(fn (array $s) => str_contains($s['name'], 'Isiolo'));

        $this->assertNotNull($kenya, 'Kenya NEO program in schema');
        $this->assertNotNull($isiolo, 'Isiolo site in schema');

        $seasonsById = collect($schema['filters']['seasons'])->keyBy('id');
        $scope = collect($schema['filters']['scopes'])->first(function (array $s) use ($kenya, $isiolo, $seasonsById) {
            if ($s['program_id'] !== $kenya['id'] || $s['site_id'] !== $isiolo['id']) {
                return false;
            }
            $season = $seasonsById->get($s['season_id']);

            return $season && ($season['label'] ?? '') === 'MAM 2026';
        });

        $this->assertNotNull($scope, 'Kenya / Isiolo / MAM 2026 scope in schema');

        $scoped = $dataset->rows($tool, [
            'program_id' => $scope['program_id'],
            'site_ids' => [$scope['site_id']],
            'season_ids' => [$scope['season_id']],
        ]);

        $this->assertGreaterThan(0, $dataset->rows($tool, [])['row_count'], 'Expected exportable rows overall');
        $this->assertGreaterThan(0, $scoped['row_count'], 'Expected rows for Kenya / Isiolo / MAM 2026');
    }
}
