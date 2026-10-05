<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DcAnswer extends Model
{
    public $timestamps = false;

    protected $table = 'dc_answers';

    protected $fillable = [
        'submission_id',
        'field_id',
        'repeat_id',
        'value_text',
        'value_number',
        'value_bool',
        'value_date',
        'value_datetime',
        'value_json',
        'is_missing',
        'missing_code',
    ];

    protected function casts(): array
    {
        return [
            'value_bool' => 'boolean',
            'value_date' => 'date',
            'value_datetime' => 'datetime',
            'value_json' => 'array',
            'is_missing' => 'boolean',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(DcFormField::class, 'field_id');
    }
}
