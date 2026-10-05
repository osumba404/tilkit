<?php

namespace App\Http\Controllers;

use App\Models\DcForm;
use App\Models\DcProject;
use App\Support\FieldTypeHelper;
use App\Services\FormBuilderService;
use App\Services\ToolAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ToolFormController extends Controller
{
    public function create(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $access->assertCanManage($request->user(), $tool);

        return view('tools.forms.create', compact('tool'));
    }

    public function store(Request $request, DcProject $tool, ToolAccessService $access, FormBuilderService $builder): RedirectResponse
    {
        $access->assertCanManage($request->user(), $tool);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:60'],
            'allow_anonymous' => ['sometimes', 'boolean'],
        ]);

        $form = $builder->createForm($tool, $request->user(), $validated);

        return redirect()->route('tools.forms.builder', [$tool, $form]);
    }

    public function builder(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access): View
    {
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $form->load(['currentVersion.fields.fieldTypeDefinition']);
        $fieldTypes = \App\Models\DcFieldType::query()->orderBy('family')->orderBy('code')->get();

        return view('tools.forms.builder', compact('tool', 'form', 'fieldTypes'));
    }

    public function saveBuilder(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access, FormBuilderService $builder): RedirectResponse
    {
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $validated = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9_]*$/'],
            'fields.*.field_type' => ['required', 'exists:dc_field_types,code'],
            'fields.*.label' => ['nullable', 'string'],
            'fields.*.hint' => ['nullable', 'string'],
            'fields.*.is_required' => ['sometimes', 'boolean'],
            'fields.*.parameters' => ['nullable', 'array'],
            'fields.*.parameters.choices' => ['nullable', 'array'],
            'fields.*.parameters.choices.*.value' => ['required_with:fields.*.parameters.choices', 'string', 'max:100'],
            'fields.*.parameters.choices.*.label' => ['required_with:fields.*.parameters.choices', 'string', 'max:255'],
            'fields.*.parameters.max_selections' => ['nullable', 'integer', 'min:1', 'max:50'],
            'fields.*.parameters.max_size_mb' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        foreach ($validated['fields'] as $i => &$field) {
            $field['position'] = $i;
            if (in_array($field['field_type'], ['image', 'audio', 'video', 'file'], true)) {
                $field['parameters'] = $field['parameters'] ?? [];
                $field['parameters']['allow_capture'] = ! empty($field['parameters']['allow_capture']);
                $field['parameters']['allow_upload'] = ! empty($field['parameters']['allow_upload']);
            }
            if (FieldTypeHelper::supportsChoices($field['field_type'])) {
                $choices = $field['parameters']['choices'] ?? [];
                if (count($choices) < 1) {
                    throw ValidationException::withMessages([
                        "fields.{$i}.parameters.choices" => 'Add at least one choice for '.$field['field_type'].'.',
                    ]);
                }
            } else {
                unset($field['parameters']['choices'], $field['parameters']['max_selections']);
            }
        }

        $version = $form->currentVersion;
        $builder->syncDraftFields($version, $validated['fields']);

        return back()->with('status', 'Draft saved.');
    }

    public function publish(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access, FormBuilderService $builder): RedirectResponse
    {
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $builder->publish($form, $request->user(), $request->input('change_note'));

        return redirect()->route('tools.show', $tool)->with('status', 'Form published and ready for collection.');
    }
}
