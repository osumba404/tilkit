<?php

namespace App\Services;

use App\Models\DcFormField;
use App\Models\DcSubmission;
use App\Models\DcSubmissionFile;
use App\Models\MediaAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmissionMediaService
{
    public function storeFieldUpload(DcSubmission $submission, DcFormField $field, UploadedFile $file, ?int $userId = null): MediaAttachment
    {
        $disk = 'local';
        $directory = 'submissions/'.$submission->id;
        $stored = $file->store($directory, $disk);
        $hash = hash_file('sha256', $file->getRealPath());

        $media = MediaAttachment::create([
            'attachable_type' => 'dc_submissions',
            'attachable_id' => $submission->id,
            'collection' => $field->name,
            'disk' => $disk,
            'path' => $stored,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'extension' => $file->getClientOriginalExtension(),
            'size_bytes' => $file->getSize(),
            'sha256' => $hash,
            'uploaded_by' => $userId,
            'created_by' => $userId,
            'created_at' => now(),
        ]);

        DcSubmissionFile::create([
            'submission_id' => $submission->id,
            'media_id' => $media->id,
            'kind' => $this->kindForField($field->field_type),
            'sha256' => $hash,
            'captured_at' => now(),
        ]);

        return $media;
    }

    public function storeFromDataUrl(DcSubmission $submission, DcFormField $field, string $dataUrl, ?int $userId = null): MediaAttachment
    {
        if (! preg_match('#^data:image/(png|jpe?g|webp);base64,#i', $dataUrl)) {
            throw ValidationException::withMessages([
                $field->name => 'Invalid signature image data.',
            ]);
        }

        $comma = strpos($dataUrl, ',');
        $binary = base64_decode(substr($dataUrl, $comma + 1), true);
        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages([
                $field->name => 'Could not read signature image.',
            ]);
        }

        $mime = 'image/png';
        $extension = 'png';
        if (preg_match('#^data:image/(jpe?g);base64,#i', $dataUrl)) {
            $mime = 'image/jpeg';
            $extension = 'jpg';
        } elseif (preg_match('#^data:image/webp;base64,#i', $dataUrl)) {
            $mime = 'image/webp';
            $extension = 'webp';
        }

        $disk = 'local';
        $directory = 'submissions/'.$submission->id;
        $filename = Str::uuid().'.'.$extension;
        $stored = $directory.'/'.$filename;
        Storage::disk($disk)->put($stored, $binary);
        $hash = hash('sha256', $binary);

        $media = MediaAttachment::create([
            'attachable_type' => 'dc_submissions',
            'attachable_id' => $submission->id,
            'collection' => $field->name,
            'disk' => $disk,
            'path' => $stored,
            'original_name' => $field->name.'_signature.'.$extension,
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => strlen($binary),
            'sha256' => $hash,
            'uploaded_by' => $userId,
            'created_by' => $userId,
            'created_at' => now(),
        ]);

        DcSubmissionFile::create([
            'submission_id' => $submission->id,
            'media_id' => $media->id,
            'kind' => 'signature',
            'sha256' => $hash,
            'captured_at' => now(),
        ]);

        return $media;
    }

    private function kindForField(string $type): string
    {
        return match ($type) {
            'image' => 'photo',
            'audio' => 'audio',
            'video' => 'video',
            'signature' => 'signature',
            default => 'document',
        };
    }

    public function publicPathSummary(MediaAttachment $media): array
    {
        return [
            'uuid' => $media->uuid,
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
            'size_bytes' => $media->size_bytes,
        ];
    }
}
