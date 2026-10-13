<?php

namespace Tests\Unit;

use App\Support\DatasetChartSchema;
use PHPUnit\Framework\TestCase;

class DatasetChartSchemaTest extends TestCase
{
    public function test_two_axis_types_require_x_and_y(): void
    {
        $this->assertSame('Pick both X and Y fields for this 2D chart.', DatasetChartSchema::validate([
            'type' => 'scatter',
            'x_column' => 'a',
            'y_column' => null,
        ]));
        $this->assertNull(DatasetChartSchema::validate([
            'type' => 'line',
            'x_column' => 'date',
            'y_column' => 'amount',
        ]));
    }

    public function test_three_axis_type_requires_distinct_x_y_z(): void
    {
        $this->assertSame('Pick X, Y, and Z fields for this 3D chart.', DatasetChartSchema::validate([
            'type' => '3d_bubble',
            'x_column' => 'a',
            'y_column' => 'b',
        ]));
        $this->assertNull(DatasetChartSchema::validate([
            'type' => '3d_bubble',
            'x_column' => 'a',
            'y_column' => 'b',
            'z_column' => 'c',
        ]));
    }
}
