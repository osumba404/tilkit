<?php

namespace Tests\Unit;

use App\Enums\Tilkit\TercileForecast;
use App\Enums\Tilkit\ValidationStatus;
use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\User;
use App\Services\Tilkit\IndicatorHistoryService;
use App\Services\Tilkit\WorkbookRecordService;
use App\Services\Tilkit\TilkitEngineService;
use App\Services\Tilkit\WeightingService;
use App\Support\Tilkit\IndicatorHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitEngineTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    public function test_frog_first_season_weight_cap_final_six(): void
    {
        $record = $this->makeRecord([
            'sighted' => true,
            'confirmed' => true,
            'approved' => true,
            'validation_status' => ValidationStatus::CommunityPlenaryEndorsed,
            'tercile_forecast' => TercileForecast::AboveNormal,
            'current_reliability' => 3,
            'decision_relevance' => 3,
        ]);

        $history = new IndicatorHistory(corroboration: 1, scoredSeasons: 0, hits: 0, misses: 0, falseStarts: 0);
        $weights = app(WeightingService::class)->compute($record, $history, null);

        $this->assertSame('rating-based', $weights->phase);
        $this->assertSame(2.0, $weights->baseWeight);
        $this->assertSame(6.0, $weights->finalWeight);
        $this->assertTrue($weights->corroborationCapApplied);
    }

    public function test_engine_persists_ce_and_cb_on_save(): void
    {
        $record = $this->makeRecord([
            'sighted' => true,
            'confirmed' => true,
            'approved' => true,
            'validation_status' => ValidationStatus::CommunityPlenaryEndorsed,
            'tercile_forecast' => TercileForecast::AboveNormal,
            'current_reliability' => 3,
            'decision_relevance' => 3,
        ]);

        $this->assertSame(3, $record->fresh()->current_reliability?->value);
        app(TilkitEngineService::class)->recalculateRecord($record);

        $fresh = $record->fresh();
        $hist = app(IndicatorHistoryService::class)->forIndicator($fresh->indicator_id);
        $weights = app(WeightingService::class)->compute($fresh, $hist, null);
        $this->assertSame(6.0, $weights->finalWeight);

        $ce = $record->workbookValues()->where('column_letter', 'CE')->first();
        $cb = $record->workbookValues()->where('column_letter', 'CB')->first();
        $cg = $record->workbookValues()->where('column_letter', 'CG')->first();

        $this->assertNotNull($ce);
        $this->assertSame('Counted in tally', $ce->value_text);
        $this->assertNotNull($cb);
        $this->assertSame(6.0, (float) ($cb->value_number ?? $cg?->value_number));
    }

    /** @param  array<string, mixed>  $attrs */
    private function makeRecord(array $attrs): TilkitIndicatorRecord
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);
        $program = TilkitProgram::query()->create(['project_id' => $tool->id, 'name' => 'Test program']);
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
            'local_name' => 'Frog',
        ]);

        return TilkitIndicatorRecord::query()->create([
            'indicator_id' => $indicator->id,
            'site_id' => $site->id,
            'season_id' => $season->id,
            ...$attrs,
        ]);
    }
}
