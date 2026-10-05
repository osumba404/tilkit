<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DcGeoValue extends Model
{
    public $timestamps = false;

    protected $table = 'dc_geo_values';

    protected $fillable = [
        'answer_id',
        'geom_type',
        'lat',
        'lng',
        'altitude_m',
        'accuracy_m',
        'gnss_meta',
        'geojson',
    ];

    protected function casts(): array
    {
        return [
            'gnss_meta' => 'array',
            'geojson' => 'array',
        ];
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(DcAnswer::class, 'answer_id');
    }
}
