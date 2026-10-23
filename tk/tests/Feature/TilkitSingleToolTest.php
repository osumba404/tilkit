<?php

namespace Tests\Feature;

use App\Models\DcProject;
use App\Models\User;
use App\Support\Tilkit\WeatherTool;
use Database\Seeders\TilkitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TilkitSingleToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_one_weather_tool_without_demo_rows(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);

        $this->seed(TilkitSeeder::class);

        $this->assertSame(1, DcProject::query()->count());
        $tool = WeatherTool::resolve();
        $this->assertNotNull($tool);
        $this->assertSame('tilkit-weather', $tool->code);
        $this->assertSame('tilkit_weather', $tool->module);
        $this->assertSame(0, $tool->tilkitIndicators()->count());
    }

    public function test_cannot_create_additional_tools_when_single_tool_mode(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('tools.create'))
            ->assertNotFound();
    }

    public function test_dashboard_redirects_to_weather_log(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $this->seed(TilkitSeeder::class);
        $admin = User::query()->where('email', 'superadmin@tilkit.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('tools.tilkit.overview', WeatherTool::resolve()));
    }
}
