<?php

namespace Tests\Unit;

use App\Models\Tilkit\TilkitIndicatorCode;
use App\Support\Tilkit\IndicatorHierarchy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndicatorHierarchyTest extends TestCase
{
    public function test_workbook_descriptor_separates_levels(): void
    {
        $hierarchy = app(IndicatorHierarchy::class);
        $code = new TilkitIndicatorCode([
            'code' => '01-PLT-FLW',
            'category' => '01',
            'type' => 'PLT',
            'sub_type' => 'FLW',
            'label' => 'Flowering/blooming',
        ]);

        $wb = $hierarchy->workbookDescriptor($code, 'Mutundu flowering tree');
        $this->assertStringStartsWith('01 —', $wb['A']);
        $this->assertStringContainsString('01-PLT', $wb['B']);
        $this->assertStringContainsString('FLW', $wb['B']);
        $this->assertStringContainsString('Flowering/blooming', $wb['B']);
        $this->assertSame('Mutundu flowering tree', $wb['C']);
        $this->assertSame('', $wb['D']);
    }

    public function test_column_d_bleed_rejects_bird_on_plant_code(): void
    {
        $hierarchy = app(IndicatorHierarchy::class);
        $code = new TilkitIndicatorCode([
            'code' => '01-PLT-FLW',
            'category' => '01',
            'type' => 'PLT',
            'sub_type' => 'FLW',
            'label' => 'Flowering/blooming',
        ]);

        $msg = $hierarchy->columnBleedViolation($code, 'D', 'Birds flying eastwards');
        $this->assertNotNull($msg);
    }

    public function test_column_a_bleed_rejects_type_line_in_category(): void
    {
        $hierarchy = app(IndicatorHierarchy::class);
        $code = new TilkitIndicatorCode([
            'code' => '01-PLT-FLW',
            'category' => '01',
            'type' => 'PLT',
            'sub_type' => 'FLW',
        ]);

        $msg = $hierarchy->columnBleedViolation($code, 'A', '01-PLT flowering');
        $this->assertNotNull($msg);
    }

    #[DataProvider('invalidLegendRows')]
    public function test_assert_legend_row_invalid(string $code, string $cat, string $type, string $sub): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(IndicatorHierarchy::class)->assertLegendRowValid($code, $cat, $type, $sub);
    }

    /** @return array<string, array{0: string, 1: string, 2: string, 3: string}> */
    public static function invalidLegendRows(): array
    {
        return [
            'type wrong category' => ['02-PLT-FLW', '02', 'PLT', 'FLW'],
            'non canonical type line' => ['01-XXX-FLW', '01', 'XXX', 'FLW'],
            'code mismatch' => ['01-PLT-FLW', '01', 'ANI', 'FLW'],
        ];
    }

    public function test_analytical_levels_document_all_stages(): void
    {
        $levels = app(IndicatorHierarchy::class)->analyticalLevels();
        $this->assertGreaterThanOrEqual(8, count($levels));
        $workbooks = implode(' ', array_column($levels, 'workbook'));
        $this->assertStringContainsString('A', $workbooks);
        $this->assertStringContainsString('D', $workbooks);
        $this->assertStringContainsString('U', $workbooks);
    }
}
