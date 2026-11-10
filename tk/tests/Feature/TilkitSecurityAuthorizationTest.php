<?php

namespace Tests\Feature;

use App\Enums\ToolMemberRole;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSite;
use App\Models\ToolMember;
use App\Models\User;
use App\Support\Tilkit\WeatherTool;
use Database\Seeders\TilkitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitSecurityAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    public function test_collector_cannot_export_workbook_files(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $this->seed(TilkitSeeder::class);

        $admin = User::query()->where('email', 'superadmin@tilkit.local')->firstOrFail();
        $tool = WeatherTool::resolve();
        $collector = User::factory()->create();

        ToolMember::query()->create([
            'project_id' => $tool->id,
            'user_id' => $collector->id,
            'role' => ToolMemberRole::Member,
        ]);

        $this->actingAs($collector)
            ->get(route('tools.tilkit.export.csv', $tool))
            ->assertForbidden();

        $this->actingAs($collector)
            ->get(route('tools.tilkit.export.json', $tool))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('tools.tilkit.export.csv', $tool))
            ->assertOk();
    }

    public function test_collector_cannot_mutate_setup_catalogue_or_programs(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $this->seed(TilkitSeeder::class);

        $tool = WeatherTool::resolve();
        $collector = User::factory()->create();
        ToolMember::query()->create([
            'project_id' => $tool->id,
            'user_id' => $collector->id,
            'role' => ToolMemberRole::Member,
        ]);

        $program = TilkitProgram::query()->where('project_id', $tool->id)->first();
        $site = TilkitSite::query()->where('project_id', $tool->id)->first();

        $this->actingAs($collector)
            ->post(route('tools.tilkit.setup.program', $tool), ['name' => 'Rogue program'])
            ->assertForbidden();

        if ($program && $site) {
            $this->actingAs($collector)
                ->put(route('tools.tilkit.setup.site.month-indicators.update', [$tool, $site]), [
                    'assignments' => ['1' => []],
                ], ['Accept' => 'application/json'])
                ->assertForbidden();
        }
    }

    public function test_viewer_cannot_capture_or_update_records(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $this->seed(TilkitSeeder::class);

        $tool = WeatherTool::resolve();
        $viewer = User::factory()->create();
        ToolMember::query()->create([
            'project_id' => $tool->id,
            'user_id' => $viewer->id,
            'role' => ToolMemberRole::Viewer,
        ]);

        $this->actingAs($viewer)
            ->get(route('tools.tilkit.records.create', $tool))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('tools.tilkit.records.store', $tool), [])
            ->assertForbidden();
    }

    public function test_collector_cannot_access_deep_analysis_dataset(): void
    {
        config(['tilkit.weather.single_tool.enabled' => true]);
        $this->seed(TilkitSeeder::class);

        $tool = WeatherTool::resolve();
        $collector = User::factory()->create();
        ToolMember::query()->create([
            'project_id' => $tool->id,
            'user_id' => $collector->id,
            'role' => ToolMemberRole::Member,
        ]);

        $this->actingAs($collector)
            ->get(route('tools.tilkit.deep-analysis.dataset', $tool))
            ->assertForbidden();
    }
}
