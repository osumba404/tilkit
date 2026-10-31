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

    public function test_taxonomy_mindmap_nests_sub_types_under_types(): void
    {
        $protocol = config('tilkit.weather.protocol_version');
        TilkitIndicatorCode::query()->create([
            'code' => '01-ANI-FRO',
            'category' => '01',
            'type' => 'ANI',
            'sub_type' => 'FRO',
            'label' => 'Frog',
            'protocol_version' => $protocol,
        ]);

        $map = app(IndicatorCodeLegendGrouper::class)->taxonomyMindmap();

        $this->assertCount(4, $map['capture_levels']);
        $this->assertSame('A', $map['capture_levels'][0]['workbook']);
        $cat = $map['categories'][0];
        $this->assertSame('01', $cat['key']);
        $type = $cat['types'][0];
        $this->assertSame('01-ANI', $type['key']);
        $this->assertSame('FRO', $type['sub_types'][0]['key']);
        $this->assertContains('01-ANI-FRO', $type['sub_types'][0]['sample_codes']);
    }

    public function test_analysis_mindmap_includes_pillars_and_type_leaves(): void
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

        $analysis = app(IndicatorCodeLegendGrouper::class)->analysisMindmap();

        $this->assertSame('TILKIT Indicator Analysis from Top to Behavior', $analysis['root']);
        $this->assertCount(6, $analysis['pillars']);
        $this->assertTrue($analysis['has_codes']);
        $this->assertStringContainsString('01-PLT', $analysis['code_types'][0]['title']);
        $this->assertNotEmpty($analysis['code_types'][0]['sub_types']);
        $this->assertSame('01-PLT-FLW', $analysis['code_types'][0]['sub_types'][0]['codes'][0]['code']);
    }
}
