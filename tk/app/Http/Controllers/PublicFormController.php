<?php

namespace App\Http\Controllers;

use App\Models\DcForm;
use App\Models\DcFormField;
use App\Services\SubmissionIngestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function show(DcForm $form): View
    {
        abort_unless($form->status === 'published', 404);

        $form->load(['currentVersion.fields.fieldTypeDefinition', 'project']);

        return view('public.form', compact('form'));
    }

    public function submit(Request $request, DcForm $form, SubmissionIngestService $ingest): RedirectResponse
    {
        abort_unless($form->status === 'published', 404);

        if (! $form->allow_anonymous && ! $request->user()) {
            return redirect()->route('login')->with('status', 'Sign in to submit this form.');
        }

        $request->attributes->set('submission_channel', 'web');

        $version = $form->currentVersion()->with('fields')->firstOrFail();
        $rules = $this->rulesForFields($version->fields);
        $validated = $request->validate($rules);

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
                case 'rank':
                    $rules[$name] = [$required, 'string'];
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
                case 'signature':
                    $rules[$name] = $this->fileRules($field, $required, []);
                    break;

                case 'note':
                    break;

                default:
                    $rules[$name] = [$required, 'string'];
            }
        }

        return $rules;
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
