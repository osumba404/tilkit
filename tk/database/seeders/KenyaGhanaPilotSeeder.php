<?php

namespace Database\Seeders;

use App\Enums\ToolMemberRole;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Models\ToolMember;
use App\Models\User;
use App\Services\Tilkit\KenyaGhanaPilotDataset;
use App\Services\Tilkit\TilkitEngineService;
use App\Services\Tilkit\TilkitPilotEvidenceSeeder;
use App\Services\Tilkit\TilkitWeatherDataReset;
use App\Services\Tilkit\WorkbookDemoImporter;
use App\Services\Tilkit\WorkbookRecordService;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KenyaGhanaPilotSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('tilkit.weather.demo.owner_email', 'osumbaevans21@gmail.com');
        $owner = User::query()->where('email', $email)->first();

        if ($owner === null) {
            $this->command?->warn("Owner account not found ({$email}). Create the user first, then re-run.");

            return;
        }

        app(TilkitWeatherDataReset::class)->wipeWeatherToolData();

        $tool = WeatherTool::ensureForUser($owner);
        $tool->update(['owner_user_id' => $owner->id]);

        ToolMember::query()->updateOrCreate(
            ['project_id' => $tool->id, 'user_id' => $owner->id],
            ['role' => ToolMemberRole::Admin]
        );

        $kenyaProgram = TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => 'Kenya NEO',
            'description' => 'Kenya Nature Early Outlook pilot — long rains (MAM) and short rains (OND) community indicator monitoring.',
        ]);

        $ghanaProgram = TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => 'Ghana community forecast',
            'description' => 'Ghana pilot — southern bi-modal rains (major AMJ + minor SON) and northern single rainy season.',
        ]);

        $dataset = app(KenyaGhanaPilotDataset::class);
        /** @var WorkbookDemoImporter $importer */
        $importer = app(WorkbookDemoImporter::class);

        $kenyaStats = $importer->importRows($tool, $dataset->kenyaRows(), $kenyaProgram, $owner);
        $ghanaStats = $importer->importRows($tool, $dataset->ghanaRows(), $ghanaProgram, $owner);

        $this->seedSeasonSettings($kenyaProgram);
        $this->seedSeasonSettings($ghanaProgram);

        app(TilkitPilotEvidenceSeeder::class)->seedForTool($tool, $owner);

        $this->recalculateAllPilotRecords($tool);
        $this->publishPilotTallies($tool, $owner);

        $this->command?->info("Kenya NEO: {$kenyaStats['records']} records, {$kenyaStats['sites']} sites, {$kenyaStats['seasons']} seasons (MAM + OND long/short rains).");
        $this->command?->info("Ghana: {$ghanaStats['records']} records, {$ghanaStats['sites']} sites, {$ghanaStats['seasons']} seasons (north single + south major/minor).");
        $this->command?->info("Weather log owned by {$email} (tool {$tool->code}).");
    }

    private function seedSeasonSettings(TilkitProgram $program): void
    {
        foreach ($program->sites()->with('records')->get() as $site) {
            $seasonIds = $site->records()->distinct()->pluck('season_id');
            foreach ($seasonIds as $seasonId) {
                $season = TilkitSeason::query()->find($seasonId);
                $integration = $this->seasonIntegrationDefaults($season?->label ?? '');

                TilkitSeasonSettings::query()->updateOrCreate(
                    ['site_id' => $site->id, 'season_id' => $seasonId],
                    $integration,
                );
            }
        }
    }

    /** @return array{kmsa_outlook_tercile: int, ndma_drought_phase: string, ndma_vci3m: float} */
    private function seasonIntegrationDefaults(string $seasonLabel): array
    {
        $upper = strtoupper($seasonLabel);

        if (str_contains($upper, 'MAM')) {
            return [
                'kmsa_outlook_tercile' => 3,
                'ndma_drought_phase' => 'Normal',
                'ndma_vci3m' => 52.0,
            ];
        }
        if (str_contains($upper, 'OND')) {
            return [
                'kmsa_outlook_tercile' => 2,
                'ndma_drought_phase' => 'Normal',
                'ndma_vci3m' => 48.0,
            ];
        }
        if (str_contains($upper, 'AMJ') || str_contains($upper, 'MAJOR')) {
            return [
                'kmsa_outlook_tercile' => 3,
                'ndma_drought_phase' => 'Normal',
                'ndma_vci3m' => 55.0,
            ];
        }
        if (str_contains($upper, 'SON') || str_contains($upper, 'MINOR')) {
            return [
                'kmsa_outlook_tercile' => 2,
                'ndma_drought_phase' => 'Normal',
                'ndma_vci3m' => 50.0,
            ];
        }

        return [
            'kmsa_outlook_tercile' => 2,
            'ndma_drought_phase' => 'Normal',
            'ndma_vci3m' => 46.0,
        ];
    }

    private function recalculateAllPilotRecords(DcProject $tool): void
    {
        $engine = app(TilkitEngineService::class);
        $workbook = app(WorkbookRecordService::class);

        TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['indicator', 'site', 'season'])
            ->orderBy('id')
            ->each(function (TilkitIndicatorRecord $record) use ($workbook, $engine): void {
                $workbook->storeReferenceColumns($record->fresh());
                $engine->recalculateRecord($record->fresh());
            });
    }

    private function publishPilotTallies(DcProject $tool, User $owner): void
    {
        $engine = app(TilkitEngineService::class);

        $pairs = TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->select(['site_id', 'season_id'])
            ->distinct()
            ->get();

        $published = 0;
        foreach ($pairs as $pair) {
            $result = $engine->runTally((int) $pair->site_id, (int) $pair->season_id, true, $owner->id);
            if ($result->countedRows > 0) {
                $published++;
            }
        }

        $this->command?->info("Published {$published} tally snapshots with counted rows ({$pairs->count()} site·season pairs).");
    }
}
