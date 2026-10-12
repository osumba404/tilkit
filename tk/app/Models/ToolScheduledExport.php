<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolScheduledExport extends Model
{
    protected $fillable = [
        'project_id',
        'recipient_emails',
        'frequency',
        'last_run_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_run_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }
}
