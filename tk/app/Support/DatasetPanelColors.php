<?php

namespace App\Support;

use Illuminate\Http\Request;

class DatasetPanelColors
{
    /** @return list<string> */
    public static function defaults(): array
    {
        return ['#0a7c94', '#5b6573', '#94a3b8', '#2a3441', '#08697d', '#475569'];
    }

    /** @return list<string> */
    public static function fromRequest(Request $request): array
    {
        $colors = [];
        $raw = $request->input('colors', []);
        if (is_array($raw)) {
            foreach ($raw as $value) {
                $hex = self::normalizeHex(is_string($value) ? $value : null);
                if ($hex !== null) {
                    $colors[] = $hex;
                }
            }
        }

        for ($i = 1; $i <= 6; $i++) {
            $hex = self::normalizeHex($request->input("color_{$i}"));
            if ($hex !== null) {
                $colors[] = $hex;
            }
        }

        $colors = array_values(array_unique($colors));

        return $colors === [] ? self::defaults() : array_slice($colors, 0, 6);
    }

    /** @param  list<string>|null  $stored */
    public static function resolve(?array $stored): array
    {
        if ($stored === null || $stored === []) {
            return self::defaults();
        }

        $out = [];
        foreach ($stored as $c) {
            $hex = self::normalizeHex($c);
            if ($hex !== null) {
                $out[] = $hex;
            }
        }

        return $out === [] ? self::defaults() : array_slice($out, 0, 6);
    }

    private static function normalizeHex(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim($value);
        if (! str_starts_with($value, '#')) {
            $value = '#'.$value;
        }

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtolower($value) : null;
    }
}
