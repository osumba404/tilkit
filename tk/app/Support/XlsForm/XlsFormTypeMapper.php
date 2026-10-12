<?php

namespace App\Support\XlsForm;

use App\Models\DcFieldType;

class XlsFormTypeMapper
{
    /** @var array<string, true> */
    private static ?array $knownTilkit = null;

    public static function toSurveyType(string $tilkitType, ?string $choiceList = null): string
    {
        $choiceList = $choiceList ?: 'choices';

        return match ($tilkitType) {
            'select_one' => 'select_one '.$choiceList,
            'select_multiple' => 'select_multiple '.$choiceList,
            'rank' => 'rank '.$choiceList,
            'group' => 'begin_group',
            'repeat' => 'begin_repeat',
            'datetime' => 'dateTime',
            'geotrace' => 'geotrace',
            'geoshape' => 'geoshape',
            'signature', 'audio', 'video' => 'text',
            default => $tilkitType,
        };
    }

    /**
     * @return array{field_type: string, choice_list: ?string, skip: bool, end_repeat: bool}
     */
    public static function fromSurveyType(string $rawType): array
    {
        $rawType = trim($rawType);
        $lower = strtolower($rawType);

        if ($lower === 'end_group') {
            return ['field_type' => 'group', 'choice_list' => null, 'skip' => true, 'end_repeat' => false];
        }

        if ($lower === 'end repeat' || $lower === 'end_repeat') {
            return ['field_type' => 'repeat', 'choice_list' => null, 'skip' => true, 'end_repeat' => true];
        }

        if ($lower === 'begin_group' || $lower === 'start') {
            return ['field_type' => 'group', 'choice_list' => null, 'skip' => false, 'end_repeat' => false];
        }

        if ($lower === 'begin_repeat') {
            return ['field_type' => 'repeat', 'choice_list' => null, 'skip' => false, 'end_repeat' => false];
        }

        if (preg_match('/^(select_one|select_multiple|rank)\s+(\S+)/i', $rawType, $m)) {
            return [
                'field_type' => strtolower($m[1]),
                'choice_list' => $m[2],
                'skip' => false,
                'end_repeat' => false,
            ];
        }

        $map = [
            'datetime' => 'datetime',
            'dateTime' => 'datetime',
            'date' => 'date',
            'time' => 'time',
            'integer' => 'integer',
            'decimal' => 'decimal',
            'range' => 'range',
            'text' => 'text',
            'note' => 'note',
            'geopoint' => 'geopoint',
            'geotrace' => 'geotrace',
            'geoshape' => 'geoshape',
            'image' => 'image',
            'file' => 'file',
            'barcode' => 'barcode',
            'calculate' => 'calculate',
            'acknowledge' => 'acknowledge',
            'hidden' => 'metadata',
        ];

        $fieldType = $map[$lower] ?? $map[$rawType] ?? null;

        if ($fieldType === null && self::isKnownTilkitType($lower)) {
            $fieldType = $lower;
        }

        if ($fieldType === null) {
            $fieldType = 'text';
        }

        return ['field_type' => $fieldType, 'choice_list' => null, 'skip' => false, 'end_repeat' => false];
    }

    private static function isKnownTilkitType(string $code): bool
    {
        if (self::$knownTilkit === null) {
            self::$knownTilkit = DcFieldType::query()->pluck('code')->flip()->all();
        }

        return isset(self::$knownTilkit[$code]);
    }
}
