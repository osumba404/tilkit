<?php

namespace Tests\Unit;

use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\User;
use App\Services\Tilkit\EligibilityGate;
use App\Services\Tilkit\PriorMultiplierMatrix;
use App\Services\Tilkit\WeightingService;
use App\Support\Tilkit\IndicatorHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitPriorAndGateTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    public function test_prior_matrix_resolves_plant_and_sensory_types(): void
    {
        $matrix = app(PriorMultiplierMatrix::class);

        $plant = new TilkitIndicator(['indicator_uid' => '01-PLT-OAK-001']);
        $sensory = new TilkitIndicator(['indicator_uid' => '06-SEN-ODR-001']);

        $this->assertSame(1.35, $matrix->forIndicator($plant));
        $this->assertSame(0.55, $matrix->forIndicator($sensory));
    }

    public function test_db_prior_multiplier_overrides_matrix(): void
    {
        $record = $this->makeRecord([], [
            'code' => '01-PLT-OAK',
            'uid' => '01-PLT-OAK-001',
            'prior_multiplier' => 1.11,
        ]);

        $history = new IndicatorHistory(0, 0, 0, 0, 0);
        $prior = app(WeightingService::class)->compute($record, $history, null)->priorMultiplier;

        $this->assertSame(1.11, $prior);
    }

    public function test_ce_eligibility_prefix_matches_workbook(): void
    {
        $this->assertTrue(EligibilityGate::ceCountsInTally('Counted in tally'));
        $this->assertTrue(EligibilityGate::ceCountsInTally('Counted (simple tally only - no weight)'));
        $this->assertFalse(EligibilityGate::ceCountsInTally('Excluded: not sighted'));
    }

    public function test_is_first_record_for_sole_row(): void
    {
        $record = $this->makeRecord([]);

        $this->assertTrue(app(EligibilityGate::class)->isFirstRecord($record));
        $this->assertSame(1, app(EligibilityGate::class)->evaluateDuplicateMarker($record));
    }

    public function test_exclusion_key_from_ce_label(): void
    {
        $this->assertSame(
            'duplicate',
            EligibilityGate::exclusionKeyFromCeLabel('Excluded: duplicate (later row with same ID, site and season)'),
        );
    }

    /**
     * @param  array<string, mixed>  $recordAttrs
     * @param  array<string, mixed>  $codeAttrs
     */
    private function makeRecord(array $recordAttrs, array $codeAttrs = []): TilkitIndicatorRecord
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
            'code' => $codeAttrs['code'] ?? '01-ANI-FRO',
            'category' => '01',
            'type' => 'ANI',
            'sub_type' => 'FRO',
            'protocol_version' => config('tilkit.weather.protocol_version'),
            'prior_multiplier' => $codeAttrs['prior_multiplier'] ?? null,
        ]);
        $indicator = TilkitIndicator::query()->create([
            'indicator_uid' => $codeAttrs['uid'] ?? '01-ANI-FRO-001',
            'indicator_code_id' => $code->id,
            'project_id' => $tool->id,
            'local_name' => 'Sign',
        ]);

        return TilkitIndicatorRecord::query()->create([
            'indicator_id' => $indicator->id,
            'site_id' => $site->id,
            'season_id' => $season->id,
            ...$recordAttrs,
        ]);
    }
}
