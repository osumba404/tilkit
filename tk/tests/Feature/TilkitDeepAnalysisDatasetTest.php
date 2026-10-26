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
        $mam = collect($schema['filters']['seasons'])->firstWhere('label', 'MAM 2026');

        $this->assertNotNull($kenya, 'Kenya NEO program in schema');
        $this->assertNotNull($isiolo, 'Isiolo site in schema');
        $this->assertNotNull($mam, 'MAM 2026 season in schema');

        $scoped = $dataset->rows($tool, [
            'program_id' => $kenya['id'],
            'site_ids' => [$isiolo['id']],
            'season_ids' => [$mam['id']],
        ]);

        $this->assertGreaterThan(0, $dataset->rows($tool, [])['row_count'], 'Expected exportable rows overall');
        $this->assertGreaterThan(0, $scoped['row_count'], 'Expected rows for Kenya / Isiolo / MAM 2026');
    }
}
