<?php

namespace App\Models\Tilkit;

use App\Models\DcProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TilkitSeason extends Model
{
    protected $fillable = [
        'project_id',
        'program_id',
        'label',
        'starts_on',
        'ends_on',
        'protocol_version',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
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
        return $this->hasMany(TilkitIndicatorRecord::class, 'season_id');
    }
}
