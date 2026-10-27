<?php

namespace Tests\Unit;

use App\Support\Tilkit\WorkbookFormLabel;
use Tests\TestCase;

class WorkbookFormLabelTest extends TestCase
{
    public function test_caption_includes_workbook_column_and_plain_language(): void
    {
        $caption = WorkbookFormLabel::caption(['column' => 'AC', 'field' => 'Confirmed (1/0)']);

        $this->assertStringContainsString('Workbook column AC', $caption);
        $this->assertStringContainsString('Focus Group Discussion (FGD)', $caption);
    }

    public function test_expand_acronyms_spells_out_ndma(): void
    {
        $text = WorkbookFormLabel::expandAcronyms('NDMA drought phase');

        $this->assertStringContainsString('National Drought Management Authority (NDMA)', $text);
    }
}
