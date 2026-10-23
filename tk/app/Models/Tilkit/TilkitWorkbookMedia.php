<?php

namespace App\Models\Tilkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TilkitWorkbookMedia extends Model
{
    protected $fillable = [
        'indicator_record_id',
        'column_letter',
        'storage_path',
        'mime_type',
        'sort_order',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(TilkitIndicatorRecord::class, 'indicator_record_id');
    }
}
