<?php

namespace App\Models;

use App\Enums\DataSubjectRequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSubjectRequest extends Model
{
    protected $fillable = [
        'project_id',
        'request_type',
        'requester_name',
        'requester_email',
        'requester_phone',
        'record_reference',
        'details',
        'status',
        'resolution_notes',
        'handled_by',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => DataSubjectRequestType::class,
            'handled_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'in_progress'], true);
    }
}
