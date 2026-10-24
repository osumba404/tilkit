<?php

namespace App\Services\Tilkit;

/**
 * Generates additional workbook rows for demo / pilot dashboards (not Excel parity fixtures).
 */
class TilkitSyntheticDatasetBuilder
{
    /** @return list<array<string, mixed>> */
    public function rows(): array
    {
        $sites = [
            ['name' => 'Kitui Central', 'ward' => 'Kitui Town', 'county' => 'Kitui'],
            ['name' => 'Mwingi North', 'ward' => 'Kyuso', 'county' => 'Kitui'],
            ['name' => 'Mutomo Hills', 'ward' => 'Mutomo', 'county' => 'Kitui'],
            ['name' => 'Kisumu Lakeshore', 'ward' => 'Kisumu Central', 'county' => 'Kisumu'],
        ];

        $seasons = ['MAM 2025', 'OND 2025', 'MAM 2026', 'OND 2026'];

        $signs = [
            ['uid' => '01-ANI-FRO-001', 'name' => 'Frogs croaking at night near water'],
            ['uid' => '02-ATM-CLD-001', 'name' => 'Heavy morning cloud over hills'],
            ['uid' => '02-ATM-WND-001', 'name' => 'East wind before rains'],
            ['uid' => '03-PLT-TRE-001', 'name' => 'Mango trees flowering early'],
            ['uid' => '03-ANI-BRD-001', 'name' => 'Weaver birds nesting low'],
            ['uid' => '01-AST-MON-001', 'name' => 'Crescent moon orientation'],
            ['uid' => '04-SOI-CRK-001', 'name' => 'Dry soil cracks widening'],
            ['uid' => '04-HYD-RIV-001', 'name' => 'River level low at crossing'],
            ['uid' => '05-PLT-GRS-001', 'name' => 'Grass turning green on plains'],
            ['uid' => '02-ATM-HUM-001', 'name' => 'Humidity rise in evening'],
        ];

        $validationLabels = [
            'Community/Plenary-endorsed',
            'FGD-validated only',
            'Community/Plenary-endorsed',
            'Provisional/emerging',
            'Sub-community-specific',
        ];

        $rows = [];
        $i = 0;

        foreach ($sites as $site) {
            foreach ($seasons as $season) {
                foreach ($signs as $sign) {
                    $i++;
                    $tercile = ($i % 3) + 1;
                    $reliability = max(1, min(3, 2 + ($i % 3) - 1));
                    $rows[] = [
                        'A' => explode('-', $sign['uid'])[0],
                        'C' => $sign['name'],
                        'L' => $reliability,
                        'M' => $reliability,
                        'N' => $tercile,
                        'U' => $reliability,
                        'V' => $reliability,
                        'AC' => 1,
                        'AD' => 1,
                        'AH' => $sign['uid'],
                        'AI' => $site['name'],
                        'AJ' => $site['ward'],
                        'AK' => $site['county'],
                        'AL' => 'Kenya',
                        'AM' => $season,
                        'AO' => 'Demo community narrative '.$i,
                        'AP' => $validationLabels[$i % count($validationLabels)],
                        'AV' => $reliability,
                        'BA' => 'Synthetic pilot row for dashboard demos',
                        'BL' => 3,
                        'BM' => 2,
                        'BN' => 2,
                        'BO' => 1,
                        'BP' => 0,
                        'BC' => 3,
                        'BB' => 0,
                        'BU' => ($i % 4) + 1,
                    ];
                }
            }
        }

        return $rows;
    }
}
