<?php

namespace Tests\Feature;

use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitSiteMonthIndicatorCode;
use App\Models\User;
use App\Services\Tilkit\SiteIndicatorCatalogService;
use App\Support\Tilkit\SeasonMonthCoverage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitSiteMonthIndicatorsTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    /** @return array{program: TilkitProgram, site: TilkitSite, season: TilkitSeason, code: TilkitIndicatorCode, indicator: TilkitIndicator} */
    private function seedSiteSeasonSign(User $user): array
    {
        $tool = $this->tilkitToolFor($user);
        $program = TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => 'Test program',
            'protocol_version' => config('tilkit.weather.protocol_version'),
        ]);
        $site = TilkitSite::query()->create([
            'project_id' => $tool->id,
            'program_id' => $program->id,
            'name' => 'Demo site',
            'country' => 'KE',
        ]);
        $season = TilkitSeason::query()->create([
            'project_id' => $tool->id,
            'program_id' => $program->id,
            'site_id' => $site->id,
            'label' => 'MAM',
            'start_month' => 3,
            'end_month' => 5,
            'protocol_version' => config('tilkit.weather.protocol_version'),
        ]);
        $code = TilkitIndicatorCode::query()->create([
            'code' => '01-ANI-BMG',
            'category' => '01',
            'type' => 'ANI',
            'sub_type' => 'BMG',
            'label' => 'Seasonal migration or movement',
            'protocol_version' => config('tilkit.weather.protocol_version'),
        ]);
        $indicator = TilkitIndicator::query()->create([
            'indicator_uid' => '01-ANI-BMG-001',
            'indicator_code_id' => $code->id,
            'project_id' => $tool->id,
            'local_name' => 'Demo bird flock',
        ]);

        return compact('program', 'site', 'season', 'code', 'indicator');
    }

    public function test_can_save_and_load_monthly_code_assignments_for_site(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);
        $ctx = $this->seedSiteSeasonSign($user);

        $this->actingAs($user)
            ->putJson(route('tools.tilkit.setup.site.month-indicators.update', [$tool, $ctx['site']]), [
                'assignments' => [
                    '3' => [$ctx['code']->id],
                    '4' => [$ctx['code']->id],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('tilkit_site_month_indicator_codes', [
            'site_id' => $ctx['site']->id,
            'calendar_month' => 3,
            'indicator_code_id' => $ctx['code']->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('tools.tilkit.setup.site.month-indicators', [$tool, $ctx['site']]))
            ->assertOk()
            ->assertJsonPath('assignments.3.0', $ctx['code']->id);
    }

    public function test_season_union_deduplicates_same_code_across_months(): void
    {
        $user = User::factory()->create();
        $ctx = $this->seedSiteSeasonSign($user);

        TilkitSiteMonthIndicatorCode::query()->create([
            'project_id' => $ctx['site']->project_id,
            'program_id' => $ctx['program']->id,
            'site_id' => $ctx['site']->id,
            'calendar_month' => 3,
            'indicator_code_id' => $ctx['code']->id,
        ]);
        TilkitSiteMonthIndicatorCode::query()->create([
            'project_id' => $ctx['site']->project_id,
            'program_id' => $ctx['program']->id,
            'site_id' => $ctx['site']->id,
            'calendar_month' => 4,
            'indicator_code_id' => $ctx['code']->id,
        ]);

        $union = app(SiteIndicatorCatalogService::class)->codeIdsForSeasonUnion($ctx['site'], $ctx['season']);
        $this->assertSame([$ctx['code']->id], $union);
        $this->assertSame([3, 4, 5], SeasonMonthCoverage::monthsInSeason(3, 5));
    }

    public function test_capture_rejects_indicator_not_in_site_month_catalogue(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);
        $ctx = $this->seedSiteSeasonSign($user);

        $this->actingAs($user)
            ->post(route('tools.tilkit.records.store', $tool), [
                'indicator_id' => $ctx['indicator']->id,
                'site_id' => $ctx['site']->id,
                'season_id' => $ctx['season']->id,
                'collection_date' => '2026-04-15',
                'collection_year' => 2026,
            ])
            ->assertStatus(422);
    }

    public function test_capture_allows_indicator_when_site_month_and_season_match(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);
        $ctx = $this->seedSiteSeasonSign($user);

        TilkitSiteMonthIndicatorCode::query()->create([
            'project_id' => $ctx['site']->project_id,
            'program_id' => $ctx['program']->id,
            'site_id' => $ctx['site']->id,
            'calendar_month' => 4,
            'indicator_code_id' => $ctx['code']->id,
        ]);

        $this->actingAs($user)
            ->post(route('tools.tilkit.records.store', $tool), [
                'indicator_id' => $ctx['indicator']->id,
                'site_id' => $ctx['site']->id,
                'season_id' => $ctx['season']->id,
                'collection_date' => '2026-04-15',
                'collection_year' => 2026,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->getJson(route('tools.tilkit.capture.indicator-options', $tool).'?'.http_build_query([
                'site_id' => $ctx['site']->id,
                'season_id' => $ctx['season']->id,
                'collection_date' => '2026-04-15',
            ]))
            ->assertOk()
            ->assertJsonPath('allowed_code_ids.0', $ctx['code']->id)
            ->assertJsonCount(1, 'indicators');
    }
}
