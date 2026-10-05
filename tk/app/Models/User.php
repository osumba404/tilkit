<?php

namespace App\Models;

use App\Enums\PlatformRole;
use App\Enums\ToolMemberRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'user_type',
        'platform_role',
        'person_type',
        'person_id',
        'name',
        'username',
        'email',
        'phone',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'status',
        'mfa_enabled',
        'must_change_password',
        'password_changed_at',
        'failed_login_count',
        'locked_until',
        'last_login_at',
        'last_login_ip',
        'locale',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'mfa_enabled' => 'boolean',
            'must_change_password' => 'boolean',
            'platform_role' => PlatformRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
            if (empty($user->status)) {
                $user->status = 'active';
            }
            if (empty($user->user_type)) {
                $user->user_type = 'staff';
            }
            if (empty($user->platform_role)) {
                $user->platform_role = PlatformRole::Member;
            }
        });
    }

    public function isSuperAdmin(): bool
    {
        return $this->platform_role === PlatformRole::SuperAdmin;
    }

    public function toolMemberships(): HasMany
    {
        return $this->hasMany(ToolMember::class);
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(DcProject::class, 'owner_user_id');
    }

    public function toolRoleFor(DcProject $project): ?ToolMemberRole
    {
        if ($this->isSuperAdmin()) {
            return ToolMemberRole::Admin;
        }

        $membership = $this->toolMemberships()
            ->where('project_id', $project->id)
            ->first();

        return $membership?->role;
    }

    public function canAccessTool(DcProject $project): bool
    {
        return $this->isSuperAdmin() || $this->toolRoleFor($project) !== null;
    }
}
