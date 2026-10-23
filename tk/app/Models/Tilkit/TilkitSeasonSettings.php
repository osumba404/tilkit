<?php

namespace App\Models\Tilkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TilkitSeasonSettings extends Model
{
    protected $table = 'tilkit_season_settings';

    protected $fillable = [
        'site_id',
        'season_id',
        'use_prior_multiplier',
        'settings',
        'protocol_version',
        'kmsa_outlook_tercile',
        'ndma_drought_phase',
        'ndma_vci3m',
    ];

    protected function casts(): array
    {
        return [
            'use_prior_multiplier' => 'boolean',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TilkitSeasonSettings $row): void {
            if (empty($row->protocol_version)) {
                $row->protocol_version = config('tilkit.weather.protocol_version');
            }
            if ($row->settings === null) {
                $row->settings = self::defaultSettings();
            }
        });
    }

    /** @return array<string, mixed> */
    public static function defaultSettings(): array
    {
        return [
            'hazard_priority' => [
                'drought' => 1.5,
                'flood' => 1.5,
                'erratic' => 1.25,
                'pest' => 1.25,
                'default' => 1.0,
            ],
            'trigger_thresholds' => [
                'above_normal_weighted_share' => 50,
                'confidence_floor' => 50,
            ],
            'confidence_weights' => [
                'track_record' => 25,
                'corroboration' => 15,
                'validation_breadth' => 15,
                'lead_time' => 10,
                'stability' => 10,
                'kmsa_agreement' => 10,
                'observers' => 5,
                'verified' => 10,
            ],
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
}
