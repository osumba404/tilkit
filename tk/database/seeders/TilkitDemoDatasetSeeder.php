<?php

namespace Database\Seeders;

use App\Enums\ToolMemberRole;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Models\Tilkit\TilkitSite;
use App\Models\Tilkit\TilkitSighting;
use App\Models\ToolMember;
use App\Models\User;
use App\Services\Tilkit\TilkitEngineService;
use App\Services\Tilkit\TilkitSyntheticDatasetBuilder;
use App\Services\Tilkit\WorkbookDemoImporter;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TilkitDemoDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('tilkit.weather.demo.owner_email', 'osumbaevans21@gmail.com');
        $owner = User::query()->where('email', $email)->first();

        if ($owner === null) {
            $this->command?->warn("Demo owner not found ({$email}). Register or verify that account, then run this seeder again.");

            return;
        }

        $tool = WeatherTool::ensureForUser($owner);
        $tool->update(['owner_user_id' => $owner->id]);

        ToolMember::query()->updateOrCreate(
            ['project_id' => $tool->id, 'user_id' => $owner->id],
            ['role' => ToolMemberRole::Admin]
        );

        $existing = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->count();

        $skipAt = (int) config('tilkit.weather.demo.min_records_before_skip', 120);
        $force = (bool) config('tilkit.weather.demo.force_import', false);
        if (! $force && $existing >= $skipAt) {
            $this->command?->info("TILKIT demo already has {$existing} records (≥ {$skipAt}). Skipping import. Run php artisan tilkit:seed-demo --force to re-import.");

            $this->publishSampleTallies($tool, $owner);

            return;
        }

        /** @var WorkbookDemoImporter $importer */
        $importer = app(WorkbookDemoImporter::class);

        $fixtures = [
            base_path('tests/Fixtures/Tilkit/demo_workbook_full.json'),
            base_path('tests/Fixtures/Tilkit/six_sign_ond2026.json'),
        ];

        $total = 0;
        foreach ($fixtures as $path) {
            if (! is_readable($path)) {
                continue;
            }
            $stats = $importer->importFixture($tool, $path);
            $total += $stats['records'];
            $this->command?->info("Imported {$stats['records']} rows from ".basename($path));
        }

        $synthetic = app(TilkitSyntheticDatasetBuilder::class)->rows();
        $synStats = $importer->importRows($tool, $synthetic);
        $total += $synStats['records'];
        $this->command?->info("Imported {$synStats['records']} synthetic pilot rows.");

        $this->stampCollectionMetadata($tool, $owner);
        $this->ensureSeasonSettings($tool);
        $this->seedSightings($tool, $owner);
        $this->publishSampleTallies($tool, $owner);

        $this->command?->info("Demo dataset ready for {$email}: {$total}+ workbook rows on tool {$tool->code}.");
    }

    private function stampCollectionMetadata(DcProject $tool, User $owner): void
    {
        TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->whereNull('entered_by')
            ->update(['entered_by' => $owner->id]);
    }

    private function ensureSeasonSettings(DcProject $tool): void
    {
        $pairs = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->select(['site_id', 'season_id'])
            ->distinct()
            ->limit(24)
            ->get();

        foreach ($pairs as $pair) {
            TilkitSeasonSettings::query()->firstOrCreate(
                ['site_id' => $pair->site_id, 'season_id' => $pair->season_id],
                [
                    'kmsa_outlook_tercile' => 2,
                    'ndma_drought_phase' => 'Normal',
                    'ndma_vci3m' => 42.5,
                ]
            );
        }
    }

    private function seedSightings(DcProject $tool, User $owner): void
    {
        $records = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with('site')
            ->inRandomOrder()
            ->limit(35)
            ->get();

        foreach ($records as $record) {
            if ($record->sightings()->exists()) {
                continue;
            }

            TilkitSighting::query()->create([
                'indicator_record_id' => $record->id,
                'sighted_at' => now()->subDays(random_int(1, 90)),
                'place_text' => ($record->site?->name ?? 'Field').' observation point',
                'landmark' => 'Community landmark',
                'latitude' => -1.0 + (random_int(0, 1000) / 10000),
                'longitude' => 37.0 + (random_int(0, 1000) / 10000),
                'observed_by' => 'Community observer',
                'created_by' => $owner->id,
            ]);
        }
    }

    private function publishSampleTallies(DcProject $tool, User $owner): void
    {
        $engine = app(TilkitEngineService::class);

        $targets = [
            ['Six-sign demo', 'OND 2026'],
            ['Mutirithia (DEMO)', 'OND 2026'],
            ['Kitui Central', 'OND 2026'],
        ];

        foreach ($targets as [$siteName, $seasonLabel]) {
            $site = TilkitSite::query()->where('project_id', $tool->id)->where('name', $siteName)->first();
            $season = TilkitSeason::query()->where('project_id', $tool->id)->where('label', $seasonLabel)->first();
            if ($site === null || $season === null) {
                continue;
            }

            DB::transaction(fn () => $engine->runTally($site->id, $season->id, true, $owner->id));
            $this->command?->info("Published tally snapshot: {$siteName} · {$seasonLabel}");
        }
    }
}
