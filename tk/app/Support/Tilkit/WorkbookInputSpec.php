<?php

namespace App\Support\Tilkit;

/**
 * Maps each raw workbook column to a capture control kind (v4 Community Data List).
 */
class WorkbookInputSpec
{
    /** @var array<string, string> */
    private const BY_COLUMN = [
        'A' => 'text',
        'B' => 'text',
        'C' => 'textarea',
        'D' => 'textarea',
        'E' => 'textarea',
        'F' => 'textarea',
        'G' => 'text',
        'H' => 'text',
        'I' => 'list_text',
        'J' => 'history_scale',
        'K' => 'lead_time_class',
        'L' => 'rating',
        'M' => 'rating',
        'N' => 'yes_no',
        'O' => 'textarea',
        'P' => 'date',
        'Q' => 'date',
        'R' => 'date',
        'S' => 'date',
        'T' => 'rating',
        'U' => 'tercile',
        'V' => 'rating',
        'W' => 'textarea',
        'X' => 'list_text',
        'Y' => 'photos',
        'Z' => 'gps',
        'AA' => 'text',
        'AB' => 'text',
        'AC' => 'yes_no',
        'AD' => 'yes_no',
        'AE' => 'yes_no',
        'AF' => 'text',
        'AG' => 'textarea',
        'AH' => 'readonly',
        'AI' => 'readonly',
        'AJ' => 'readonly',
        'AK' => 'readonly',
        'AL' => 'readonly',
        'AM' => 'readonly',
        'AN' => 'date',
        'AO' => 'text',
        'AP' => 'validation',
        'AQ' => 'textarea',
        'AR' => 'textarea',
        'AS' => 'date',
        'AT' => 'date',
        'AU' => 'rating_321',
        'AV' => 'rating_321',
        'AZ' => 'outcome',
        'BA' => 'textarea',
        'BB' => 'yes_no',
        'BC' => 'consent',
        'BD' => 'text',
        'BE' => 'text',
        'BF' => 'text',
        'BG' => 'integer',
        'BH' => 'text',
        'BI' => 'text',
        'BJ' => 'text',
        'BK' => 'text',
        'BU' => 'text',
        'BV' => 'textarea',
        'BW' => 'textarea',
        'BX' => 'text',
        'BY' => 'text',
        'BZ' => 'text',
        'CI' => 'date',
        'CK' => 'text',
        'CL' => 'text',
        'CN' => 'textarea',
        'CO' => 'textarea',
        'CQ' => 'rating',
        'CR' => 'number',
        'CW' => 'textarea',
        'CX' => 'text',
        'CY' => 'yes_no',
        'CZ' => 'textarea',
        'DA' => 'yes_no',
        'DB' => 'date',
        'DC' => 'text',
        'DD' => 'integer',
        'DE' => 'integer',
        'DF' => 'number',
        'DG' => 'number',
        'DJ' => 'tercile',
        'DL' => 'text',
        'DM' => 'yes_no',
        'DN' => 'textarea',
        'DO' => 'yes_no',
        'DP' => 'textarea',
    ];

    public static function kind(array $column): string
    {
        $letter = strtoupper($column['column']);

        if (isset(self::BY_COLUMN[$letter])) {
            return self::BY_COLUMN[$letter];
        }

        return self::inferFromLabel($column);
    }

    public static function isReadOnly(array $column): bool
    {
        return self::kind($column) === 'readonly';
    }

    public static function skipInForm(array $column): bool
    {
        return in_array(self::kind($column), ['photos', 'readonly'], true);
    }

    private static function inferFromLabel(array $column): string
    {
        $field = strtolower($column['field']);

        if (str_contains($field, '(1/0)')) {
            return 'yes_no';
        }
        if (preg_match('/\(3\/2\/1\/0\)/', $field)) {
            return 'rating';
        }
        if (preg_match('/\(3\/2\/1\)/', $field)) {
            return 'rating_321';
        }
        if (str_contains($field, 'date') || str_contains($field, 'onset') || str_contains($field, 'cessation')) {
            return 'date';
        }
        if (str_contains($field, 'gps')) {
            return 'gps';
        }
        if (str_contains($field, 'photos')) {
            return 'photos';
        }
        if (str_contains($field, 'sector impact')) {
            return 'list_text';
        }
        if (str_contains($field, 'free text') || str_contains($field, 'description') || str_contains($field, 'notes')) {
            return 'textarea';
        }
        if (str_contains($field, 'number of') || str_contains($field, 'people reached')) {
            return 'integer';
        }
        if (str_contains($field, '%') || str_contains($field, 'vci')) {
            return 'number';
        }

        return 'text';
    }
}
