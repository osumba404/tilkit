<?php

namespace Tests\Unit;

use App\Support\XlsForm\XlsFormTypeMapper;
use PHPUnit\Framework\TestCase;

class XlsFormTypeMapperTest extends TestCase
{
    public function test_maps_select_one_with_list(): void
    {
        $parsed = XlsFormTypeMapper::fromSurveyType('select_one gender');

        $this->assertSame('select_one', $parsed['field_type']);
        $this->assertSame('gender', $parsed['choice_list']);
        $this->assertFalse($parsed['skip']);
    }

    public function test_end_repeat_marker(): void
    {
        $parsed = XlsFormTypeMapper::fromSurveyType('end_repeat');

        $this->assertTrue($parsed['skip']);
        $this->assertTrue($parsed['end_repeat']);
    }

    public function test_tilkit_group_and_repeat_types(): void
    {
        $this->assertSame('begin_group', XlsFormTypeMapper::toSurveyType('group'));
        $this->assertSame('begin_repeat', XlsFormTypeMapper::toSurveyType('repeat'));
        $this->assertSame('select_one my_list', XlsFormTypeMapper::toSurveyType('select_one', 'my_list'));
    }
}
