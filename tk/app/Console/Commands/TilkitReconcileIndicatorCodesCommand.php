<?php

namespace App\Console\Commands;

use App\Models\Tilkit\TilkitIndicatorCode;
use App\Support\Tilkit\IndicatorHierarchy;
use Illuminate\Console\Command;

class TilkitReconcileIndicatorCodesCommand extends Command
{
    protected $signature = 'tilkit:reconcile-indicator-codes';

    protected $description = 'Set category, type, and sub_type on legend rows from the canonical code string (XX-TTT-SSS).';

    public function handle(IndicatorHierarchy $hierarchy): int
    {
        $updated = 0;
        TilkitIndicatorCode::query()->whereNull('deprecated_at')->orderBy('code')->each(function (TilkitIndicatorCode $row) use ($hierarchy, &$updated) {
            $segments = $hierarchy->parseLegendCodeSegments((string) $row->code);
            if ($segments === null) {
                return;
            }

            $changes = [
                'category' => $segments['category'],
                'type' => $segments['type'],
                'sub_type' => $segments['sub_type'],
            ];

            $dirty = false;
            foreach ($changes as $field => $value) {
                if ((string) $row->{$field} !== (string) $value) {
                    $dirty = true;
                    break;
                }
            }

            if (! $dirty) {
                return;
            }

            $row->update($changes);
            $updated++;
        });

        $this->info("Reconciled {$updated} indicator code row(s) from legend code strings.");

        return self::SUCCESS;
    }
}
