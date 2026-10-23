<?php

namespace App\Models\Tilkit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TilkitWeightReview extends Model
{
    protected $table = 'tilkit_weight_reviews';

    protected $fillable = [
        'indicator_record_id',
        'status',
        'climate_flag',
        'notes',
        'assigned_lead_itk_user_id',
        'assigned_technical_user_id',
        'signed_off_by',
        'signed_off_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_off_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(TilkitIndicatorRecord::class, 'indicator_record_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
