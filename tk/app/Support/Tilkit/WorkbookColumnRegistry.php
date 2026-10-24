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

    public static function inputKind(array $column): string
    {
        return WorkbookInputSpec::kind($column);
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
