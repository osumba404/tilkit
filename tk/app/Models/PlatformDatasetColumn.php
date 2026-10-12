<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformDatasetColumn extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'platform_dataset_id',
        'name',
        'position',
        'inferred_type',
        'sample_values',
        'null_count',
    ];

    protected function casts(): array
    {
        return [
            'sample_values' => 'array',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(PlatformDataset::class, 'platform_dataset_id');
    }
}
