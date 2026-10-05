<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DcFieldType extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'code';

    protected $table = 'dc_field_types';

    protected $fillable = [
        'code',
        'family',
        'catalogue_id',
        'value_kind',
        'is_input',
        'is_container',
    ];

    protected function casts(): array
    {
        return [
            'is_input' => 'boolean',
            'is_container' => 'boolean',
        ];
    }
}
