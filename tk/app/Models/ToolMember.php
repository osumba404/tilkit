<?php

namespace App\Models;

use App\Enums\ToolMemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolMember extends Model
{
    public $timestamps = false;

    const UPDATED_AT = null;

    const CREATED_AT = null;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'tilkit_persona',
        'can_export',
        'can_publish_forms',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => ToolMemberRole::class,
            'can_export' => 'boolean',
            'can_publish_forms' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
