<?php

namespace App\Http\Controllers;

use App\Models\DcForm;
use App\Models\DcProject;
use App\Support\Tilkit\WeatherTool;
use App\Support\FieldTypeHelper;
use App\Services\FormBuilderService;
use App\Services\ToolAccessService;
use App\Services\ToolAuditService;
use App\Services\XlsFormService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ToolFormController extends Controller
{
    private function assertNotTilkitWeatherModule(DcProject $tool): void
    {
        abort_if(WeatherTool::isWeatherModule($tool), 404);
    }

    /** @param  array<int, array<string, mixed>>  $choices */
    private function assignChoiceValues(array $choices): array
    {
        $used = [];
        $out = [];

        foreach ($choices as $index => $choice) {
            $label = trim((string) ($choice['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $base = Str::slug($label, '_');
            $base = preg_replace('/[^a-z0-9_]/', '', Str::lower($base)) ?: 'option_'.($index + 1);
            if (! preg_match('/^[a-z]/', $base)) {
                $base = 'option_'.$base;
            }
            $base = Str::limit($base, 100, '');

            $name = $base;
            $suffix = 2;
            while (in_array($name, $used, true)) {
                $name = Str::limit($base, 96, '').'_'.$suffix;
                $suffix++;
            }
            $used[] = $name;

            $out[] = ['label' => $label, 'value' => $name];
        }

        return $out;
    }

    public function create(Request $request, DcProject $tool, ToolAccessService $access): View
    {
        $this->assertNotTilkitWeatherModule($tool);
        $access->assertCanManage($request->user(), $tool);

        return view('tools.forms.create', compact('tool'));
    }

    public function store(Request $request, DcProject $tool, ToolAccessService $access, FormBuilderService $builder): RedirectResponse
    {
        $this->assertNotTilkitWeatherModule($tool);
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

    public function builder(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access, FormBuilderService $builder): View
    {
        $this->assertNotTilkitWeatherModule($tool);
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $form->load(['currentVersion.fields.fieldTypeDefinition']);
        $fieldTypes = \App\Models\DcFieldType::query()->orderBy('family')->orderBy('code')->get();
        $publishDiff = $builder->diffPublishedToDraft($form);

        return view('tools.forms.builder', compact('tool', 'form', 'fieldTypes', 'publishDiff'));
    }

    public function saveBuilder(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access, FormBuilderService $builder, ToolAuditService $audit): RedirectResponse
    {
        $this->assertNotTilkitWeatherModule($tool);
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
            'fields.*.relevant_expr' => ['nullable', 'string', 'max:500'],
            'fields.*.repeat_parent' => ['nullable', 'string', 'max:80'],
        ]);

        foreach ($validated['fields'] as $i => &$field) {
            $field['position'] = $i;
            if (in_array($field['field_type'], ['image', 'audio', 'video', 'file'], true)) {
                $field['parameters'] = $field['parameters'] ?? [];
                $field['parameters']['allow_capture'] = ! empty($field['parameters']['allow_capture']);
                $field['parameters']['allow_upload'] = ! empty($field['parameters']['allow_upload']);
            }
            if ($field['field_type'] === 'signature') {
                $field['parameters'] = $field['parameters'] ?? [];
                $field['parameters']['allow_draw'] = ! empty($field['parameters']['allow_draw']);
                $field['parameters']['allow_upload'] = ! empty($field['parameters']['allow_upload']);
                $field['parameters']['allow_capture'] = ! empty($field['parameters']['allow_capture']);
            }
            if ($field['field_type'] === 'calculate') {
                $expr = trim((string) ($field['parameters']['expression'] ?? ''));
                $field['calculation_expr'] = $expr !== '' ? $expr : null;
            }
            if (FieldTypeHelper::supportsChoices($field['field_type'])) {
                $choices = array_values(array_filter(
                    $field['parameters']['choices'] ?? [],
                    fn ($c) => trim((string) ($c['label'] ?? '')) !== '',
                ));
                $field['parameters']['choices'] = $this->assignChoiceValues($choices);
                if (count($field['parameters']['choices']) < 1) {
                    throw ValidationException::withMessages([
                        "fields.{$i}.parameters.choices" => 'Add at least one choice for '.$field['field_type'].'.',
                    ]);
                }
            } else {
                unset($field['parameters']['choices'], $field['parameters']['max_selections']);
            }
        }

        $result = $builder->syncDraftFields($form, $request->user(), $validated['fields']);

        $audit->log($tool, $request->user(), 'form.builder_saved', 'dc_form', $form->id, [
            'version_no' => $result['version']->version_no,
            'field_count' => count($validated['fields']),
        ]);

        $message = $result['forked']
            ? 'Created draft version '.$result['version']->version_no.' from the published form. Changes apply when you publish again.'
            : 'Draft saved.';

        return back()->with('status', $message);
    }

    public function publish(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access, FormBuilderService $builder, ToolAuditService $audit): RedirectResponse
    {
        $this->assertNotTilkitWeatherModule($tool);
        $access->assertCanPublishForms($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $diff = $builder->diffPublishedToDraft($form);
        $version = $builder->publish($form, $request->user(), $request->input('change_note'));

        $audit->log($tool, $request->user(), 'form.published', 'dc_form', $form->id, [
            'version_no' => $version->version_no,
            'diff' => $diff,
        ]);

        return redirect()->route('tools.show', $tool)->with('status', 'Form published and ready for collection.');
    }

    public function duplicate(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access, FormBuilderService $builder, ToolAuditService $audit): RedirectResponse
    {
        $this->assertNotTilkitWeatherModule($tool);
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $copy = $builder->duplicateForm($form, $request->user());
        $audit->log($tool, $request->user(), 'form.duplicated', 'dc_form', $copy->id, ['from_form_id' => $form->id]);

        return redirect()->route('tools.forms.builder', [$tool, $copy])->with('status', 'Form duplicated. Edit the copy before publishing.');
    }

    public function importCsv(Request $request, DcProject $tool, DcForm $form, ToolAccessService $access, FormBuilderService $builder): RedirectResponse
    {
        $this->assertNotTilkitWeatherModule($tool);
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $validated = $request->validate([
            'csv' => ['required', 'string', 'max:500000'],
        ]);

        $imported = $builder->importFieldsFromCsv($validated['csv']);
        if ($imported === []) {
            return back()->withErrors(['csv' => 'Could not parse CSV. Use columns: name, type, label.']);
        }

        $existing = $form->currentVersion?->fields->map(fn ($f) => [
            'name' => $f->name,
            'field_type' => $f->field_type,
            'label' => $f->label,
            'hint' => $f->hint,
            'is_required' => $f->is_required,
            'parameters' => $f->parameters ?? [],
            'relevant_expr' => $f->relevant_expr,
        ])->values()->all() ?? [];

        $builder->syncDraftFields($form, $request->user(), array_merge($existing, $imported));

        return back()->with('status', count($imported).' field(s) imported from CSV.');
    }

    public function exportXlsForm(
        Request $request,
        DcProject $tool,
        DcForm $form,
        ToolAccessService $access,
        XlsFormService $xlsform,
    ): StreamedResponse {
        $this->assertNotTilkitWeatherModule($tool);
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $version = $form->liveVersion() ?? $form->currentVersion;
        abort_unless($version, 404, 'No published or draft version to export.');

        return $xlsform->downloadResponse($form, $version);
    }

    public function importXlsForm(
        Request $request,
        DcProject $tool,
        DcForm $form,
        ToolAccessService $access,
        FormBuilderService $builder,
        XlsFormService $xlsform,
        ToolAuditService $audit,
    ): RedirectResponse {
        $this->assertNotTilkitWeatherModule($tool);
        $access->assertCanManage($request->user(), $tool);
        abort_unless($form->project_id === $tool->id, 404);

        $validated = $request->validate([
            'xlsform' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $fields = $xlsform->importToDraftFields($validated['xlsform']);
        $result = $builder->syncDraftFields($form, $request->user(), $fields);

        $audit->log($tool, $request->user(), 'form.xlsform_imported', 'dc_form', $form->id, [
            'version_no' => $result['version']->version_no,
            'field_count' => count($fields),
        ]);

        $message = $result['forked']
            ? 'XLSForm imported into new draft version '.$result['version']->version_no.'. Review and publish when ready.'
            : 'XLSForm imported - draft updated with '.count($fields).' field(s).';

        return back()->with('status', $message);
    }
}
