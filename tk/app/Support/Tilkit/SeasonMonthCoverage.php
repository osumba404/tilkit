<?php

namespace App\Support\Tilkit;

class SeasonMonthCoverage
{
    /** @return list<int> months 1–12 covered by this season (forward from start; wraps Dec→Jan) */
    public static function monthsInSeason(int $start, int $end): array
    {
        if ($start < 1 || $start > 12 || $end < 1 || $end > 12) {
            return [];
        }

        $months = [];
        $month = $start;
        for ($i = 0; $i < 12; $i++) {
            $months[] = $month;
            if ($month === $end) {
                return $months;
            }
            $month = self::monthAfter($month);
        }

        return [];
    }

    public static function monthAfter(int $month): int
    {
        return $month >= 12 ? 1 : $month + 1;
    }

    /** @return array<int, bool> 1-indexed months => covered */
    public static function coverageMap(iterable $seasons): array
    {
        $map = array_fill(1, 12, false);

        foreach ($seasons as $season) {
            $start = (int) (is_array($season) ? $season['start_month'] : $season->start_month);
            $end = (int) (is_array($season) ? $season['end_month'] : $season->end_month);
            foreach (self::monthsInSeason($start, $end) as $m) {
                $map[$m] = true;
            }
        }

        return $map;
    }

    /** @param array<int, bool> $map */
    public static function isComplete(array $map): bool
    {
        for ($m = 1; $m <= 12; $m++) {
            if (! ($map[$m] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<int, bool> $map */
    public static function nextStartMonth(array $map): ?int
    {
        for ($m = 1; $m <= 12; $m++) {
            if (! ($map[$m] ?? false)) {
                return $m;
            }
        }

        return null;
    }

    /**
     * Start month for the next season after existing ones (null = first season — user picks start).
     */
    public static function nextSeasonStartMonth(iterable $seasons): ?int
    {
        $last = null;
        foreach ($seasons as $season) {
            $last = $season;
        }
        if ($last === null) {
            return null;
        }

        $end = (int) (is_array($last) ? $last['end_month'] : $last->end_month);

        return self::monthAfter($end);
    }

    /**
     * @param  array<int, bool>  $map
     */
    public static function validateNextRange(array $map, int $startMonth, int $endMonth, iterable $existingSeasons): ?string
    {
        if (self::isComplete($map)) {
            return 'All twelve months are already assigned to seasons.';
        }

        $expectedStart = self::nextSeasonStartMonth($existingSeasons);

        if ($expectedStart === null) {
            if ($startMonth < 1 || $startMonth > 12) {
                return 'Choose a valid start month for your first season.';
            }
        } elseif ($startMonth !== $expectedStart) {
            $label = SeasonMonthRange::monthOptions()[$expectedStart] ?? (string) $expectedStart;

            return 'This season must start in '.$label.' (the month after the previous season ends).';
        }

        $months = self::monthsInSeason($startMonth, $endMonth);
        if ($months === []) {
            return 'Choose a valid end month for this season.';
        }

        foreach ($months as $m) {
            if ($map[$m] ?? false) {
                return 'That range overlaps months already covered by another season.';
            }
        }

        return null;
    }
}
