<?php

namespace Tests\Unit;

use App\Support\Tilkit\PilotSeasonCalendar;
use Tests\TestCase;

class PilotSeasonCalendarTest extends TestCase
{
    public function test_workbook_season_labels_resolve_calendar_months(): void
    {
        $mam = PilotSeasonCalendar::datesForWorkbookSeasonLabel('MAM 2026');
        $this->assertSame(3, $mam['start_month']);
        $this->assertSame(5, $mam['end_month']);

        $major = PilotSeasonCalendar::datesForWorkbookSeasonLabel('AMJ 2026 (major rains)');
        $this->assertSame(4, $major['start_month']);
        $this->assertSame(7, $major['end_month']);

        $minor = PilotSeasonCalendar::datesForWorkbookSeasonLabel('SON 2026 (minor rains)');
        $this->assertSame(9, $minor['start_month']);
        $this->assertSame(11, $minor['end_month']);
    }
}
