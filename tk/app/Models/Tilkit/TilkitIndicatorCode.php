<?php

namespace App\Models\Tilkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TilkitIndicatorCode extends Model
{
    protected $fillable = [
        'code',
        'category',
        'type',
        'sub_type',
        'label',
        'usage_notes',
        'prior_multiplier',
        'preferred_code',
        'deprecated_at',
        'protocol_version',
    ];

    protected function casts(): array
    {
        return [
            'prior_multiplier' => 'decimal:3',
            'deprecated_at' => 'datetime',
        ];
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(TilkitIndicator::class, 'indicator_code_id');
    }
}
