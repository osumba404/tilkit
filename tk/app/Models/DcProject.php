<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DcProject extends Model
{
    use SoftDeletes;

    protected $table = 'dc_projects';

    protected $fillable = [
        'code',
        'name',
        'module',
        'purpose',
        'lawful_basis',
        'retention_months',
        'starts_on',
        'ends_on',
        'status',
        'owner_user_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ToolMember::class, 'project_id');
    }

    public function forms(): HasMany
    {
        return $this->hasMany(DcForm::class, 'project_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(DcSubmission::class, 'project_id');
    }
}
