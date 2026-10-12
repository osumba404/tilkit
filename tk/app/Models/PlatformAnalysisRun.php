<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAnalysisRun extends Model
{
    protected $fillable = [
        'platform_dataset_id',
        'user_id',
        'prompt',
        'generated_code',
        'attempts',
        'status',
        'error_message',
        'summary',
        'chart_spec',
        'chart_image_path',
    ];

    protected function casts(): array
    {
        return [
            'chart_spec' => 'array',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(PlatformDataset::class, 'platform_dataset_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
