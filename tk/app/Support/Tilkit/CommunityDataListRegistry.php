<?php

namespace App\Support\Tilkit;

use InvalidArgumentException;

class CommunityDataListRegistry
{
    /** @var list<array<string, mixed>>|null */
    private static ?array $items = null;

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        if (self::$items === null) {
            $path = config_path('tilkit_community_data_list.json');
            self::$items = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }

        return self::$items;
    }

    /** @return array<string, list<array<string, mixed>>> */
    public static function bySection(): array
    {
        $groups = [];
        foreach (self::items() as $item) {
            $section = $item['section'] ?? 'Other';
            $groups[$section][] = $item;
        }

        return $groups;
    }

    /** @return list<array<string, mixed>> Items for section key A–E */
    public static function itemsForSectionKey(string $key): array
    {
        if (! preg_match('/^[A-E]$/', $key)) {
            return [];
        }

        return array_values(array_filter(
            self::items(),
            fn (array $item) => str_starts_with((string) ($item['section'] ?? ''), $key.'.')
        ));
    }

    /** @return list<string> Unique column letters for a section key */
    public static function columnLettersForSectionKey(string $key): array
    {
        $letters = [];
        foreach (self::itemsForSectionKey($key) as $item) {
            foreach (self::parseColumns($item['columns'] ?? '') as $letter) {
                $letters[$letter] = true;
            }
        }

        return array_keys($letters);
    }

    /** @return list<string> Column letters referenced in the community data list */
    public static function allColumnLetters(): array
    {
        $letters = [];
        foreach (self::items() as $item) {
            foreach (self::parseColumns($item['columns'] ?? '') as $letter) {
                $letters[$letter] = true;
            }
        }

        return array_keys($letters);
    }

    /** @return list<string> */
    public static function parseColumns(string $spec): array
    {
        $letters = [];
        $spec = str_replace(['–', '—'], '-', $spec);
        if (preg_match('/^([A-Z]{1,2})\s*-\s*([A-Z]{1,2})$/', trim($spec), $m)) {
            return self::expandRange($m[1], $m[2]);
        }
        if (preg_match_all('/[A-Z]{1,2}/', strtoupper($spec), $matches)) {
            foreach ($matches[0] as $m) {
                if (! in_array($m, ['TO', 'OR', 'IN', 'OF', 'NO', 'ID'], true)) {
                    $letters[] = $m;
                }
            }
        }

        return array_values(array_unique($letters));
    }

    /** @return list<string> */
    private static function expandRange(string $from, string $to): array
    {
        $fromIdx = self::columnIndex($from);
        $toIdx = self::columnIndex($to);
        if ($fromIdx > $toIdx) {
            [$fromIdx, $toIdx] = [$toIdx, $fromIdx];
        }
        $out = [];
        for ($i = $fromIdx; $i <= $toIdx; $i++) {
            $out[] = self::columnLetter($i);
        }

        return $out;
    }

    private static function columnIndex(string $letter): int
    {
        $letter = strtoupper($letter);
        $n = 0;
        for ($i = 0; $i < strlen($letter); $i++) {
            $n = $n * 26 + (ord($letter[$i]) - 64);
        }

        return $n;
    }

    private static function columnLetter(int $index): string
    {
        $s = '';
        while ($index > 0) {
            $index--;
            $s = chr(65 + ($index % 26)).$s;
            $index = intdiv($index, 26);
        }

        if ($s === '') {
            throw new InvalidArgumentException('Invalid column index');
        }

        return $s;
    }
}
