<?php

namespace App\Http\Controllers;

use App\Models\DcForm;
use App\Models\DcFormField;
use App\Services\SubmissionIngestService;
use App\Support\DateTimeFieldValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function show(DcForm $form): View
    {
        abort_unless($form->status === 'published', 404);

        $form->load(['currentVersion', 'project']);
        $liveVersion = $form->liveVersion();
        abort_unless($liveVersion, 404);
        $liveVersion->load(['fields.fieldTypeDefinition']);

        return view('public.form', compact('form', 'liveVersion'));
    }

    public function submit(Request $request, DcForm $form, SubmissionIngestService $ingest): RedirectResponse
    {
        abort_unless($form->status === 'published', 404);

        if (! $form->allow_anonymous && ! $request->user()) {
            return redirect()->route('login')->with('status', 'Sign in to submit this form.');
        }

        $request->attributes->set('submission_channel', 'web');

        $version = $form->liveVersion();
        abort_unless($version, 404);
        $version->load('fields');
        $rules = $this->rulesForFields($version->fields);
        $validated = $request->validate($rules);
        $validated = $this->normalizeDateTimeAnswers($version->fields, $validated);

        $ingest->submit($form, $validated, $request, $request->user());

        return redirect()
            ->route('public.forms.thanks', $form)
            ->with('status', 'Thank you - your response was recorded.');
    }

    public function thanks(DcForm $form): View
    {
        return view('public.thanks', compact('form'));
    }

    /** @param \Illuminate\Support\Collection<int, DcFormField> $fields */
    private function rulesForFields($fields): array
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

                case 'note':
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
     * @param  \Illuminate\Support\Collection<int, DcFormField>  $fields
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeDateTimeAnswers($fields, array $validated): array
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

        $rules = array_filter([
            $required,
            'file',
            'max:'.($maxMb * 1024),
            $extensions ? 'mimes:'.implode(',', $extensions) : null,
        ]);

        return $rules;
    }
}
