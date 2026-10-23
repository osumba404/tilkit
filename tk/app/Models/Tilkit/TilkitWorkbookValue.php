<?php

namespace App\Models\Tilkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TilkitWorkbookValue extends Model
{
    protected $fillable = [
        'indicator_record_id',
        'column_letter',
        'field_key',
        'is_derived',
        'value_text',
        'value_number',
        'value_date',
        'value_json',
        'protocol_version',
    ];

    protected function casts(): array
    {
        return [
            'is_derived' => 'boolean',
            'value_number' => 'decimal:6',
            'value_date' => 'date',
            'value_json' => 'array',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(TilkitIndicatorRecord::class, 'indicator_record_id');
    }
}
