<?php

namespace App\Support\Tilkit;

class SeasonMonthRange
{
    /** @return array<int, string> 1-indexed month => short label */
    public static function monthOptions(): array
    {
        return [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    }

    /** @return array<int, string> */
    public static function shortMonthOptions(): array
    {
        return [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];
    }

    public static function labelFromMonths(int $startMonth, int $endMonth): string
    {
        $short = self::shortMonthOptions();
        $start = $short[$startMonth] ?? (string) $startMonth;
        $end = $short[$endMonth] ?? (string) $endMonth;

        if ($startMonth === $endMonth) {
            return $start;
        }

        return "{$start}–{$end}";
    }
}
