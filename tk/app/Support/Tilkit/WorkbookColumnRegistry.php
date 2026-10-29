<?php

namespace App\Support\Tilkit;

use InvalidArgumentException;

class WorkbookColumnRegistry
{
    /** @var list<array{column: string, field: string, key: string, group: string, stage: string, provided_by: string, derived: bool, filled_in_demo: bool}>|null */
    private static ?array $columns = null;

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        if (self::$columns === null) {
            $path = config('tilkit.weather.column_register_path');
            $json = file_get_contents($path);
            self::$columns = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }

        return self::$columns;
    }

    /** @return array<string, mixed>|null */
    public static function forColumn(string $letter): ?array
    {
        $letter = strtoupper(trim($letter));

        foreach (self::all() as $col) {
            if ($col['column'] === $letter) {
                return $col;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public static function rawColumns(): array
    {
        return array_values(array_filter(self::all(), fn ($c) => ! $c['derived']));
    }

    /** @return list<array<string, mixed>> */
    public static function derivedColumns(): array
    {
        return array_values(array_filter(self::all(), fn ($c) => $c['derived']));
    }

    /** @return array<string, list<array<string, mixed>>> */
    public static function rawColumnsByStage(): array
    {
        $groups = [];
        foreach (self::rawColumns() as $col) {
            $stage = $col['stage'] ?: 'Other';
            $groups[$stage][] = $col;
        }

        return $groups;
    }

    /**
     * Sheet 1 bands by leading column letter (A, AA, AB … then B, BA, BB …), in workbook order.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function rawColumnsByLeadingLetter(): array
    {
        $groups = [];
        $order = [];
        foreach (self::rawColumns() as $col) {
            $letter = $col['column'];
            if ($letter === '') {
                continue;
            }
            $key = $letter[0];
            if (! isset($groups[$key])) {
                $order[] = $key;
                $groups[$key] = [];
            }
            $groups[$key][] = $col;
        }

        $ordered = [];
        foreach ($order as $key) {
            $ordered[$key] = $groups[$key];
        }

        return $ordered;
    }

    /** Human-readable list of column letters in a band (for section headings). */
    public static function columnBandSummary(string $leadingLetter, array $columns): string
    {
        $visible = array_values(array_filter(
            $columns,
            fn (array $col) => ! WorkbookInputSpec::skipInForm($col),
        ));
        if ($visible === []) {
            return 'Column '.$leadingLetter;
        }

        $letters = array_map(fn (array $col) => $col['column'], $visible);
        if (count($letters) <= 4) {
            return implode(', ', $letters);
        }

        return $letters[0].', '.$letters[1].', … '.$letters[count($letters) - 1];
    }

    /**
     * Letter bands that contain at least one capturable field (excludes photo-only bands if empty).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function rawColumnBandsForForm(): array
    {
        return array_filter(
            self::rawColumnsByLeadingLetter(),
            fn (array $cols) => count(array_filter(
                $cols,
                fn (array $col) => ! WorkbookInputSpec::skipInForm($col),
            )) > 0,
        );
    }

    public static function inputKind(array $column): string
    {
        return WorkbookInputSpec::kind($column);
    }

    public static function formCaption(array $column): string
    {
        return WorkbookFormLabel::caption($column);
    }

    public static function formHint(array $column): ?string
    {
        return WorkbookFormLabel::hint($column);
    }

    public static function assertRawColumn(string $letter): array
    {
        $meta = self::forColumn($letter);
        if ($meta === null) {
            throw new InvalidArgumentException("Unknown workbook column {$letter}.");
        }
        if ($meta['derived']) {
            throw new InvalidArgumentException("Column {$letter} is derived and cannot be written by clients.");
        }

        return $meta;
    }
}
