<?php

namespace Tests\Unit;

use App\Models\PlatformDataset;
use App\Models\PlatformDatasetColumn;
use App\Models\User;
use App\Services\Datasets\DatasetAccService;
use App\Support\DatasetAccFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatasetAccServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_distribution_respects_row_filter(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $workspace = 'datasets/test-uuid';
        Storage::disk('local')->makeDirectory($workspace);
        $csv = "region,amount\nNorth,10\nSouth,20\nNorth,5\n";
        Storage::disk('local')->put($workspace.'/data.csv', $csv);

        $dataset = PlatformDataset::create([
            'uuid' => 'test-uuid',
            'uploaded_by' => $user->id,
            'name' => 'Test',
            'original_filename' => 't.csv',
            'storage_path' => $workspace.'/data.csv',
            'workspace_path' => $workspace,
            'row_count' => 3,
            'column_count' => 2,
            'status' => 'ready',
        ]);
        PlatformDatasetColumn::create([
            'platform_dataset_id' => $dataset->id,
            'name' => 'region',
            'position' => 0,
            'inferred_type' => 'category',
            'sample_values' => ['North', 'South'],
            'null_count' => 0,
        ]);
        PlatformDatasetColumn::create([
            'platform_dataset_id' => $dataset->id,
            'name' => 'amount',
            'position' => 1,
            'inferred_type' => 'integer',
            'sample_values' => ['10'],
            'null_count' => 0,
        ]);

        $service = new DatasetAccService;
        $filters = new DatasetAccFilters('region', 'North', [
            ['id' => 'acc_chart_0', 'type' => 'pie', 'column' => 'region', 'x_column' => null, 'y_column' => null, 'z_column' => null, 'metric' => 'count'],
        ]);
        $overview = $service->overview($dataset, $filters);

        $this->assertSame(2, $overview['filtered_rows']);
        $this->assertSame(['North' => 2], $service->distribution($dataset, 'region', $filters));

        $charts = $service->materializeCharts($dataset, $filters);
        $this->assertCount(1, $charts);
        $this->assertSame('pie', $charts[0]['type']);

        $tableFilters = new DatasetAccFilters(null, null, [
            ['id' => 't1', 'type' => 'stats', 'column' => 'amount', 'x_column' => null, 'y_column' => null, 'z_column' => null, 'metric' => 'count'],
        ]);
        $stats = $service->materializeCharts($dataset, $tableFilters);
        $this->assertSame('stats', $stats[0]['render']);
        $this->assertNotEmpty($stats[0]['stats']);
    }
}
