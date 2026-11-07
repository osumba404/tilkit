<?php

namespace Tests\Unit;

use App\Models\Tilkit\TilkitIndicatorCode;
use App\Support\Tilkit\IndicatorLegendTree;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorLegendTreeBucketTest extends TestCase
{
    use RefreshDatabase;

    public function test_buckets_by_legend_code_string_when_db_columns_are_wrong(): void
    {
        $protocol = config('tilkit.weather.protocol_version');
        TilkitIndicatorCode::query()->create([
            'code' => '02-ATM-CLD',
            'category' => '01',
            'type' => 'ANI',
            'sub_type' => 'CLD',
            'label' => 'Clouds',
            'protocol_version' => $protocol,
        ]);

        $tree = app(IndicatorLegendTree::class)->build();

        $this->assertCount(1, $tree);
        $this->assertSame('02', $tree[0]['key']);
        $this->assertSame('02-ATM', $tree[0]['types'][0]['key']);
    }
}
