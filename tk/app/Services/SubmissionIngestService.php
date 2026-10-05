<?php

namespace App\Services;

use App\Models\DcAnswer;
use App\Models\DcForm;
use App\Models\DcFormField;
use App\Models\DcGeoValue;
use App\Models\DcSubmission;
use App\Models\User;
use App\Support\DateTimeFieldValidator;
use App\Support\GeoFieldValidator;
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
        $version = $form->liveVersion();
        abort_unless($version, 422, 'This form is not published yet.');
        $version->load('fields');

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
                if (in_array($field->field_type, ['note', 'group', 'repeat'], true)) {
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
            if (in_array($field->field_type, ['note', 'group', 'repeat'], true)) {
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

            $drawnKey = $field->name.'_drawn';
            if ($field->field_type === 'signature' && ! empty($input[$drawnKey]) && is_string($input[$drawnKey])) {
                $media = $this->mediaService->storeFromDataUrl(
                    $submission,
                    $field,
                    $input[$drawnKey],
                    $collector?->id,
                );
                $out[$field->name] = $this->mediaService->publicPathSummary($media);

                continue;
            }

            if (array_key_exists($field->name, $input)) {
                $value = $input[$field->name];
                if (in_array($field->field_type, ['geopoint', 'geotrace', 'geoshape'], true)) {
                    $value = GeoFieldValidator::parseAndValidate($field->field_type, $value, $field->name);
                } elseif (in_array($field->field_type, ['date', 'time', 'datetime'], true) && is_string($value)) {
                    $value = DateTimeFieldValidator::parseAndValidate($field->field_type, $value, $field->name);
                } elseif ($field->field_type === 'select_multiple' && is_array($value)) {
                    $value = array_values($value);
                } elseif ($field->field_type === 'rank' && is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? array_values($decoded) : [];
                } elseif ($field->field_type === 'metadata' && is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? $decoded : [];
                } elseif ($field->field_type === 'acknowledge') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) || $value === '1' || $value === 'on';
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
        } elseif (in_array($type, ['select_one', 'text', 'barcode'], true)) {
            $answer['value_text'] = is_scalar($value) ? (string) $value : json_encode($value);
        } elseif (in_array($type, ['select_multiple', 'rank'], true) && is_array($value)) {
            $answer['value_json'] = $value;
        } elseif ($type === 'acknowledge') {
            $answer['value_text'] = $value ? '1' : '0';
        } elseif ($type === 'calculate') {
            $answer['value_text'] = is_scalar($value) ? (string) $value : '';
        } elseif ($type === 'metadata' && is_array($value)) {
            $answer['value_json'] = $value;
        } elseif ($type === 'date') {
            $answer['value_date'] = (string) $value;
            $answer['value_text'] = (string) $value;
        } elseif ($type === 'datetime') {
            $answer['value_datetime'] = (string) $value;
            $answer['value_text'] = (string) $value;
        } elseif ($type === 'time') {
            $answer['value_text'] = (string) $value;
        } elseif (in_array($type, ['image', 'audio', 'video', 'file', 'signature'], true) && is_array($value)) {
            $answer['value_json'] = $value;
        } elseif (in_array($type, ['geopoint', 'geotrace', 'geoshape'], true) && is_array($value)) {
            $answer['value_json'] = $value;
        } else {
            $answer['value_text'] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        $record = DcAnswer::create($answer);

        if (in_array($type, ['geopoint', 'geotrace', 'geoshape'], true) && is_array($value)) {
            $this->storeGeoValue($record, $type, $value);
        }
    }

    /** @param array<string, mixed> $geojson */
    private function storeGeoValue(DcAnswer $answer, string $fieldType, array $geojson): void
    {
        $geomType = match ($fieldType) {
            'geopoint' => 'point',
            'geotrace' => 'line',
            'geoshape' => 'polygon',
        };

        $rep = GeoFieldValidator::representativeLatLng($geojson);
        $props = $geojson['properties'] ?? [];

        DcGeoValue::create([
            'answer_id' => $answer->id,
            'geom_type' => $geomType,
            'lat' => $rep['lat'],
            'lng' => $rep['lng'],
            'altitude_m' => $props['altitude'] ?? null,
            'accuracy_m' => $props['accuracy'] ?? null,
            'gnss_meta' => $props['gnss_meta'] ?? null,
            'geojson' => $geojson,
        ]);
    }
}
