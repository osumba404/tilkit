<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\ValidationStatus;
use App\Models\DcProject;
use App\Models\Tilkit\TilkitIndicatorRecord;
use Illuminate\Support\Collection;

class DataDeskService
{
    public function __construct(private WorkbookRecordService $workbook) {}

    /** @return Collection<int, array<string, mixed>> */
    public function queueForTool(DcProject $tool): Collection
    {
        return TilkitIndicatorRecord::query()
            ->whereHas('indicator', fn ($q) => $q->where('project_id', $tool->id))
            ->with(['indicator', 'site', 'season', 'workbookValues', 'sightings'])
            ->latest('updated_at')
            ->limit(200)
            ->get()
            ->map(fn (TilkitIndicatorRecord $record) => $this->rowMeta($record));
    }

    /** @return array<string, mixed> */
    private function rowMeta(TilkitIndicatorRecord $record): array
    {
        $ce = $record->workbookValues->firstWhere('column_letter', 'CE');
        $cc = $record->workbookValues->firstWhere('column_letter', 'CC');
        $cp = $record->workbookValues->firstWhere('column_letter', 'CP');
        $map = $this->workbook->rawWorkbookMap($record);
        $v = $map['V'] ?? null;
        $blankRelevance = $v === null || $v === '' || $v === 0;

        return [
            'record' => $record,
            'tally_status' => $ce?->value_text ?? '—',
            'needs_confirm' => ! $record->confirmed,
            'needs_approve' => ! $record->approved,
            'needs_verify' => ! $record->verified,
            'contested' => $record->validation_status === ValidationStatus::Contested,
            'stability_flag' => (int) ($cc?->value_number ?? 0) === 1,
            'climate_strong' => ($cp?->value_text ?? '') === 'STRONG',
            'blank_decision_relevance' => $blankRelevance,
            'sighting_count' => $record->sightings->count(),
            'queue' => $this->primaryQueue($record, $blankRelevance, $cp?->value_text ?? null),
        ];
    }

    private function primaryQueue(TilkitIndicatorRecord $record, bool $blankRelevance, ?string $cp): string
    {
        if ($record->validation_status === ValidationStatus::Contested) {
            return 'contested';
        }
        if ($cp === 'STRONG') {
            return 'climate_review';
        }
        if (! $record->confirmed || ! $record->approved) {
            return 'pending_validation';
        }
        if ($blankRelevance) {
            return 'blank_relevance';
        }
        if (! $record->verified) {
            return 'pending_verify';
        }

        return 'ok';
    }
}
