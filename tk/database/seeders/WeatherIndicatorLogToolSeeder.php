<?php

namespace Database\Seeders;

use App\Enums\ToolMemberRole;
use App\Models\DcForm;
use App\Models\DcProject;
use App\Models\ToolMember;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Services\ToolAccessService;
use Database\Seeders\Support\WeatherIndicatorLogFieldCatalog;
use Illuminate\Database\Seeder;

class WeatherIndicatorLogToolSeeder extends Seeder
{
    public const TOOL_CODE = 'ghana_ikit_weather_indicator_log';

    public const OWNER_EMAIL = 'osumbaevans21@gmail.com';

    public function run(): void
    {
        $owner = User::query()
            ->whereRaw('lower(email) = ?', [strtolower(self::OWNER_EMAIL)])
            ->first();

        if ($owner === null) {
            return;
        }

        $access = app(ToolAccessService::class);
        $builder = app(FormBuilderService::class);

        $tool = DcProject::query()->where('code', self::TOOL_CODE)->first();

        if ($tool === null) {
            $tool = $access->createTool($owner, [
                'code' => self::TOOL_CODE,
                'name' => 'TILKIT — Indigenous Weather Indicator Log',
                'module' => 'weather_forecast',
                'purpose' => 'Traditional Indigenous and Local Knowledge (ITK) indicator tool for community weather forecasting: '
                    .'documenting indicators through FGD and plenary workshops and ongoing community monitoring; '
                    .'supporting validation, calibration, tercile integration, hazard and action linkages, and equity-aware observer profiling '
                    .'aligned with the TILKIT Indicator Weighting Protocol and climate-information barrier framework (salience, access, legitimacy, equity, integration).',
                'lawful_basis' => 'consent',
                'retention_months' => 120,
            ]);
        } else {
            $this->assignToolOwner($tool, $owner);
        }

        $this->ensureForm(
            $tool,
            $owner,
            $builder,
            'workshop_session',
            'Workshop & collection session',
            'Register each FGD, plenary, interview, or field monitoring session before logging individual weather indicators.',
            WeatherIndicatorLogFieldCatalog::workshopSessionFields(),
        );

        $this->ensureForm(
            $tool,
            $owner,
            $builder,
            'weather_indicator_log',
            'Weather indicator log entry',
            'Full indicator record matching the extended TILKIT weather indicator log: classification, names, reliability, forecast tercile, '
            .'evidence, validation chain, identification, post-season verification, governance, observer demographics, calibration metrics, and hazard/action references.',
            WeatherIndicatorLogFieldCatalog::indicatorLogFields(),
        );

        $this->ensureForm(
            $tool,
            $owner,
            $builder,
            'seasonal_tercile_tally',
            'Seasonal tercile tally',
            'Live bottom-up forecast summary for a community/site and season/year: simple and weighted tercile counts per the Indicator Weighting Protocol.',
            WeatherIndicatorLogFieldCatalog::tercileSummaryFields(),
        );
    }

    /** @param list<array<string, mixed>> $fields */
    private function ensureForm(
        DcProject $tool,
        User $owner,
        FormBuilderService $builder,
        string $code,
        string $title,
        string $description,
        array $fields,
    ): void {
        $form = DcForm::query()
            ->where('project_id', $tool->id)
            ->where('code', $code)
            ->first();

        if ($form === null) {
            $form = $builder->createForm($tool, $owner, [
                'code' => $code,
                'title' => $title,
                'description' => $description,
                'record_kind' => 'record',
                'allow_anonymous' => false,
            ]);
        }

        $form->load('currentVersion.fields');

        if ($form->currentVersion && $form->currentVersion->fields->isNotEmpty()) {
            return;
        }

        $builder->syncDraftFields($form, $owner, $fields);
        $builder->publish($form, $owner, 'Initial published version from TILKIT weather indicator log template.');
    }

    private function assignToolOwner(DcProject $tool, User $owner): void
    {
        $tool->update([
            'owner_user_id' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        ToolMember::query()->updateOrCreate(
            ['project_id' => $tool->id, 'user_id' => $owner->id],
            ['role' => ToolMemberRole::Admin],
        );
    }
}
