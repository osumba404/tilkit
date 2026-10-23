<?php

namespace App\Models\Tilkit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TilkitTallySnapshot extends Model
{
    protected $fillable = [
        'site_id',
        'season_id',
        'protocol_version',
        'use_prior_multiplier',
        'settings_payload',
        'results',
        'reconciliation',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'use_prior_multiplier' => 'boolean',
            'settings_payload' => 'array',
            'results' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(TilkitSite::class, 'site_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(TilkitSeason::class, 'season_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
