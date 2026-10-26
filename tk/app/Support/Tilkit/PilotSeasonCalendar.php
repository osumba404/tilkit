<?php

namespace App\Support\Tilkit;

/**
 * Rainy-season windows for Kenya / Ghana pilot seed data (workbook AM labels + TilkitSeason dates).
 */
class PilotSeasonCalendar
{
    /** @return array{label: string, starts_on: string, ends_on: string, start_month: int, end_month: int, kind: string} */
    public static function kenya(string $seasonKey): array
    {
        return match ($seasonKey) {
            'mam' => [
                'label' => 'MAM 2026',
                'starts_on' => '2026-03-01',
                'ends_on' => '2026-05-31',
                'start_month' => 3,
                'end_month' => 5,
                'kind' => 'mam',
            ],
            default => [
                'label' => 'OND 2026',
                'starts_on' => '2026-10-01',
                'ends_on' => '2027-02-28',
                'start_month' => 10,
                'end_month' => 12,
                'kind' => 'ond',
            ],
        };
    }

    /** @return array{label: string, starts_on: string, ends_on: string, start_month: int, end_month: int, kind: string} */
    public static function ghanaNorthern(): array
    {
        return [
            'label' => 'Rainy season 2026 (north)',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-10-31',
            'start_month' => 4,
            'end_month' => 10,
            'kind' => 'ghana_north',
        ];
    }

    /** @return array{label: string, starts_on: string, ends_on: string, start_month: int, end_month: int, kind: string} */
    public static function ghanaSouthernMajor(): array
    {
        return [
            'label' => 'AMJ 2026 (major rains)',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-07-31',
            'start_month' => 4,
            'end_month' => 7,
            'kind' => 'ghana_south_major',
        ];
    }

    /** @return array{label: string, starts_on: string, ends_on: string, start_month: int, end_month: int, kind: string} */
    public static function ghanaSouthernMinor(): array
    {
        return [
            'label' => 'SON 2026 (minor rains)',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-11-30',
            'start_month' => 9,
            'end_month' => 11,
            'kind' => 'ghana_south_minor',
        ];
    }

    /** @return array{starts_on: string, ends_on: string, start_month: int, end_month: int}|null */
    public static function datesForWorkbookSeasonLabel(string $seasonLabel): ?array
    {
        $label = trim($seasonLabel);
        $upper = strtoupper($label);

        if (preg_match('/^MAM\s+/i', $label)) {
            $meta = self::kenya('mam');

            return self::sliceDates($meta);
        }
        if (preg_match('/^OND\s+/i', $label)) {
            $meta = self::kenya('ond');

            return self::sliceDates($meta);
        }
        if (str_contains($upper, 'AMJ') || str_contains($upper, 'MAJOR')) {
            return self::sliceDates(self::ghanaSouthernMajor());
        }
        if (str_contains($upper, 'SON') || str_contains($upper, 'MINOR')) {
            return self::sliceDates(self::ghanaSouthernMinor());
        }
        if (str_contains($upper, 'NORTH') || str_contains($upper, 'RAINY SEASON')) {
            return self::sliceDates(self::ghanaNorthern());
        }

        return null;
    }

    /** @param  array{starts_on: string, ends_on: string, start_month: int, end_month: int}  $meta */
    private static function sliceDates(array $meta): array
    {
        return [
            'starts_on' => $meta['starts_on'],
            'ends_on' => $meta['ends_on'],
            'start_month' => $meta['start_month'],
            'end_month' => $meta['end_month'],
        ];
    }
}
