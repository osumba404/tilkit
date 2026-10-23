<?php

namespace App\Models\Tilkit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TilkitConsensusForecast extends Model
{
    protected $table = 'tilkit_consensus_forecasts';

    protected $fillable = [
        'site_id',
        'season_id',
        'label',
        'community_tercile',
        'kmsa_tercile',
        'integrated_tercile',
        'advisory_message',
        'tally_snapshot_id',
        'created_by',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(TilkitSite::class, 'site_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(TilkitSeason::class, 'season_id');
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(TilkitTallySnapshot::class, 'tally_snapshot_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
