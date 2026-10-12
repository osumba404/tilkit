<?php

namespace Tests\Unit;

use App\Services\Datasets\DatasetTypeInference;
use PHPUnit\Framework\TestCase;

class DatasetTypeInferenceTest extends TestCase
{
    public function test_profiles_csv_headers_and_types(): void
    {
        $path = sys_get_temp_dir().'/tilkit_test_'.uniqid().'.csv';
        file_put_contents($path, "amount,category,active\n10,food,true\n20,travel,false\n");

        $profile = (new DatasetTypeInference)->profileCsv($path);
        unlink($path);

        $this->assertSame(2, $profile['row_count']);
        $this->assertCount(3, $profile['columns']);
        $this->assertSame('amount', $profile['columns'][0]['name']);
        $this->assertSame('integer', $profile['columns'][0]['inferred_type']);
    }
}
