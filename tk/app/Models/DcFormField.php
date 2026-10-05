<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DcFormField extends Model
{
    public $timestamps = false;

    protected $table = 'dc_form_fields';

    protected $fillable = [
        'form_version_id',
        'parent_id',
        'field_type',
        'name',
        'label',
        'hint',
        'position',
        'choice_list_id',
        'is_required',
        'required_message',
        'relevant_expr',
        'constraint_expr',
        'constraint_msg',
        'calculation_expr',
        'default_value',
        'is_read_only',
        'appearance',
        'parameters',
        'repeat_count_expr',
        'sensitivity',
        'is_identifier',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_read_only' => 'boolean',
            'is_identifier' => 'boolean',
            'parameters' => 'array',
        ];
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(DcFormVersion::class, 'form_version_id');
    }

    public function fieldTypeDefinition(): BelongsTo
    {
        return $this->belongsTo(DcFieldType::class, 'field_type', 'code');
    }
}
