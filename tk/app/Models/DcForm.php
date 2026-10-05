<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DcForm extends Model
{
    use SoftDeletes;

    protected $table = 'dc_forms';

    protected $fillable = [
        'uuid',
        'project_id',
        'code',
        'title',
        'description',
        'record_kind',
        'subject_type',
        'allow_anonymous',
        'allow_offline',
        'is_encrypted',
        'current_version_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'allow_anonymous' => 'boolean',
            'allow_offline' => 'boolean',
            'is_encrypted' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DcFormVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DcFormVersion::class, 'form_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(DcSubmission::class, 'form_id');
    }

    /** Version collectors see on the public web form (latest published). */
    public function liveVersion(): ?DcFormVersion
    {
        $current = $this->currentVersion;
        if ($current?->status === 'published') {
            return $current;
        }

        return $this->versions()
            ->where('status', 'published')
            ->orderByDesc('version_no')
            ->first();
    }

    public function hasUnpublishedDraft(): bool
    {
        $current = $this->currentVersion;

        return $current !== null && $current->status === 'draft' && $this->liveVersion() !== null;
    }
}
