<?php

namespace App\Models\Tilkit;

use App\Models\DcProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TilkitIndicator extends Model
{
    protected $fillable = [
        'indicator_uid',
        'indicator_code_id',
        'project_id',
        'local_name',
        'local_description',
        'local_prediction',
        'locality',
        'ethnic_community',
        'language',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(DcProject::class, 'project_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(TilkitIndicatorCode::class, 'indicator_code_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(TilkitIndicatorRecord::class, 'indicator_id');
    }
}
