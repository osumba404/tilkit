<?php

namespace Tests\Feature;

use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitWeatherSchemaTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    public function test_tool_tilkit_overview_requires_auth(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);

        $this->get(route('tools.tilkit.overview', $tool))->assertRedirect();
    }

    public function test_indicator_record_unique_per_site_season(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);

        $site = TilkitSite::query()->create([
            'project_id' => $tool->id,
            'name' => 'Mutirithia',
            'country' => 'KE',
        ]);
        $season = TilkitSeason::query()->create([
            'project_id' => $tool->id,
            'label' => 'OND 2026',
            'protocol_version' => config('tilkit.weather.protocol_version'),
        ]);
        $code = TilkitIndicatorCode::query()->create([
            'code' => '01-ANI-FRO',
            'category' => '01',
            'type' => 'ANI',
            'sub_type' => 'FRO',
            'protocol_version' => config('tilkit.weather.protocol_version'),
        ]);
        $indicator = TilkitIndicator::query()->create([
            'indicator_uid' => '01-ANI-FRO-001',
            'indicator_code_id' => $code->id,
            'project_id' => $tool->id,
            'local_name' => 'Frog croaking',
        ]);

        TilkitIndicatorRecord::query()->create([
            'indicator_id' => $indicator->id,
            'site_id' => $site->id,
            'season_id' => $season->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        TilkitIndicatorRecord::query()->create([
            'indicator_id' => $indicator->id,
            'site_id' => $site->id,
            'season_id' => $season->id,
        ]);
    }
}
