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
        'site_id',
        'label',
        'start_month',
        'end_month',
        'starts_on',
        'ends_on',
        'protocol_version',
    ];

    protected function casts(): array
    {
        return [
            'start_month' => 'integer',
            'end_month' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function monthRangeLabel(): string
    {
        $name = trim((string) ($this->label ?? ''));
        if ($name !== '') {
            return $name;
        }

        if ($this->start_month && $this->end_month) {
            return \App\Support\Tilkit\SeasonMonthRange::labelFromMonths(
                (int) $this->start_month,
                (int) $this->end_month,
            );
        }

        return 'Season';
    }

    public function calendarMonthRangeLabel(): string
    {
        if ($this->start_month && $this->end_month) {
            return \App\Support\Tilkit\SeasonMonthRange::labelFromMonths(
                (int) $this->start_month,
                (int) $this->end_month,
            );
        }

        return '';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TilkitProgram::class, 'program_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(TilkitSite::class, 'site_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(TilkitIndicatorRecord::class, 'season_id');
    }
}
