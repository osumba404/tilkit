<?php

namespace Tests\Feature;

use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\User;
use App\Services\Tilkit\TilkitEngineService;
use App\Services\Tilkit\WorkbookDemoImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitSixSignParityTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    public function test_six_sign_weighting_options_example_within_tolerance(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);
        $fixture = base_path('tests/Fixtures/Tilkit/six_sign_ond2026.json');
        $meta = json_decode(file_get_contents($fixture), true, 512, JSON_THROW_ON_ERROR);

        $tmp = tempnam(sys_get_temp_dir(), 'six_').'.json';
        file_put_contents($tmp, json_encode(['rows' => $meta['rows']], JSON_THROW_ON_ERROR));
        app(WorkbookDemoImporter::class)->importFixture($tool, $tmp);
        @unlink($tmp);

        $site = TilkitSite::query()->where('name', 'Six-sign demo')->firstOrFail();
        $season = TilkitSeason::query()->where('label', 'OND 2026')->firstOrFail();
        $result = app(TilkitEngineService::class)->runTally($site->id, $season->id);

        $expected = $meta['expected'];
        foreach ([3, 2, 1] as $k) {
            $this->assertEqualsWithDelta($expected['simple'][(string) $k], $result->simple[$k]['percent'], 1.0, "simple tercile {$k}");
            $this->assertEqualsWithDelta($expected['weighted'][(string) $k], $result->weighted[$k]['percent'], 2.0, "weighted tercile {$k}");
            $this->assertEqualsWithDelta($expected['priority_adjusted'][(string) $k], $result->priorityAdjusted[$k]['percent'], 3.0, "priority tercile {$k}");
        }
        $this->assertSame(3, $result->leadingWeighted);
    }
}
