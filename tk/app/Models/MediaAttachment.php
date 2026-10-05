<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MediaAttachment extends Model
{
    protected $table = 'media_attachments';

    protected $fillable = [
        'uuid',
        'attachable_type',
        'attachable_id',
        'collection',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'sha256',
        'uploaded_by',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (MediaAttachment $media): void {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }
}
