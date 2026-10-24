<?php

namespace App\Console\Commands;

use App\Models\ToolScheduledExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledToolExports extends Command
{
    protected $signature = 'tilkit:send-scheduled-exports';

    protected $description = 'Email CSV exports to tool admins on their configured schedule';

    public function handle(): int
    {
        $exports = ToolScheduledExport::query()
            ->where('is_active', true)
            ->with('project')
            ->get();

        foreach ($exports as $export) {
            if (! $this->isDue($export)) {
                continue;
            }

            $emails = array_filter(array_map('trim', explode(',', $export->recipient_emails)));
            if ($emails === []) {
                continue;
            }

            $csv = $this->buildCsv($export);
            $filename = 'tilkit-'.$export->project->code.'-'.now()->format('Y-m-d').'.csv';

            foreach ($emails as $email) {
                Mail::raw(
                    'Attached is the scheduled submission export for '.$export->project->name.'.',
                    function ($message) use ($email, $export, $csv, $filename): void {
                        $message->to($email)
                            ->subject('Scheduled export - '.$export->project->name)
                            ->attachData($csv, $filename, ['mime' => 'text/csv']);
                    }
                );
            }

            $export->update(['last_run_at' => now()]);
        }

        return self::SUCCESS;
    }

    private function isDue(ToolScheduledExport $export): bool
    {
        if ($export->last_run_at === null) {
            return true;
        }

        return match ($export->frequency) {
            'daily' => $export->last_run_at->lt(now()->subDay()),
            'monthly' => $export->last_run_at->lt(now()->subMonth()),
            default => $export->last_run_at->lt(now()->subWeek()),
        };
    }

    private function buildCsv(ToolScheduledExport $export): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['client_uuid', 'form_code', 'status', 'channel', 'received_at', 'data_json']);

        $export->project->submissions()
            ->with('form:id,code')
            ->orderBy('id')
            ->chunk(200, function ($chunk) use ($handle): void {
                foreach ($chunk as $row) {
                    fputcsv($handle, [
                        $row->client_uuid,
                        $row->form?->code,
                        $row->status,
                        $row->channel,
                        $row->received_at?->toIso8601String(),
                        json_encode($row->data),
                    ]);
                }
            });

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }
}
