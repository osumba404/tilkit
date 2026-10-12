<?php

namespace App\Services\Datasets;

use App\Models\PlatformDataset;
use App\Models\PlatformDatasetColumn;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DatasetIngestionService
{
    public function __construct(
        private DatasetTypeInference $inference,
    ) {}

    public function ingest(UploadedFile $file, User $user, ?string $name = null): PlatformDataset
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt', 'xlsx', 'xls'], true)) {
            throw ValidationException::withMessages([
                'dataset' => 'Upload a CSV or Excel file (.csv, .xlsx, .xls).',
            ]);
        }

        return DB::transaction(function () use ($file, $user, $name, $ext): PlatformDataset {
            $uuid = (string) Str::uuid();
            $workspace = 'datasets/'.$uuid;
            Storage::disk('local')->makeDirectory($workspace);

            $disk = Storage::disk('local');
            $stored = $file->storeAs($workspace, 'original.'.$ext, 'local');
            if ($stored === false) {
                throw ValidationException::withMessages([
                    'dataset' => 'Could not save the uploaded file. Check storage permissions.',
                ]);
            }

            $sourcePath = $disk->path($stored);
            $csvPath = $disk->path($workspace.'/data.csv');

            $this->convertToCsv($sourcePath, $csvPath, $ext);

            $profile = $this->inference->profileCsv($csvPath);

            $maxRows = config('tilkit.dataset_analysis.max_rows', 100000);
            if ($profile['row_count'] > $maxRows) {
                throw ValidationException::withMessages([
                    'dataset' => "Dataset exceeds {$maxRows} rows. Split the file or raise TILKIT_DATASET_MAX_ROWS.",
                ]);
            }

            $dataset = PlatformDataset::create([
                'uuid' => $uuid,
                'uploaded_by' => $user->id,
                'name' => $name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_filename' => $file->getClientOriginalName(),
                'storage_path' => $stored,
                'workspace_path' => $workspace,
                'row_count' => $profile['row_count'],
                'column_count' => count($profile['columns']),
                'status' => 'ready',
            ]);

            foreach ($profile['columns'] as $col) {
                PlatformDatasetColumn::create([
                    'platform_dataset_id' => $dataset->id,
                    'name' => $col['name'],
                    'position' => $col['position'],
                    'inferred_type' => $col['inferred_type'],
                    'sample_values' => $col['sample_values'],
                    'null_count' => $col['null_count'],
                ]);
            }

            return $dataset->fresh('columns');
        });
    }

    private function convertToCsv(string $sourcePath, string $csvPath, string $ext): void
    {
        if ($ext === 'csv' || $ext === 'txt') {
            copy($sourcePath, $csvPath);

            return;
        }

        $spreadsheet = IOFactory::load($sourcePath);
        $sheet = $spreadsheet->getActiveSheet();
        $handle = fopen($csvPath, 'w');
        if ($handle === false) {
            throw ValidationException::withMessages(['dataset' => 'Could not create staging CSV.']);
        }

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getValue();
            }
            fputcsv($handle, $cells);
        }
        fclose($handle);
    }
}
