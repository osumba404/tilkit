<?php

namespace App\Models\Tilkit;

use App\Models\DcProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TilkitSiteMonthIndicatorCode extends Model
{
    protected $fillable = [
        'project_id',
        'program_id',
        'site_id',
        'calendar_month',
        'indicator_code_id',
    ];

    protected function casts(): array
    {
        return [
            'calendar_month' => 'integer',
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

    public function site(): BelongsTo
    {
        return $this->belongsTo(TilkitSite::class, 'site_id');
    }

    public function indicatorCode(): BelongsTo
    {
        return $this->belongsTo(TilkitIndicatorCode::class, 'indicator_code_id');
    }
}
