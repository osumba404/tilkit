<?php

namespace App\Support;

/**
 * Evaluates simple relevance expressions for public forms.
 * Supported forms (field names are form input names):
 *   field_name=值
 *   field_name!=值
 *   field_name          (non-empty)
 *   field_name.empty
 */
class RelevanceEvaluator
{
    /** @param array<string, mixed> $values */
    public static function isVisible(?string $expression, array $values): bool
    {
        if ($expression === null || trim($expression) === '') {
            return true;
        }

        $expr = trim($expression);

        if (preg_match('/^([a-z0-9_]+)\s*!=\s*(.+)$/i', $expr, $m)) {
            $actual = self::valueFor($values, $m[1]);

            return (string) $actual !== trim($m[2], " \t\"'");
        }

        if (preg_match('/^([a-z0-9_]+)\s*=\s*(.+)$/i', $expr, $m)) {
            $actual = self::valueFor($values, $m[1]);

            return (string) $actual === trim($m[2], " \t\"'");
        }

        if (str_ends_with($expr, '.empty')) {
            $name = substr($expr, 0, -6);

            return self::isEmpty(self::valueFor($values, $name));
        }

        if (preg_match('/^[a-z0-9_]+$/i', $expr)) {
            return ! self::isEmpty(self::valueFor($values, $expr));
        }

        return true;
    }

    /** @param array<string, mixed> $values */
    private static function valueFor(array $values, string $name): mixed
    {
        if (! array_key_exists($name, $values)) {
            return null;
        }

        return $values[$name];
    }

    private static function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === false) {
            return true;
        }

        if (is_array($value) && count($value) === 0) {
            return true;
        }

        return false;
    }
}
