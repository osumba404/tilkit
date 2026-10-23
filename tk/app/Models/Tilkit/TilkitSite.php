<?php

namespace App\Models\Tilkit;

use App\Models\DcProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TilkitSite extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'ward',
        'county',
        'country',
        'primary_language',
        'latitude',
        'longitude',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(TilkitIndicatorRecord::class, 'site_id');
    }
}
