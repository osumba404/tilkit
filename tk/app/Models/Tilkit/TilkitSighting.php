<?php

namespace App\Models\Tilkit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TilkitSighting extends Model
{
    protected $fillable = [
        'uuid',
        'indicator_record_id',
        'sighted_at',
        'place_text',
        'landmark',
        'latitude',
        'longitude',
        'observed_by',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (TilkitSighting $sighting): void {
            if (empty($sighting->uuid)) {
                $sighting->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'sighted_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(TilkitIndicatorRecord::class, 'indicator_record_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
