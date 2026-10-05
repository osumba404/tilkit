<?php

namespace Database\Seeders;

use App\Models\DcFieldType;
use Illuminate\Database\Seeder;

class FieldTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['text', 'TXT', 'TXT-01', 'text', true, false],
            ['note', 'TXT', 'TXT-02', 'none', false, false],
            ['integer', 'NUM', 'NUM-01', 'number', true, false],
            ['decimal', 'NUM', 'NUM-02', 'number', true, false],
            ['range', 'NUM', 'NUM-03', 'number', true, false],
            ['select_one', 'CHO', 'CHO-01', 'choice', true, false],
            ['select_multiple', 'CHO', 'CHO-02', 'choice', true, false],
            ['rank', 'CHO', 'CHO-03', 'choice', true, false],
            ['date', 'DT', 'DT-01', 'date', true, false],
            ['time', 'DT', 'DT-02', 'time', true, false],
            ['datetime', 'DT', 'DT-03', 'datetime', true, false],
            ['geopoint', 'GEO', 'GEO-01', 'geo', true, false],
            ['geotrace', 'GEO', 'GEO-02', 'geo', true, false],
            ['geoshape', 'GEO', 'GEO-03', 'geo', true, false],
            ['image', 'MED', 'MED-01', 'file', true, false],
            ['audio', 'MED', 'MED-02', 'file', true, false],
            ['video', 'MED', 'MED-03', 'file', true, false],
            ['file', 'MED', 'MED-04', 'file', true, false],
            ['barcode', 'HW', 'HW-01', 'text', true, false],
            ['signature', 'BIO', 'BIO-01', 'file', true, false],
            ['calculate', 'CAL', 'CAL-01', 'none', false, false],
            ['group', 'STR', 'STR-01', 'none', false, true],
            ['repeat', 'STR', 'STR-02', 'none', false, true],
            ['metadata', 'META', 'META-01', 'json', false, false],
            ['acknowledge', 'SENS', 'SENS-01', 'bool', true, false],
        ];

        foreach ($types as [$code, $family, $catalogueId, $valueKind, $isInput, $isContainer]) {
            DcFieldType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'family' => $family,
                    'catalogue_id' => $catalogueId,
                    'value_kind' => $valueKind,
                    'is_input' => $isInput,
                    'is_container' => $isContainer,
                ]
            );
        }
    }
}
