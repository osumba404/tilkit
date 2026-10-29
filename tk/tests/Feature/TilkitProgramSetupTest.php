<?php

namespace Tests\Feature;

use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\User;
use App\Services\Tilkit\GeographyCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitProgramSetupTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    public function test_user_can_create_program_and_site_with_geography(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                ['name' => 'Kenya', 'iso2' => 'KE', 'region' => 'Africa'],
                ['name' => 'Ghana', 'iso2' => 'GH', 'region' => 'Africa'],
            ], 200),
        ]);
        app(GeographyCatalogService::class)->clearCache();

        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);

        $this->actingAs($user)
            ->post(route('tools.tilkit.setup.program', $tool), [
                'name' => 'Ghana pilot program (2019–2026)',
            ])
            ->assertRedirect();

        $program = TilkitProgram::query()->where('project_id', $tool->id)->where('name', 'like', 'Ghana pilot%')->first();
        $this->assertNotNull($program);

        $this->actingAs($user)
            ->post(route('tools.tilkit.setup.site', $tool), [
                'program_id' => $program->id,
                'name' => 'Tamale community plot',
                'continent' => 'Africa',
                'country' => 'GH',
                'county' => 'Northern Region',
                'ward' => 'Tamale Metro',
            ])
            ->assertRedirect();

        $site = TilkitSite::query()->where('program_id', $program->id)->first();
        $this->assertSame('Tamale community plot', $site->name);
        $this->assertSame('GH', $site->country);
        $this->assertSame('Africa', $site->continent);

        $this->actingAs($user)
            ->post(route('tools.tilkit.setup.season', $tool), [
                'program_id' => $program->id,
                'site_id' => $site->id,
                'label' => 'Full year cycle',
                'start_month' => 1,
                'end_month' => 12,
            ])
            ->assertRedirect();

        $this->assertTrue(
            TilkitSeason::query()->where('program_id', $program->id)->where('label', 'Full year cycle')->exists(),
        );

        $continents = app(GeographyCatalogService::class)->continents();
        $this->assertNotEmpty($continents);
    }

    public function test_site_is_created_for_selected_program_not_only_active_session(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                ['name' => 'Kenya', 'iso2' => 'KE', 'region' => 'Africa'],
            ], 200),
        ]);
        app(GeographyCatalogService::class)->clearCache();

        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);

        $programA = TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => 'Program A',
        ]);
        $programB = TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => 'Program B',
        ]);

        $this->actingAs($user)
            ->post(route('tools.tilkit.program.switch', $tool), ['program_id' => $programA->id])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('tools.tilkit.setup.site', $tool), [
                'program_id' => $programB->id,
                'name' => 'Nairobi site',
                'continent' => 'Africa',
                'country' => 'KE',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tilkit_sites', [
            'program_id' => $programB->id,
            'name' => 'Nairobi site',
        ]);
        $this->assertDatabaseMissing('tilkit_sites', [
            'program_id' => $programA->id,
            'name' => 'Nairobi site',
        ]);
    }

    public function test_program_can_be_updated_via_json_for_wizard_edit(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);
        $program = TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => 'Original name',
            'description' => 'Old note',
        ]);

        $this->actingAs($user)
            ->patchJson(route('tools.tilkit.setup.program.update', [$tool, $program]), [
                'name' => 'Renamed initiative',
                'description' => 'Updated scope',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Renamed initiative');

        $program->refresh();
        $this->assertSame('Renamed initiative', $program->name);
        $this->assertSame('Updated scope', $program->description);
    }

    public function test_duplicate_program_name_returns_plain_language_json_error(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);

        $this->actingAs($user)
            ->postJson(route('tools.tilkit.setup.program', $tool), [
                'name' => 'Ghana pilot',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('tools.tilkit.setup.program', $tool), [
                'name' => 'Ghana pilot',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'A program with this name already exists. Choose a different name or use Edit on the existing program.',
            ]);
    }

    public function test_season_must_continue_calendar_order(): void
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);
        $program = TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => 'Ordered seasons',
        ]);
        $site = TilkitSite::query()->create([
            'project_id' => $tool->id,
            'program_id' => $program->id,
            'name' => 'Test site',
            'continent' => 'Africa',
            'country' => 'KE',
            'country_name' => 'Kenya',
        ]);

        $this->actingAs($user)
            ->postJson(route('tools.tilkit.setup.season', $tool), [
                'program_id' => $program->id,
                'site_id' => $site->id,
                'label' => 'Early year',
                'start_month' => 1,
                'end_month' => 2,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('tools.tilkit.setup.season', $tool), [
                'program_id' => $program->id,
                'site_id' => $site->id,
                'label' => 'Wrong start',
                'start_month' => 5,
                'end_month' => 6,
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'This season must start in March (the month after the previous season ends).',
            ]);
    }
}
