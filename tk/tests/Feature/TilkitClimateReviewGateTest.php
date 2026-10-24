<?php

namespace Tests\Feature;

use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Services\Tilkit\TilkitProgramContext;
use App\Models\Tilkit\TilkitWeightReview;
use App\Models\User;
use App\Services\Tilkit\TilkitEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitClimateReviewGateTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    public function test_pending_climate_review_excludes_record_from_tally(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);
        $program = TilkitProgram::query()->create(['project_id' => $tool->id, 'name' => 'Test program']);
        app(TilkitProgramContext::class)->setActive($tool, $program->id);
        $site = TilkitSite::query()->create([
            'project_id' => $tool->id,
            'program_id' => $program->id,
            'name' => 'Site',
            'continent' => 'Africa',
            'country' => 'KE',
        ]);
        $season = TilkitSeason::query()->create([
            'project_id' => $tool->id,
            'program_id' => $program->id,
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
        ]);
        $record = TilkitIndicatorRecord::query()->create([
            'indicator_id' => $indicator->id,
            'site_id' => $site->id,
            'season_id' => $season->id,
            'sighted' => true,
            'confirmed' => true,
            'approved' => true,
            'tercile_forecast' => 3,
            'current_reliability' => 3,
            'decision_relevance' => 3,
        ]);
        TilkitWeightReview::query()->create([
            'indicator_record_id' => $record->id,
            'status' => 'pending',
            'climate_flag' => 'STRONG',
        ]);

        $result = app(TilkitEngineService::class)->runTally($site->id, $season->id);
        $this->assertSame(0, $result->countedRows);
        $this->assertArrayHasKey('climate_review_pending', $result->exclusionCounts);
    }
}
