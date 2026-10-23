<?php

namespace Tests\Feature;

use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitWeightReview;
use App\Models\User;
use App\Services\Tilkit\ClimateWeightReviewService;
use App\Services\Tilkit\TilkitEngineService;
use App\Services\Tilkit\WorkbookDemoImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitDemoTallyParityTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    public function test_demo_workbook_ond_2026_tally_matches_excel(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);

        $fixture = base_path('tests/Fixtures/Tilkit/demo_workbook_full.json');
        app(WorkbookDemoImporter::class)->importFixture($tool, $fixture);

        $site = TilkitSite::query()->where('project_id', $tool->id)->where('name', 'Mutirithia (DEMO)')->firstOrFail();
        $season = TilkitSeason::query()->where('project_id', $tool->id)->where('label', 'OND 2026')->firstOrFail();

        $engine = app(TilkitEngineService::class);
        $engine->recalculateSiteSeason($site->id, $season->id);
        $recordIds = TilkitIndicatorRecord::query()
            ->where('site_id', $site->id)
            ->where('season_id', $season->id)
            ->pluck('id');
        $reviews = TilkitWeightReview::query()->whereIn('indicator_record_id', $recordIds)->where('status', 'pending')->get();
        $signOff = app(ClimateWeightReviewService::class);
        foreach ($reviews as $review) {
            $signOff->signOff($review, $user);
        }

        $result = $engine->runTally($site->id, $season->id);

        $this->assertSame(8, $result->countedRows);
        // Excel lists 11 rows for the site/season (incl. duplicate frog row); DB keeps one record per indicator.
        $this->assertSame(10, $result->rowsLoggedWithId);
        $this->assertSame('OK', $result->reconciliation);
        $this->assertSame(50.0, $result->simple[3]['percent']);
        $this->assertSame(25.0, $result->simple[2]['percent']);
        $this->assertSame(25.0, $result->simple[1]['percent']);
        $this->assertEqualsWithDelta(58.06, $result->weighted[3]['percent'], 0.1);
        $this->assertEqualsWithDelta(12.9, $result->weighted[2]['percent'], 0.2);
        $this->assertEqualsWithDelta(29.03, $result->weighted[1]['percent'], 0.2);
        $this->assertSame('Yes', $result->simpleWeightedAgreeLabel);
    }
}
