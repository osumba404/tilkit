<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DcSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'dc_submissions';

    protected $fillable = [
        'client_uuid',
        'supersedes_uuid',
        'form_id',
        'form_version_id',
        'project_id',
        'parent_submission_id',
        'case_id',
        'assignment_id',
        'subject_type',
        'subject_id',
        'collector_id',
        'device_id',
        'sync_batch_id',
        'channel',
        'status',
        'language',
        'started_at',
        'ended_at',
        'submitted_at',
        'received_at',
        'duration_sec',
        'geo_lat',
        'geo_lng',
        'geo_accuracy_m',
        'ip_address',
        'user_agent',
        'app_version',
        'data',
        'data_hash',
        'is_encrypted',
        'retain_until',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'submitted_at' => 'datetime',
            'received_at' => 'datetime',
            'retain_until' => 'date',
            'is_encrypted' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(DcForm::class, 'form_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(DcFormVersion::class, 'form_version_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(DcAnswer::class, 'submission_id');
    }
}
