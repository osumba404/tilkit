<?php

namespace Tests\Feature;

use App\Models\PlatformDataset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformDatasetAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_dataset_workspace(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.datasets.index'))
            ->assertOk()
            ->assertSee('Dataset workspace', false);
    }

    public function test_user_cannot_view_another_users_dataset(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $dataset = PlatformDataset::create([
            'uploaded_by' => $owner->id,
            'name' => 'Private',
            'original_filename' => 'a.csv',
            'storage_path' => 'datasets/x',
            'workspace_path' => 'datasets/x',
            'row_count' => 1,
            'column_count' => 1,
            'status' => 'ready',
        ]);

        $this->actingAs($other)
            ->get(route('platform.datasets.show', $dataset))
            ->assertForbidden();
    }
}
