<?php

namespace App\Support;

use App\Models\DcForm;
use App\Models\DcFormField;
use App\Models\DcFormVersion;
use App\Models\DcProject;
use Illuminate\Support\Collection;

class FormWizardPages
{
    public static function isSectionBreak(DcFormField $field): bool
    {
        if (in_array($field->field_type, ['group', 'repeat'], true)) {
            return true;
        }

        return $field->field_type === 'note' && str_starts_with($field->name, 'section_');
    }

    /**
     * @param  Collection<int, DcFormField>  $fields
     * @return list<array{title: string, description: ?string, form_id: ?int, fields: list<DcFormField>}>
     */
    public static function pagesFromFields(
        Collection $fields,
        ?string $fallbackTitle = null,
        ?string $fallbackDescription = null,
        ?int $formId = null,
    ): array {
        $pages = [];
        $current = null;

        foreach ($fields as $field) {
            if (self::isSectionBreak($field)) {
                if ($current !== null && $current['fields'] !== []) {
                    $pages[] = $current;
                }
                $current = [
                    'title' => $field->label ?: 'Section',
                    'description' => $field->hint,
                    'form_id' => $formId,
                    'fields' => [],
                ];

                continue;
            }

            if ($current === null) {
                $current = [
                    'title' => $fallbackTitle ?? 'Questions',
                    'description' => $fallbackDescription,
                    'form_id' => $formId,
                    'fields' => [],
                ];
            }

            $current['fields'][] = $field;
        }

        if ($current !== null && ($current['fields'] !== [] || $pages === [])) {
            $pages[] = $current;
        }

        return $pages;
    }

    /**
     * @return list<array{title: string, description: ?string, form_id: int, fields: list<DcFormField>}>
     */
    public static function pagesForForm(DcForm $form, DcFormVersion $version): array
    {
        $fields = $version->fields->sortBy('position')->values();

        return self::pagesFromFields(
            $fields,
            $form->title,
            $form->description,
            $form->id,
        );
    }

    /**
     * @return list<array{title: string, description: ?string, form_id: int, fields: list<DcFormField>}>
     */
    public static function pagesForTool(DcProject $tool): array
    {
        $pages = [];

        $forms = $tool->forms()
            ->where('status', 'published')
            ->orderBy('id')
            ->get();

        foreach ($forms as $form) {
            $version = $form->liveVersion();
            if (! $version) {
                continue;
            }
            $version->load('fields');
            $formPages = self::pagesForForm($form, $version);

            if ($formPages === []) {
                continue;
            }

            $hasInternalSections = $version->fields->contains(fn (DcFormField $f) => self::isSectionBreak($f));

            if (! $hasInternalSections && count($formPages) === 1) {
                $formPages[0]['title'] = $form->title;
                $formPages[0]['description'] = $form->description;
            }

            foreach ($formPages as $page) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /** @param list<array{fields: list<DcFormField>}> $pages */
    public static function allFields(array $pages): Collection
    {
        return collect($pages)
            ->flatMap(fn (array $page) => $page['fields'])
            ->values();
    }
}
