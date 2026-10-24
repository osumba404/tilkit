<?php

namespace Tests\Feature;

use App\Models\DcProject;
use App\Models\User;
use App\Services\ToolAccessService;
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

    public function test_create_tool_via_service_is_forbidden(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tools.store'), [
                'name' => 'Extra tool',
                'purpose' => 'Should not be allowed',
                'lawful_basis' => 'consent',
            ])
            ->assertNotFound();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ToolAccessService::class)->createTool($user, [
            'name' => 'Extra',
            'purpose' => 'Blocked',
        ]);
    }

    public function test_user_with_legacy_tools_lands_on_embedded_weather_log(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $user = User::factory()->create();

        $legacy = DcProject::query()->create([
            'code' => 'ghana_ikit_weather_indicator_log',
            'name' => 'TILKIT — Indigenous Weather Indicator Log',
            'module' => 'tilkit_weather',
            'purpose' => 'Old manual tool',
            'lawful_basis' => 'consent',
            'status' => 'active',
            'owner_user_id' => $user->id,
        ]);
        \App\Models\ToolMember::query()->create([
            'project_id' => $legacy->id,
            'user_id' => $user->id,
            'role' => \App\Enums\ToolMemberRole::Admin,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('tools.tilkit.overview', WeatherTool::resolve()));

        $this->assertNotNull(WeatherTool::resolve());
        $this->assertSame('tilkit-weather', WeatherTool::resolve()->code);
        $this->assertTrue($user->fresh()->canAccessTool(WeatherTool::resolve()));
    }

    public function test_routes_for_non_canonical_tool_return_not_found(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $user = User::factory()->create();
        WeatherTool::ensureForUser($user);

        $legacy = DcProject::query()->create([
            'code' => 'legacy-other-tool',
            'name' => 'Legacy',
            'module' => 'data_collection',
            'purpose' => 'Old row',
            'lawful_basis' => 'consent',
            'status' => 'active',
            'owner_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('tools.tilkit.overview', $legacy))
            ->assertNotFound();
    }
}
