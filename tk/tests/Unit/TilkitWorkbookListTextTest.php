<?php

namespace Tests\Unit;

use App\Support\Tilkit\WorkbookCapturePreparer;
use App\Support\Tilkit\WorkbookInputSpec;
use App\Support\Tilkit\WorkbookColumnRegistry;
use App\Support\Tilkit\WorkbookValueNormalizer;
use Tests\TestCase;

class TilkitWorkbookListTextTest extends TestCase
{
    public function test_sector_columns_use_list_text_control(): void
    {
        $this->assertSame('list_text', WorkbookInputSpec::kind(WorkbookColumnRegistry::forColumn('I')));
        $this->assertSame('list_text', WorkbookInputSpec::kind(WorkbookColumnRegistry::forColumn('X')));
    }

    public function test_split_list_text_from_demo_formats(): void
    {
        $this->assertSame(['1', '2', '4', '9'], WorkbookCapturePreparer::splitListText('1;2;4;9'));
        $this->assertSame(
            ['Crops 3+', 'Water 3+', 'Livestock 2−', 'Health 2−'],
            WorkbookCapturePreparer::splitListText('Crops 3+; Water 3+; Livestock 2−; Health 2−'),
        );
    }

    public function test_normalize_and_display_list_text_round_trip(): void
    {
        $normalized = WorkbookValueNormalizer::normalize(['Crops 3+', '', 'Water 2−'], 'list_text');
        $this->assertSame(['Crops 3+', 'Water 2−'], $normalized['value_json']);
        $this->assertSame('Crops 3+; Water 2−', $normalized['value_text']);

        $display = WorkbookValueNormalizer::display(null, $normalized['value_text'], null, $normalized['value_json'], 'list_text');
        $this->assertSame(['Crops 3+', 'Water 2−'], $display);
    }
}
