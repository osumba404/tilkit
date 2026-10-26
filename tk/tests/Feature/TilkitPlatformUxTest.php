<?php

namespace Tests\Feature;

use App\Support\Tilkit\WeatherTool;
use Database\Seeders\TilkitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TilkitPlatformUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_analytics_routes_redirect_to_tilkit_insights(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $this->seed(TilkitSeeder::class);
        $admin = \App\Models\User::query()->where('email', 'superadmin@tilkit.local')->firstOrFail();
        $tool = WeatherTool::resolve();

        $this->actingAs($admin)
            ->get(route('tools.analytics', $tool))
            ->assertRedirect(route('tools.tilkit.insights', $tool));

        $this->actingAs($admin)
            ->get(route('tools.submissions.index', $tool))
            ->assertRedirect(route('tools.tilkit.records.index', $tool));
    }

    public function test_form_builder_is_not_available_on_weather_tool(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $this->seed(TilkitSeeder::class);
        $admin = \App\Models\User::query()->where('email', 'superadmin@tilkit.local')->firstOrFail();
        $tool = WeatherTool::resolve();

        $this->actingAs($admin)
            ->get(route('tools.forms.create', $tool))
            ->assertNotFound();
    }

    public function test_records_and_insights_pages_load(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $this->seed(TilkitSeeder::class);
        $admin = \App\Models\User::query()->where('email', 'superadmin@tilkit.local')->firstOrFail();
        $tool = WeatherTool::resolve();

        $this->actingAs($admin)
            ->get(route('tools.tilkit.records.index', $tool))
            ->assertOk()
            ->assertSee('Indicator records');

        $this->actingAs($admin)
            ->get(route('tools.tilkit.insights', $tool))
            ->assertOk()
            ->assertSee('Analysis');

        $this->actingAs($admin)
            ->get(route('tools.tilkit.deep-analysis', $tool))
            ->assertOk()
            ->assertSee('Deep Analysis');

        $this->actingAs($admin)
            ->get(route('tools.tilkit.deep-analysis.schema', $tool))
            ->assertOk()
            ->assertJsonStructure(['columns', 'filters' => ['sites', 'seasons', 'programs', 'scopes']]);
    }
}
