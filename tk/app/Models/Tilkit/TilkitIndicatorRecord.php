<?php

namespace App\Models\Tilkit;

use App\Enums\Tilkit\ReliabilityRating;
use App\Enums\Tilkit\SeasonOutcome;
use App\Enums\Tilkit\TercileForecast;
use App\Enums\Tilkit\ValidationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TilkitIndicatorRecord extends Model
{
    protected $fillable = [
        'uuid',
        'indicator_id',
        'site_id',
        'season_id',
        'protocol_version',
        'collection_date',
        'collection_year',
        'sighted',
        'confirmed',
        'approved',
        'verified',
        'validation_status',
        'tercile_forecast',
        'current_reliability',
        'decision_relevance',
        'onset_earliest',
        'onset_latest',
        'cessation_earliest',
        'cessation_latest',
        'distribution_forecast',
        'actual_onset',
        'actual_cessation',
        'actual_distribution',
        'actual_tercile',
        'season_outcome',
        'do_no_harm',
        'consent_level',
        'consent_credit',
        'demographics',
        'extended_attributes',
        'entered_by',
        'confirmed_by_user_id',
        'approved_by_user_id',
        'verified_by_user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (TilkitIndicatorRecord $record): void {
            if (empty($record->uuid)) {
                $record->uuid = (string) Str::uuid();
            }
            if (empty($record->protocol_version)) {
                $record->protocol_version = config('tilkit.weather.protocol_version');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'sighted' => 'boolean',
            'confirmed' => 'boolean',
            'approved' => 'boolean',
            'verified' => 'boolean',
            'validation_status' => ValidationStatus::class,
            'tercile_forecast' => TercileForecast::class,
            'current_reliability' => ReliabilityRating::class,
            'decision_relevance' => ReliabilityRating::class,
            'season_outcome' => SeasonOutcome::class,
            'collection_date' => 'date',
            'collection_year' => 'integer',
            'onset_earliest' => 'date',
            'onset_latest' => 'date',
            'cessation_earliest' => 'date',
            'cessation_latest' => 'date',
            'actual_onset' => 'date',
            'actual_cessation' => 'date',
            'do_no_harm' => 'boolean',
            'demographics' => 'array',
            'extended_attributes' => 'array',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(TilkitIndicator::class, 'indicator_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(TilkitSite::class, 'site_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(TilkitSeason::class, 'season_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function sightings(): HasMany
    {
        return $this->hasMany(TilkitSighting::class, 'indicator_record_id');
    }

    public function workbookValues(): HasMany
    {
        return $this->hasMany(TilkitWorkbookValue::class, 'indicator_record_id');
    }

    public function workbookMedia(): HasMany
    {
        return $this->hasMany(TilkitWorkbookMedia::class, 'indicator_record_id');
    }

    public function weightReviews(): HasMany
    {
        return $this->hasMany(TilkitWeightReview::class, 'indicator_record_id');
    }
}
