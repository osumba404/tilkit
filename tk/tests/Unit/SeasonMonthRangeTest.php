<?php

namespace Tests\Unit;

use App\Support\Tilkit\SeasonMonthRange;
use PHPUnit\Framework\TestCase;

class SeasonMonthRangeTest extends TestCase
{
    public function test_builds_range_label(): void
    {
        $this->assertSame('Jan–Feb', SeasonMonthRange::labelFromMonths(1, 2));
        $this->assertSame('Mar–Sep', SeasonMonthRange::labelFromMonths(3, 9));
        $this->assertSame('Jun', SeasonMonthRange::labelFromMonths(6, 6));
    }
}
