<?php

namespace Tests\Unit;

use App\Models\Tilkit\TilkitIndicatorCode;
use App\Support\Tilkit\IndicatorCodeLegendGrouper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TilkitIndicatorLegendGrouperTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_codes_by_category_and_type(): void
    {
        $protocol = config('tilkit.weather.protocol_version');
        TilkitIndicatorCode::query()->create([
            'code' => '01-PLT-FLW',
            'category' => '01',
            'type' => 'PLT',
            'sub_type' => 'FLW',
            'label' => 'Flowering',
            'protocol_version' => $protocol,
        ]);
        TilkitIndicatorCode::query()->create([
            'code' => '01-ANI-FRO',
            'category' => '01',
            'type' => 'ANI',
            'sub_type' => 'FRO',
            'label' => 'Frog',
            'protocol_version' => $protocol,
        ]);
        TilkitIndicatorCode::query()->create([
            'code' => '06-SEN-ODR',
            'category' => '06',
            'type' => 'SEN',
            'sub_type' => 'ODR',
            'label' => 'Odour',
            'protocol_version' => $protocol,
        ]);

        $groups = app(IndicatorCodeLegendGrouper::class)->groupedLegend();

        $this->assertCount(2, $groups);
        $this->assertSame('01', $groups[0]['key']);
        $this->assertCount(2, $groups[0]['types']);
        $typeKeys = collect($groups[0]['types'])->pluck('key')->all();
        $this->assertEqualsCanonicalizing(['01-ANI', '01-PLT'], $typeKeys);
        $this->assertSame('06', $groups[1]['key']);
    }
}
