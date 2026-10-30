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

    public function test_column_reference_matches_letter(): void
    {
        $this->assertSame('Workbook column AU', WorkbookFormLabel::columnReference('au'));
        $caption = WorkbookFormLabel::captionForLetter('AU');
        $this->assertStringContainsString('Workbook column AU', $caption);
        $this->assertStringContainsString('plenary rating', WorkbookFormLabel::titleForLetter('AU'));
    }

    public function test_rating321_select_options_include_labels_not_numbers_only(): void
    {
        $options = WorkbookFormLabel::rating321SelectOptions();

        $this->assertArrayHasKey(3, $options);
        $this->assertStringContainsString('High', $options[3]);
        $this->assertNotSame('3', $options[3]);
    }
}
