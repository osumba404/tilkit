<?php

namespace Tests\Unit;

use App\Support\Tilkit\WorkbookColumnRegistry;
use App\Support\Tilkit\WorkbookInputSpec;
use Tests\TestCase;

class TilkitWorkbookInputSpecTest extends TestCase
{
    public function test_every_raw_column_has_explicit_input_kind(): void
    {
        $reflection = new \ReflectionClass(WorkbookInputSpec::class);
        /** @var array<string, string> $map */
        $map = $reflection->getConstant('BY_COLUMN');

        $missing = [];
        foreach (WorkbookColumnRegistry::rawColumns() as $col) {
            if (! isset($map[$col['column']])) {
                $missing[] = $col['column'];
            }
        }

        $this->assertSame([], $missing, 'Add kinds in WorkbookInputSpec for: '.implode(', ', $missing));
        $this->assertCount(count(WorkbookColumnRegistry::rawColumns()), $map);
    }

    public function test_location_and_governance_fields_use_typed_controls(): void
    {
        $this->assertSame('gps', WorkbookInputSpec::kind(WorkbookColumnRegistry::forColumn('Z')));
        $this->assertSame('textarea', WorkbookInputSpec::kind(WorkbookColumnRegistry::forColumn('O')));
        $this->assertSame('date', WorkbookInputSpec::kind(WorkbookColumnRegistry::forColumn('P')));
        $this->assertSame('consent', WorkbookInputSpec::kind(WorkbookColumnRegistry::forColumn('BC')));
        $this->assertSame('readonly', WorkbookInputSpec::kind(WorkbookColumnRegistry::forColumn('AI')));
        $this->assertSame('integer', WorkbookInputSpec::kind(WorkbookColumnRegistry::forColumn('BG')));
    }
}
