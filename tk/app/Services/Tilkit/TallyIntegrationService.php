<?php

namespace App\Services\Tilkit;

use App\Enums\Tilkit\TercileForecast;
use App\Models\Tilkit\TilkitIndicatorRecord;
use App\Models\Tilkit\TilkitSeasonSettings;
use App\Support\Tilkit\TallyResult;
use Illuminate\Support\Collection;

class TallyIntegrationService
{
    public function __construct(
        private WorkbookRecordService $workbook,
        private V4FieldDeriver $v4,
    ) {}

    /** @param  Collection<int, TilkitIndicatorRecord>  $counted */
    public function enrich(TallyResult $base, Collection $counted, ?TilkitSeasonSettings $settings): array
    {
        $meanConfidence = $this->meanConfidence($counted);
        $vegIndex = $this->vegetationIndex($counted);
        $kmsa = $settings?->kmsa_outlook_tercile;
        $leading = $base->leadingWeighted ?? $base->leadingSimple;
        $agreement = $leading !== null && $kmsa !== null
            ? $this->v4->kmsaAgreement($leading, ['DJ' => $kmsa], $settings)
            : null;

        $thresholds = $settings?->settings['trigger_thresholds'] ?? [];
        $shareFloor = (float) ($thresholds['above_normal_weighted_share'] ?? 50);
        $confFloor = (float) ($thresholds['confidence_floor'] ?? 50);

        $belowShare = $base->weighted[1]['percent'] ?? 0;
        $aboveShare = $base->weighted[3]['percent'] ?? 0;

        $triggers = [];
        if ($aboveShare >= $shareFloor && $meanConfidence >= $confFloor) {
            $triggers[] = 'READINESS - excess rain / flood (weighted above-normal share)';
        }
        if ($belowShare >= $shareFloor && $meanConfidence >= $confFloor) {
            $triggers[] = 'ACTIVATE - drought / dry spell (weighted below-normal share)';
        }
        if ($vegIndex !== null && $vegIndex <= 33) {
            $triggers[] = 'READINESS - livestock / pasture (vegetation index ≤ 33)';
        }

        return [
            'mean_confidence_dq' => $meanConfidence,
            'vegetation_index' => $vegIndex,
            'kmsa_outlook_tercile' => $kmsa,
            'ndma_drought_phase' => $settings?->ndma_drought_phase,
            'ndma_vci3m' => $settings?->ndma_vci3m,
            'kmsa_agreement' => $agreement,
            'triggers' => $triggers,
            'integration_note' => $this->integrationNote($agreement),
        ];
    }

    /** @param  Collection<int, TilkitIndicatorRecord>  $counted */
    private function meanConfidence(Collection $counted): float
    {
        if ($counted->isEmpty()) {
            return 0.0;
        }
        $sum = 0;
        foreach ($counted as $record) {
            $dq = $record->workbookValues->firstWhere('column_letter', 'DQ');
            $sum += (float) ($dq?->value_number ?? 0);
        }

        return round($sum / $counted->count(), 2);
    }

    /** @param  Collection<int, TilkitIndicatorRecord>  $counted */
    private function vegetationIndex(Collection $counted): ?float
    {
        $weights = 0.0;
        $total = 0.0;
        foreach ($counted as $record) {
            $map = $this->workbook->rawWorkbookMap($record);
            $cq = isset($map['CQ']) && is_numeric($map['CQ']) ? (int) $map['CQ'] : null;
            if ($cq === null || $cq === 0) {
                continue;
            }
            $w = (float) ($record->workbookValues->firstWhere('column_letter', 'CG')?->value_number ?? 1);
            $score = match ($cq) {
                3 => 100.0,
                2 => 50.0,
                1 => 0.0,
                default => 0.0,
            };
            $weights += $w * $score;
            $total += $w;
        }

        return $total > 0 ? round($weights / $total, 2) : null;
    }

    private function integrationNote(?string $agreement): string
    {
        return match ($agreement) {
            'Agrees' => 'Publish one joint message where community and KMSA agree.',
            'Differs by one class' => 'Explain both forecasts; discuss in integration session.',
            'Opposite' => 'Schedule integration session - do not treat as data error.',
            default => 'Enter KMSA outlook in season settings (DJ) to compare.',
        };
    }
}
