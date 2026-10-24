<?php

namespace App\Models\Tilkit;

use App\Models\DcProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TilkitProgram extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(TilkitSite::class, 'program_id');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(TilkitSeason::class, 'program_id');
    }
}
