<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PlatformDataset extends Model
{
    protected $fillable = [
        'uuid',
        'uploaded_by',
        'name',
        'original_filename',
        'storage_path',
        'workspace_path',
        'row_count',
        'column_count',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (PlatformDataset $dataset): void {
            if (empty($dataset->uuid)) {
                $dataset->uuid = (string) Str::uuid();
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(PlatformDatasetColumn::class)->orderBy('position');
    }

    public function analysisRuns(): HasMany
    {
        return $this->hasMany(PlatformAnalysisRun::class)->latest();
    }

    public function workspaceAbsolutePath(): string
    {
        return storage_path('app/'.$this->workspace_path);
    }

    public function dataCsvPath(): string
    {
        return $this->workspaceAbsolutePath().DIRECTORY_SEPARATOR.'data.csv';
    }
}
