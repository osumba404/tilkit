<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DcFormVersion extends Model
{
    public $timestamps = false;

    protected $table = 'dc_form_versions';

    protected $fillable = [
        'form_id',
        'version_no',
        'status',
        'compiled_json',
        'source_media_id',
        'checksum',
        'change_note',
        'published_at',
        'published_by',
        'created_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'compiled_json' => 'array',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(DcForm::class, 'form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DcFormField::class, 'form_version_id')->orderBy('position');
    }
}
