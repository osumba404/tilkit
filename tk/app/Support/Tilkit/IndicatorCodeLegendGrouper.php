<?php

namespace App\Support\Tilkit;

use App\Models\Tilkit\TilkitIndicatorCode;
class IndicatorCodeLegendGrouper
{
    /** @return list<array{key: string, label: string, types: list<array{key: string, label: string, codes: list<array{code: string, label: string|null, sub_type: string|null}>}>}> */
    public function groupedLegend(): array
    {
        $taxonomy = $this->taxonomy();
        $categoryOrder = $taxonomy['category_order'] ?? array_keys($taxonomy['categories'] ?? []);

        $codes = TilkitIndicatorCode::query()
            ->whereNull('deprecated_at')
            ->orderBy('category')
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        /** @var array<string, array<string, list<TilkitIndicatorCode>>> $bucket */
        $bucket = [];
        foreach ($codes as $row) {
            $cat = str_pad((string) $row->category, 2, '0', STR_PAD_LEFT);
            $type = strtoupper((string) ($row->type ?? ''));
            $bucket[$cat][$type][] = $row;
        }

        $out = [];
        foreach ($categoryOrder as $catKey) {
            if (! isset($bucket[$catKey])) {
                continue;
            }
            $typesOut = [];
            foreach ($bucket[$catKey] as $typeKey => $typeCodes) {
                $typeLine = $catKey.'-'.$typeKey;
                $typesOut[] = [
                    'key' => $typeLine,
                    'label' => $taxonomy['types'][$typeLine] ?? ($typeKey !== '' ? $typeKey : 'Other'),
                    'codes' => collect($typeCodes)->map(fn (TilkitIndicatorCode $c) => [
                        'code' => $c->code,
                        'label' => $c->label,
                        'sub_type' => $c->sub_type,
                    ])->values()->all(),
                ];
            }
            usort($typesOut, fn ($a, $b) => strcmp($a['key'], $b['key']));

            $out[] = [
                'key' => $catKey,
                'label' => ($taxonomy['categories'][$catKey] ?? 'Category '.$catKey),
                'types' => $typesOut,
            ];
        }

        return $out;
    }

    /** @return array{categories: array<string, string>, types: array<string, string>, category_order: list<string>} */
    private function taxonomy(): array
    {
        $path = config_path('tilkit_indicator_taxonomy.json');
        $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : [];

        return is_array($data) ? $data : ['categories' => [], 'types' => [], 'category_order' => []];
    }
}
