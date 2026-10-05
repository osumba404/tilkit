<?php

namespace App\Services;

use App\Models\DcFormField;
use App\Support\DateTimeFieldValidator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PublicFormValidationService
{
    /** @param Collection<int, DcFormField> $fields */
    public function rulesForFields(Collection $fields): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $params = $field->parameters ?? [];
            $name = $field->name;
            $required = $field->is_required ? 'required' : 'nullable';

            switch ($field->field_type) {
                case 'select_multiple':
                    $rules[$name] = array_filter([$required, 'array']);
                    if ($field->is_required) {
                        $rules[$name][] = 'min:1';
                    }
                    if (! empty($params['max_selections'])) {
                        $rules[$name][] = 'max:'.(int) $params['max_selections'];
                    }
                    $rules[$name.'.*'] = ['string'];
                    break;

                case 'select_one':
                case 'text':
                case 'barcode':
                    $rules[$name] = [$required, 'string', 'max:2000'];
                    break;

                case 'rank':
                    $rules[$name] = array_filter([$required, 'string', 'json']);
                    break;

                case 'date':
                case 'time':
                case 'datetime':
                    $rules[$name] = array_filter([$required, 'string', 'max:32']);
                    break;

                case 'acknowledge':
                    $rules[$name] = $field->is_required ? ['accepted'] : ['nullable'];
                    break;

                case 'calculate':
                case 'metadata':
                    $rules[$name] = ['nullable', 'string'];
                    break;

                case 'group':
                case 'repeat':
                case 'note':
                    break;

                case 'integer':
                    $rules[$name] = array_filter([
                        $required,
                        'integer',
                        isset($params['min']) ? 'min:'.$params['min'] : null,
                        isset($params['max']) ? 'max:'.$params['max'] : null,
                    ]);
                    break;

                case 'decimal':
                case 'range':
                    $rules[$name] = array_filter([
                        $required,
                        'numeric',
                        isset($params['min']) ? 'min:'.$params['min'] : null,
                        isset($params['max']) ? 'max:'.$params['max'] : null,
                    ]);
                    break;

                case 'image':
                    $rules[$name] = $this->fileRules($field, $required, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic']);
                    break;

                case 'audio':
                    $rules[$name] = $this->fileRules($field, $required, ['mp3', 'wav', 'ogg', 'm4a', 'webm']);
                    break;

                case 'video':
                    $rules[$name] = $this->fileRules($field, $required, ['mp4', 'mov', 'webm', 'mkv']);
                    break;

                case 'file':
                    $rules[$name] = $this->fileRules($field, $required, []);
                    break;

                case 'signature':
                    $rules[$name] = array_filter([
                        $field->is_required ? 'required_without:'.$name.'_drawn' : 'nullable',
                        'file',
                        'max:'.((int) ($params['max_size_mb'] ?? 25) * 1024),
                        'mimes:jpg,jpeg,png,webp,pdf',
                    ]);
                    $rules[$name.'_drawn'] = array_filter([
                        $field->is_required ? 'required_without:'.$name : 'nullable',
                        'string',
                        'regex:/^data:image\/(png|jpe?g|webp);base64,/',
                    ]);
                    break;

                case 'geopoint':
                case 'geotrace':
                case 'geoshape':
                    $rules[$name] = array_filter([$required, 'string']);
                    break;

                default:
                    $rules[$name] = [$required, 'string'];
            }
        }

        return $rules;
    }

    /**
     * @param Collection<int, DcFormField> $fields
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function normalizeDateTimeAnswers(Collection $fields, array $validated): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (! in_array($field->field_type, ['date', 'time', 'datetime'], true)) {
                continue;
            }

            $name = $field->name;
            if (! array_key_exists($name, $validated)) {
                continue;
            }

            $raw = $validated[$name];
            if ($raw === null || $raw === '') {
                continue;
            }

            try {
                $validated[$name] = DateTimeFieldValidator::parseAndValidate(
                    $field->field_type,
                    $raw,
                    $name,
                );
            } catch (ValidationException $e) {
                foreach ($e->errors() as $key => $messages) {
                    $errors[$key] = $messages;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    /** @param list<string> $extensions */
    private function fileRules(DcFormField $field, string $required, array $extensions): array
    {
        $params = $field->parameters ?? [];
        $maxMb = (int) ($params['max_size_mb'] ?? 25);

        return array_filter([
            $required,
            'file',
            'max:'.($maxMb * 1024),
            $extensions ? 'mimes:'.implode(',', $extensions) : null,
        ]);
    }
}
