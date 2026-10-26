<?php

namespace Tests\Unit;

use App\Services\Tilkit\KenyaGhanaPilotDataset;
use App\Support\Tilkit\CommunityDataListRegistry;
use App\Support\Tilkit\WorkbookColumnRegistry;
use App\Support\Tilkit\WorkbookInputSpec;
use Tests\TestCase;

class KenyaGhanaPilotDatasetTest extends TestCase
{
    public function test_pilot_rows_fill_community_data_list_capture_columns(): void
    {
        $ds = new KenyaGhanaPilotDataset;
        $rows = array_merge($ds->kenyaRows(), $ds->ghanaRows());

        $this->assertCount(32, $rows);

        $letters = [];
        foreach (WorkbookColumnRegistry::rawColumns() as $col) {
            if (WorkbookInputSpec::skipInForm($col)) {
                continue;
            }
            if (in_array($col['column'], CommunityDataListRegistry::allColumnLetters(), true)) {
                $letters[] = $col['column'];
            }
        }
        $letters = array_values(array_unique($letters));

        foreach ($rows as $i => $row) {
            foreach ($letters as $letter) {
                $value = $row[$letter] ?? null;
                $this->assertNotSame('', $value, "Row {$i} missing column {$letter}");
                $this->assertNotNull($value, "Row {$i} missing column {$letter}");
            }
        }
    }

    public function test_pilot_rows_include_kenya_and_ghana_rainy_season_labels(): void
    {
        $ds = new KenyaGhanaPilotDataset;
        $kenya = array_column($ds->kenyaRows(), 'AM');
        $ghana = array_column($ds->ghanaRows(), 'AM');

        $this->assertContains('MAM 2026', $kenya);
        $this->assertContains('OND 2026', $kenya);
        $this->assertContains('Rainy season 2026 (north)', $ghana);
        $this->assertContains('AMJ 2026 (major rains)', $ghana);
        $this->assertContains('SON 2026 (minor rains)', $ghana);
    }

    public function test_kenya_and_ghana_row_counts_match_season_model(): void
    {
        $ds = new KenyaGhanaPilotDataset;
        $kenya = $ds->kenyaRows();
        $ghana = $ds->ghanaRows();

        $this->assertCount(20, $kenya);
        $this->assertSame(10, count(array_filter($kenya, fn (array $r) => ($r['AM'] ?? '') === 'MAM 2026')));
        $this->assertSame(10, count(array_filter($kenya, fn (array $r) => ($r['AM'] ?? '') === 'OND 2026')));

        $this->assertCount(12, $ghana);
        $this->assertSame(8, count(array_filter($ghana, fn (array $r) => ($r['AM'] ?? '') === 'Rainy season 2026 (north)')));
        $this->assertSame(2, count(array_filter($ghana, fn (array $r) => ($r['AM'] ?? '') === 'AMJ 2026 (major rains)')));
        $this->assertSame(2, count(array_filter($ghana, fn (array $r) => ($r['AM'] ?? '') === 'SON 2026 (minor rains)')));
    }

    public function test_forecast_windows_differ_by_season_kind(): void
    {
        $ds = new KenyaGhanaPilotDataset;
        $kenya = $ds->kenyaRows();
        $mam = collect($kenya)->firstWhere('AM', 'MAM 2026');
        $ond = collect($kenya)->firstWhere('AM', 'OND 2026');

        $this->assertNotNull($mam);
        $this->assertNotNull($ond);
        $this->assertStringStartsWith('2026-03', (string) $mam['P']);
        $this->assertStringStartsWith('2026-10', (string) $ond['P']);
    }

    public function test_pilot_rows_fill_every_raw_workbook_column_except_photos(): void
    {
        $ds = new KenyaGhanaPilotDataset;
        $rows = array_merge($ds->kenyaRows(), $ds->ghanaRows());
        $letters = array_column(WorkbookColumnRegistry::rawColumns(), 'column');

        foreach ($rows as $i => $row) {
            foreach ($letters as $letter) {
                if ($letter === 'Y') {
                    continue;
                }
                $value = $row[$letter] ?? null;
                $this->assertNotNull($value, "Row {$i} missing raw column {$letter}");
                $this->assertNotSame('', $value, "Row {$i} empty raw column {$letter}");
            }
        }
    }
}
