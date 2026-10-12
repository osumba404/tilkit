<?php

namespace App\Services;

use App\Models\DcForm;
use App\Models\DcFormField;
use App\Models\DcFormVersion;
use App\Support\XlsForm\XlsFormTypeMapper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XlsFormService
{
    private const SURVEY_HEADERS = [
        'type',
        'name',
        'label',
        'hint',
        'required',
        'relevant',
        'constraint',
        'constraint_message',
        'calculation',
        'appearance',
        'default',
        'repeat_count',
        'tilkit_field_type',
        'tilkit_parameters',
    ];

    private const CHOICE_HEADERS = ['list_name', 'name', 'label'];

    public function exportSpreadsheet(DcForm $form, DcFormVersion $version): Spreadsheet
    {
        $version->load(['fields' => fn ($q) => $q->orderBy('position')]);

        $spreadsheet = new Spreadsheet;
        $survey = $spreadsheet->getActiveSheet();
        $survey->setTitle('survey');
        $survey->fromArray([self::SURVEY_HEADERS], null, 'A1');

        $choicesSheet = $spreadsheet->createSheet();
        $choicesSheet->setTitle('choices');
        $choicesSheet->fromArray([self::CHOICE_HEADERS], null, 'A1');

        $settingsSheet = $spreadsheet->createSheet();
        $settingsSheet->setTitle('settings');
        $settingsSheet->fromArray([
            ['form_title', $form->title],
            ['form_id', $form->code],
            ['version', (string) $version->version_no],
            ['default_language', 'English'],
            ['tilkit_export', '1'],
            ['style', 'field-list'],
        ], null, 'A1');

        $choiceRows = [];
        $surveyRows = [];
        $fields = $version->fields->values();
        $byId = $fields->keyBy('id');

        foreach ($fields as $field) {
            if ($field->parent_id) {
                continue;
            }

            if ($field->field_type === 'repeat') {
                $surveyRows[] = $this->surveyRow($field, null);
                foreach ($fields->where('parent_id', $field->id)->sortBy('position') as $child) {
                    $surveyRows[] = $this->surveyRow($child, $field->name);
                }
                $surveyRows[] = $this->endRepeatRow();

                continue;
            }

            $surveyRows[] = $this->surveyRow($field, null);

            if ($field->field_type === 'group') {
                $surveyRows[] = $this->endGroupRow();
            }

            $choiceRows = array_merge($choiceRows, $this->choiceRowsForField($field));
        }

        $rowIndex = 2;
        foreach ($surveyRows as $row) {
            $survey->fromArray([$row], null, 'A'.$rowIndex);
            $rowIndex++;
        }

        $choiceIndex = 2;
        foreach ($choiceRows as $crow) {
            $choicesSheet->fromArray([$crow], null, 'A'.$choiceIndex);
            $choiceIndex++;
        }

        return $spreadsheet;
    }

    public function downloadResponse(DcForm $form, DcFormVersion $version): StreamedResponse
    {
        $spreadsheet = $this->exportSpreadsheet($form, $version);
        $filename = Str::slug($form->code).'-v'.$version->version_no.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function importToDraftFields(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $survey = $spreadsheet->getSheetByName('survey') ?? $spreadsheet->getSheet(0);
        $choices = $this->parseChoicesSheet($spreadsheet->getSheetByName('choices'));

        $headers = $this->headerMap($survey);
        if (! isset($headers['type'], $headers['name'])) {
            throw ValidationException::withMessages([
                'xlsform' => 'Survey sheet must include at least "type" and "name" columns.',
            ]);
        }

        $fields = [];
        $position = 0;
        $repeatStack = [];
        $highest = $survey->getHighestRow();

        for ($row = 2; $row <= $highest; $row++) {
            $typeRaw = $this->cell($survey, $row, $headers, 'type');
            if ($typeRaw === '') {
                continue;
            }

            $parsed = XlsFormTypeMapper::fromSurveyType($typeRaw);
            if ($parsed['end_repeat']) {
                array_pop($repeatStack);

                continue;
            }

            if ($parsed['skip']) {
                continue;
            }

            $name = $this->cell($survey, $row, $headers, 'name');
            if ($name === '' && ! in_array($parsed['field_type'], ['group', 'repeat', 'note'], true)) {
                continue;
            }

            if ($name === '' && in_array($parsed['field_type'], ['group', 'repeat'], true)) {
                $name = 'section_'.($position + 1);
            }

            $tilkitOverride = $this->cell($survey, $row, $headers, 'tilkit_field_type');
            $fieldType = $tilkitOverride !== '' ? $tilkitOverride : $parsed['field_type'];

            $parametersJson = $this->cell($survey, $row, $headers, 'tilkit_parameters');
            $parameters = $parametersJson !== '' ? (json_decode($parametersJson, true) ?: []) : [];

            $listName = $parsed['choice_list'] ?? ($name !== '' ? $name.'_choices' : null);
            if (in_array($fieldType, ['select_one', 'select_multiple', 'rank'], true)) {
                $parameters['choices'] = $choices[$listName] ?? $choices[$name] ?? [];
                if ($parameters['choices'] === []) {
                    $parameters['choices'] = $choices[Str::lower($listName ?? '')] ?? [];
                }
            }

            if ($fieldType === 'calculate') {
                $calc = $this->cell($survey, $row, $headers, 'calculation');
                if ($calc !== '') {
                    $parameters['expression'] = $calc;
                }
            }

            $repeatParent = $repeatStack !== [] ? end($repeatStack) : null;

            if ($fieldType === 'repeat') {
                $repeatStack[] = $name;
            }

            $fields[] = [
                'name' => $this->sanitizeName($name, $position),
                'field_type' => $fieldType,
                'label' => $this->cell($survey, $row, $headers, 'label') ?: $name,
                'hint' => $this->cell($survey, $row, $headers, 'hint') ?: null,
                'is_required' => $this->parseRequired($this->cell($survey, $row, $headers, 'required')),
                'relevant_expr' => $this->cell($survey, $row, $headers, 'relevant') ?: null,
                'calculation_expr' => $this->cell($survey, $row, $headers, 'calculation') ?: null,
                'repeat_parent' => $repeatParent,
                'parameters' => $parameters,
                'position' => $position,
            ];

            $position++;
        }

        if ($fields === []) {
            throw ValidationException::withMessages([
                'xlsform' => 'No questions found in the survey sheet.',
            ]);
        }

        return $fields;
    }

    /** @return list<string> */
    private function surveyRow(DcFormField $field, ?string $repeatParentName): array
    {
        $listName = in_array($field->field_type, ['select_one', 'select_multiple', 'rank'], true)
            ? $field->name.'_choices'
            : null;

        $exportType = $field->field_type;
        if ($repeatParentName !== null) {
            $exportType = $field->field_type;
        }

        $xlsType = XlsFormTypeMapper::toSurveyType($exportType, $listName);

        $params = $field->parameters ?? [];
        if ($field->field_type === 'calculate' && $field->calculation_expr) {
            $params['expression'] = $field->calculation_expr;
        }

        $needsTilkitType = in_array($field->field_type, ['signature', 'audio', 'video', 'datetime', 'metadata'], true)
            || $xlsType !== $field->field_type;

        return [
            $xlsType,
            $field->name,
            $field->label,
            $field->hint ?? '',
            $field->is_required ? 'yes' : 'no',
            $field->relevant_expr ?? '',
            $field->constraint_expr ?? '',
            $field->constraint_msg ?? '',
            $field->calculation_expr ?? ($params['expression'] ?? ''),
            $field->appearance ?? '',
            $field->default_value ?? '',
            $field->repeat_count_expr ?? '',
            $needsTilkitType ? $field->field_type : '',
            $params !== [] ? json_encode($params) : '',
        ];
    }

    /** @return list<string> */
    private function endGroupRow(): array
    {
        return ['end_group', '', '', '', '', '', '', '', '', '', '', '', '', ''];
    }

    /** @return list<string> */
    private function endRepeatRow(): array
    {
        return ['end_repeat', '', '', '', '', '', '', '', '', '', '', '', '', ''];
    }

    /** @return list<list<string>> */
    private function choiceRowsForField(DcFormField $field): array
    {
        if (! in_array($field->field_type, ['select_one', 'select_multiple', 'rank'], true)) {
            return [];
        }

        $choices = $field->parameters['choices'] ?? [];
        $listName = $field->name.'_choices';
        $rows = [];

        foreach ($choices as $choice) {
            $value = (string) ($choice['value'] ?? $choice['label'] ?? '');
            $label = (string) ($choice['label'] ?? $value);
            if ($value === '' && $label === '') {
                continue;
            }
            $rows[] = [$listName, $value, $label];
        }

        return $rows;
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function parseChoicesSheet(?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        if ($sheet === null) {
            return [];
        }

        $headers = $this->headerMap($sheet);
        if (! isset($headers['list_name'], $headers['name'], $headers['label'])) {
            return [];
        }

        $out = [];
        $highest = $sheet->getHighestRow();
        for ($row = 2; $row <= $highest; $row++) {
            $list = $this->cell($sheet, $row, $headers, 'list_name');
            $value = $this->cell($sheet, $row, $headers, 'name');
            $label = $this->cell($sheet, $row, $headers, 'label');
            if ($list === '' || $value === '') {
                continue;
            }
            $out[$list][] = ['value' => $value, 'label' => $label ?: $value];
        }

        return $out;
    }

    /** @param array<string, int> $headers */
    private function cell(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, array $headers, string $key): string
    {
        if (! isset($headers[$key])) {
            return '';
        }

        $val = $sheet->getCell([$headers[$key], $row])->getValue();

        return trim((string) ($val ?? ''));
    }

    /** @return array<string, int> */
    private function headerMap(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        $map = [];
        $col = 1;
        while (true) {
            $val = $sheet->getCell([$col, 1])->getValue();
            if ($val === null || $val === '') {
                break;
            }
            $map[strtolower(trim((string) $val))] = $col;
            $col++;
            if ($col > 50) {
                break;
            }
        }

        return $map;
    }

    private function parseRequired(string $value): bool
    {
        $v = strtolower(trim($value));

        return in_array($v, ['yes', 'true', '1'], true);
    }

    private function sanitizeName(string $name, int $position): string
    {
        $name = Str::lower(trim($name));
        $name = preg_replace('/[^a-z0-9_]/', '_', $name) ?? 'field';
        $name = trim($name, '_');
        if ($name === '' || ! preg_match('/^[a-z]/', $name)) {
            $name = 'field_'.($position + 1);
        }

        return Str::limit($name, 80, '');
    }
}
