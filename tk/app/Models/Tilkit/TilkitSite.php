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
        'program_id',
        'name',
        'continent',
        'ward',
        'county',
        'country',
        'country_name',
        'primary_language',
        'latitude',
        'longitude',
        'study_area_geojson',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'study_area_geojson' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TilkitProgram::class, 'program_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(TilkitIndicatorRecord::class, 'site_id');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(TilkitSeason::class, 'site_id');
    }

    public function monthIndicatorCodes(): HasMany
    {
        return $this->hasMany(TilkitSiteMonthIndicatorCode::class, 'site_id');
    }

    public function hasStudyAreaMapped(): bool
    {
        if ($this->study_area_geojson !== null && $this->study_area_geojson !== []) {
            return true;
        }

        return $this->latitude !== null && $this->longitude !== null;
    }
}
