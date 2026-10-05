<?php

namespace App\Services;

use App\Models\DcAnswer;
use App\Models\DcForm;
use App\Models\DcFormField;
use App\Models\DcSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmissionIngestService
{
    public function submit(DcForm $form, array $answers, Request $request, ?User $collector = null): DcSubmission
    {
        $version = $form->currentVersion()->with('fields')->firstOrFail();

        if ($version->status !== 'published') {
            abort(422, 'This form is not published yet.');
        }

        return DB::transaction(function () use ($form, $version, $answers, $request, $collector): DcSubmission {
            $normalized = $this->normalizeAnswers($answers, $version->fields);
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
                'data' => $normalized,
                'data_hash' => hash('sha256', json_encode($normalized)),
                'created_by' => $collector?->id,
            ]);

            foreach ($version->fields as $field) {
                if (! array_key_exists($field->name, $normalized)) {
                    continue;
                }
                $this->storeTypedAnswer($submission, $field, $normalized[$field->name]);
            }

            return $submission->fresh(['answers', 'form']);
        });
    }

    private function normalizeAnswers(array $input, $fields): array
    {
        $allowed = $fields->pluck('name')->all();
        $out = [];
        foreach ($allowed as $name) {
            if (array_key_exists($name, $input)) {
                $out[$name] = $input[$name];
            }
        }

        return $out;
    }

    private function storeTypedAnswer(DcSubmission $submission, DcFormField $field, mixed $value): void
    {
        $answer = [
            'submission_id' => $submission->id,
            'field_id' => $field->id,
            'is_missing' => $value === null || $value === '',
        ];

        $type = $field->field_type;
        if (in_array($type, ['integer', 'decimal', 'range'], true) && is_numeric($value)) {
            $answer['value_number'] = $value;
        } elseif (in_array($type, ['select_one', 'text', 'note'], true)) {
            $answer['value_text'] = is_scalar($value) ? (string) $value : json_encode($value);
        } elseif ($type === 'select_multiple' && is_array($value)) {
            $answer['value_json'] = $value;
        } elseif (in_array($type, ['date', 'datetime'], true)) {
            $answer['value_date'] = $value;
        } elseif ($type === 'geopoint' && is_array($value)) {
            $answer['value_json'] = $value;
        } else {
            $answer['value_text'] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        DcAnswer::create($answer);
    }
}
