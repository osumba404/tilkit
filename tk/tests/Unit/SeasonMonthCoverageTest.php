<?php

namespace Tests\Unit;

use App\Support\Tilkit\SeasonMonthCoverage;
use PHPUnit\Framework\TestCase;

class SeasonMonthCoverageTest extends TestCase
{
    public function test_first_season_may_start_after_january(): void
    {
        $this->assertNull(SeasonMonthCoverage::validateNextRange(
            SeasonMonthCoverage::coverageMap([]),
            4,
            6,
            [],
        ));
    }

    public function test_next_season_starts_after_previous_end(): void
    {
        $existing = [['start_month' => 4, 'end_month' => 6]];
        $map = SeasonMonthCoverage::coverageMap($existing);

        $this->assertSame(
            'This season must start in July (the month after the previous season ends).',
            SeasonMonthCoverage::validateNextRange($map, 8, 9, $existing),
        );
        $this->assertNull(SeasonMonthCoverage::validateNextRange($map, 7, 9, $existing));
    }

    public function test_wrapped_season_covers_year_boundary(): void
    {
        $months = SeasonMonthCoverage::monthsInSeason(11, 2);
        $this->assertSame([11, 12, 1, 2], $months);

        $map = SeasonMonthCoverage::coverageMap([
            ['start_month' => 11, 'end_month' => 2],
            ['start_month' => 3, 'end_month' => 10],
        ]);

        $this->assertTrue(SeasonMonthCoverage::isComplete($map));
    }

    public function test_complete_coverage(): void
    {
        $map = SeasonMonthCoverage::coverageMap([
            ['start_month' => 1, 'end_month' => 6],
            ['start_month' => 7, 'end_month' => 12],
        ]);

        $this->assertTrue(SeasonMonthCoverage::isComplete($map));
        $this->assertNull(SeasonMonthCoverage::nextStartMonth($map));
    }
}
