<?php

namespace App\Services;

use App\Models\DcAnswer;
use App\Models\DcForm;
use App\Models\DcFormField;
use App\Models\DcSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmissionIngestService
{
    public function __construct(
        private SubmissionMediaService $mediaService,
    ) {}

    public function submit(DcForm $form, array $answers, Request $request, ?User $collector = null): DcSubmission
    {
        $version = $form->currentVersion()->with('fields')->firstOrFail();

        if ($version->status !== 'published') {
            abort(422, 'This form is not published yet.');
        }

        return DB::transaction(function () use ($form, $version, $answers, $request, $collector): DcSubmission {
            $clientUuid = (string) ($answers['_client_uuid'] ?? Str::uuid());

            $submission = DcSubmission::create([
                'client_uuid' => $clientUuid,
                'form_id' => $form->id,
                'form_version_id' => $version->id,
                'project_id' => $form->project_id,
                'collector_id' => $collector?->id,
                'channel' => $request->attributes->get('submission_channel', 'web'),
                'status' => 'submitted',
                'submitted_at' => now(),
                'received_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
                'data' => [],
                'data_hash' => '',
                'created_by' => $collector?->id,
            ]);

            $normalized = $this->buildNormalizedPayload($answers, $version->fields, $request, $submission, $collector);

            $submission->update([
                'data' => $normalized,
                'data_hash' => hash('sha256', json_encode($normalized)),
            ]);

            foreach ($version->fields as $field) {
                if ($field->field_type === 'note') {
                    continue;
                }
                if (! array_key_exists($field->name, $normalized)) {
                    continue;
                }
                $this->storeTypedAnswer($submission, $field, $normalized[$field->name]);
            }

            return $submission->fresh(['answers', 'form']);
        });
    }

    /** @param \Illuminate\Support\Collection<int, DcFormField> $fields */
    private function buildNormalizedPayload(array $input, $fields, Request $request, DcSubmission $submission, ?User $collector): array
    {
        $out = [];

        foreach ($fields as $field) {
            if ($field->field_type === 'note') {
                continue;
            }

            if ($request->hasFile($field->name)) {
                $file = $request->file($field->name);
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $media = $this->mediaService->storeFieldUpload($submission, $field, $file, $collector?->id);
                    $out[$field->name] = $this->mediaService->publicPathSummary($media);
                }

                continue;
            }

            if (array_key_exists($field->name, $input)) {
                $value = $input[$field->name];
                if ($field->field_type === 'select_multiple' && is_array($value)) {
                    $value = array_values($value);
                }
                $out[$field->name] = $value;
            }
        }

        return $out;
    }

    private function storeTypedAnswer(DcSubmission $submission, DcFormField $field, mixed $value): void
    {
        $answer = [
            'submission_id' => $submission->id,
            'field_id' => $field->id,
            'is_missing' => $value === null || $value === '' || $value === [],
        ];

        $type = $field->field_type;
        if (in_array($type, ['integer', 'decimal', 'range'], true) && is_numeric($value)) {
            $answer['value_number'] = $value;
        } elseif (in_array($type, ['select_one', 'text'], true)) {
            $answer['value_text'] = is_scalar($value) ? (string) $value : json_encode($value);
        } elseif ($type === 'select_multiple' && is_array($value)) {
            $answer['value_json'] = $value;
        } elseif (in_array($type, ['date', 'datetime', 'time'], true)) {
            $answer['value_text'] = (string) $value;
        } elseif (in_array($type, ['image', 'audio', 'video', 'file', 'signature'], true) && is_array($value)) {
            $answer['value_json'] = $value;
        } elseif ($type === 'geopoint' && is_array($value)) {
            $answer['value_json'] = $value;
        } else {
            $answer['value_text'] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        DcAnswer::create($answer);
    }
}
