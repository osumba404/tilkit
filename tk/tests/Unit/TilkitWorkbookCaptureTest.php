<?php

namespace Tests\Unit;

use App\Models\Tilkit\TilkitIndicator;
use App\Models\Tilkit\TilkitIndicatorCode;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeason;
use App\Models\Tilkit\TilkitSite;
use App\Models\User;
use App\Services\Tilkit\WorkbookRecordService;
use App\Support\Tilkit\WorkbookColumnRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TilkitTestTool;
use Tests\TestCase;

class TilkitWorkbookCaptureTest extends TestCase
{
    use RefreshDatabase;
    use TilkitTestTool;

    public function test_column_register_matches_v4_workbook_shape(): void
    {
        $all = WorkbookColumnRegistry::all();
        $raw = WorkbookColumnRegistry::rawColumns();
        $derived = WorkbookColumnRegistry::derivedColumns();

        $this->assertCount(124, $all);
        $this->assertCount(90, $raw);
        $this->assertCount(34, $derived);
    }

    public function test_store_all_raw_columns_and_reject_derived(): void
    {
        $record = $this->makeRecord();
        $service = app(WorkbookRecordService::class);

        $payload = [];
        foreach (WorkbookColumnRegistry::rawColumns() as $col) {
            $kind = WorkbookColumnRegistry::inputKind($col);
            if (in_array($kind, ['photos', 'readonly'], true)) {
                continue;
            }
            $payload[$col['column']] = match ($kind) {
                'yes_no' => '1',
                'rating', 'tercile', 'history_scale', 'lead_time_class', 'rating_321' => '2',
                'date' => '2026-10-01',
                'json' => '{"crops":"+"}',
                'list_text' => ['Crops', 'Water'],
                'gps' => '-1.2,36.8',
                'integer' => '3',
                'number' => '42.5',
                'consent' => '2; 1; Community credit',
                'validation' => 'fgd_validated',
                'outcome' => 'hit',
                default => 'sample',
            };
        }

        $service->storeRawValues($record, $payload);

        $expectedRaw = count(array_filter(
            WorkbookColumnRegistry::rawColumns(),
            fn ($c) => ! in_array(WorkbookColumnRegistry::inputKind($c), ['photos', 'readonly'], true)
        ));
        $this->assertSame(
            $expectedRaw,
            $record->workbookValues()->where('is_derived', false)->count()
        );

        $derivedLetter = WorkbookColumnRegistry::derivedColumns()[0]['column'];
        $this->expectException(\InvalidArgumentException::class);
        $service->storeRawValues($record, [$derivedLetter => 'blocked']);
    }

    public function test_workbook_syncs_canonical_sighted_and_forecast_fields(): void
    {
        $record = $this->makeRecord();
        $service = app(WorkbookRecordService::class);

        $service->storeRawValues($record, [
            'N' => 1,
            'AC' => 1,
            'AD' => 0,
            'U' => 2,
            'P' => '2026-11-01',
        ]);

        $record->refresh();
        $this->assertTrue($record->sighted);
        $this->assertTrue($record->confirmed);
        $this->assertFalse($record->approved);
        $this->assertSame(2, $record->tercile_forecast->value);
        $this->assertSame('2026-11-01', $record->onset_earliest->format('Y-m-d'));
    }

    private function makeRecord(): TilkitIndicatorRecord
    {
        $user = User::factory()->create();
        $tool = $this->tilkitToolFor($user);

        $program = \App\Models\Tilkit\TilkitProgram::query()->create(['project_id' => $tool->id, 'name' => 'Test program']);
        $site = TilkitSite::query()->create([
            'project_id' => $tool->id,
            'program_id' => $program->id,
            'name' => 'Site A',
            'continent' => 'Africa',
            'country' => 'KE',
        ]);
        $season = TilkitSeason::query()->create([
            'project_id' => $tool->id,
            'program_id' => $program->id,
            'label' => 'OND 2026',
            'protocol_version' => config('tilkit.weather.protocol_version'),
        ]);
        $code = TilkitIndicatorCode::query()->create([
            'code' => '01-ANI-FRO',
            'category' => '01',
            'type' => 'ANI',
            'sub_type' => 'FRO',
            'protocol_version' => config('tilkit.weather.protocol_version'),
        ]);
        $indicator = TilkitIndicator::query()->create([
            'indicator_uid' => '01-ANI-FRO-001',
            'indicator_code_id' => $code->id,
            'project_id' => $tool->id,
            'local_name' => 'Frog',
        ]);

        return TilkitIndicatorRecord::query()->create([
            'indicator_id' => $indicator->id,
            'site_id' => $site->id,
            'season_id' => $season->id,
        ]);
    }
}
