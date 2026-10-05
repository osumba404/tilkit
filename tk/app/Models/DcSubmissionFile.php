<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DcSubmissionFile extends Model
{
    public $timestamps = false;

    protected $table = 'dc_submission_files';

    protected $fillable = [
        'submission_id',
        'answer_id',
        'media_id',
        'kind',
        'exif',
        'sha256',
        'captured_at',
    ];
}
