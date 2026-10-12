<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionDraft extends Model
{
    protected $fillable = [
        'project_id',
        'form_id',
        'user_id',
        'device_token',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }
}
