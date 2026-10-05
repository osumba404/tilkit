<?php

namespace App\Models;

use App\Enums\ToolMemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ToolInvitation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'email',
        'role',
        'token',
        'invited_by',
        'accepted_at',
        'expires_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => ToolMemberRole::class,
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ToolInvitation $invitation): void {
            if (empty($invitation->token)) {
                $invitation->token = (string) Str::uuid();
            }
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(14);
            }
            $invitation->email = Str::lower($invitation->email);
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
