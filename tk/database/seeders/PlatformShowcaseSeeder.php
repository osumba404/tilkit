<?php

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Enums\Tilkit\TilkitPersona;
use App\Enums\ToolMemberRole;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitProgram;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Models\ToolInvitation;
use App\Models\ToolMember;
use App\Models\User;
use App\Services\Tilkit\PlatformWorkbookDataset;
use App\Services\Tilkit\ShowcaseFieldEvidence;
use App\Services\Tilkit\TilkitEngineService;
use App\Services\Tilkit\TilkitWeatherDataReset;
use App\Services\Tilkit\WorkbookRecordService;
use App\Support\Tilkit\WeatherTool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PlatformShowcaseSeeder extends Seeder
{
    public const OWNER_EMAIL = 'osumbaevans21@gmail.com';

    public function run(): void
    {
        $this->call(FieldTypeSeeder::class);

        $password = self::OWNER_EMAIL;

        $owner = User::query()->updateOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Evans Osumba',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'status' => 'active',
                'platform_role' => PlatformRole::SuperAdmin,
                'timezone' => 'Africa/Nairobi',
            ],
        );

        app(TilkitWeatherDataReset::class)->wipeWeatherToolData();

        $tool = WeatherTool::ensureForUser($owner);

        $governance = [
            'owner_user_id' => $owner->id,
            'purpose' => 'Community indigenous weather indicators for anticipatory action — Kenya long/short rains and Ghana seasonal forecast integration.',
            'lawful_basis' => 'consent',
            'retention_months' => 84,
            'status' => 'active',
            'data_controller_name' => 'Ocean Crest Consultancy',
            'data_controller_email' => self::OWNER_EMAIL,
            'data_protection_contact_email' => 'dpo@occ.co.ke',
            'odpc_registration_number' => 'DPR-2026-0142',
            'retention_policy_summary' => 'Workbook capture, workshop evidence, and verification media retained for seven years after the monitoring season closes, then archived or anonymised per Kenya DPA 2019.',
            'cross_border_transfers_allowed' => false,
        ];

        $governance = array_filter(
            $governance,
            fn (mixed $value, string $column): bool => Schema::hasColumn('dc_projects', $column),
            ARRAY_FILTER_USE_BOTH,
        );

        $tool->update($governance);

        ToolMember::query()->updateOrCreate(
            ['project_id' => $tool->id, 'user_id' => $owner->id],
            [
                'role' => ToolMemberRole::Admin,
                'tilkit_persona' => TilkitPersona::Administrator->value,
                'can_export' => true,
                'can_publish_forms' => true,
                'joined_at' => now()->subMonths(6),
            ],
        );

        $kenyaProgram = TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => 'Kenya Nature Early Outlook',
            'description' => 'Laikipia and eastern drylands — long rains (MAM) and short rains (OND) indicator monitoring with ward plenary endorsement.',
        ]);

        $ghanaProgram = TilkitProgram::query()->create([
            'project_id' => $tool->id,
            'name' => 'Ghana seasonal community forecast',
            'description' => 'Northern savanna single rainy season and southern bimodal rains (major AMJ and minor SON) with FGD-validated signs.',
        ]);

        $dataset = app(PlatformWorkbookDataset::class);
        $importer = app(\App\Services\Tilkit\WorkbookDemoImporter::class);

        $kenyaStats = $importer->importRows($tool, $dataset->kenyaRows(), $kenyaProgram, $owner);
        $ghanaStats = $importer->importRows($tool, $dataset->ghanaRows(), $ghanaProgram, $owner);

        $this->seedSeasonSettings($kenyaProgram);
        $this->seedSeasonSettings($ghanaProgram);

        app(ShowcaseFieldEvidence::class)->attachForTool($tool, $owner);

        $this->recalculateAllRecords($tool);
        $this->publishTallies($tool, $owner);

        $this->seedTeam($tool, $owner);

        $this->command?->info("Account: ".self::OWNER_EMAIL.' (password matches email address).');
        $this->command?->info("Kenya programme: {$kenyaStats['records']} records, {$kenyaStats['sites']} sites, {$kenyaStats['seasons']} seasons.");
        $this->command?->info("Ghana programme: {$ghanaStats['records']} records, {$ghanaStats['sites']} sites, {$ghanaStats['seasons']} seasons.");
        $this->command?->info("Weather log: {$tool->name} ({$tool->code}).");
    }

    private function seedTeam(DcProject $tool, User $owner): void
    {
        $members = [
            [
                'email' => 'grace.wanjiku@iwaprojects.or.ke',
                'name' => 'Grace Wanjiku',
                'role' => ToolMemberRole::Collector,
                'persona' => TilkitPersona::Observer,
            ],
            [
                'email' => 'samuel.otieno@iwaprojects.or.ke',
                'name' => 'Samuel Otieno',
                'role' => ToolMemberRole::Member,
                'persona' => TilkitPersona::DataDesk,
                'can_export' => true,
            ],
            [
                'email' => 'akhundajida.abdi@iwaprojects.or.ke',
                'name' => 'Akhundajida Abdi',
                'role' => ToolMemberRole::Member,
                'persona' => TilkitPersona::LeadItkHolder,
            ],
        ];

        foreach ($members as $spec) {
            $user = User::query()->updateOrCreate(
                ['email' => $spec['email']],
                [
                    'name' => $spec['name'],
                    'password' => Hash::make($spec['email']),
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'platform_role' => PlatformRole::Member,
                ],
            );

            ToolMember::query()->updateOrCreate(
                ['project_id' => $tool->id, 'user_id' => $user->id],
                [
                    'role' => $spec['role'],
                    'tilkit_persona' => $spec['persona']->value,
                    'can_export' => (bool) ($spec['can_export'] ?? false),
                    'can_publish_forms' => false,
                    'joined_at' => now()->subMonths(3),
                ],
            );
        }

        ToolInvitation::query()->updateOrCreate(
            ['project_id' => $tool->id, 'email' => 'fatuma.hassan@wardfocal.or.ke'],
            [
                'role' => ToolMemberRole::Collector->value,
                'tilkit_persona' => TilkitPersona::CommunityRepresentative->value,
                'token' => (string) Str::uuid(),
                'invited_by' => $owner->id,
                'expires_at' => now()->addDays(14),
            ],
        );
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

    private function recalculateAllRecords(DcProject $tool): void
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

    private function publishTallies(DcProject $tool, User $owner): void
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

        $this->command?->info("Published {$published} tally snapshots ({$pairs->count()} site·season pairs).");
    }
}
