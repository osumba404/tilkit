<?php

namespace App\Support\Tilkit;

class WorkbookSheetCatalog
{
    /** @return list<array{id: string, title: string}> */
    public static function sheets(): array
    {
        $path = config_path('tilkit_workbook_sheets.json');

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
