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
                'label' => 'MAM 2024',
            ])
            ->assertRedirect();

        $this->assertTrue(
            TilkitSeason::query()->where('program_id', $program->id)->where('label', 'MAM 2024')->exists(),
        );

        $continents = app(GeographyCatalogService::class)->continents();
        $this->assertNotEmpty($continents);
    }
}
