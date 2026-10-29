<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tilkit_sites', 'study_area_geojson')) {
            Schema::table('tilkit_sites', function (Blueprint $table) {
                $table->json('study_area_geojson')->nullable()->after('longitude');
            });
        }

        if (! Schema::hasColumn('tilkit_seasons', 'site_id')) {
            Schema::table('tilkit_seasons', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('program_id')->constrained('tilkit_sites')->cascadeOnDelete();
            });
        }

        $this->dropSeasonLabelUniques();

        $this->backfillSeasonSiteIds();

        if (! $this->indexExists('tilkit_seasons', 'tilkit_seasons_site_id_label_unique')) {
            Schema::table('tilkit_seasons', function (Blueprint $table) {
                $table->unique(['site_id', 'label']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('tilkit_seasons', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'label']);
            if (Schema::hasColumn('tilkit_seasons', 'program_id')) {
                $table->unique(['program_id', 'label']);
            } else {
                $table->unique(['project_id', 'label']);
            }
            $table->dropConstrainedForeignId('site_id');
        });

        Schema::table('tilkit_sites', function (Blueprint $table) {
            $table->dropColumn('study_area_geojson');
        });
    }

    private function dropSeasonLabelUniques(): void
    {
        foreach (['tilkit_seasons_program_id_label_unique', 'tilkit_seasons_project_id_label_unique'] as $indexName) {
            if (! $this->indexExists('tilkit_seasons', $indexName)) {
                continue;
            }
            try {
                Schema::table('tilkit_seasons', function (Blueprint $table) use ($indexName) {
                    if ($indexName === 'tilkit_seasons_program_id_label_unique') {
                        $table->dropUnique(['program_id', 'label']);
                    } else {
                        $table->dropUnique(['project_id', 'label']);
                    }
                });
            } catch (\Throwable) {
                // Some MySQL installs refuse to drop legacy uniques; site-scoped uniques still apply for new rows.
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            $rows = $connection->select("PRAGMA index_list('{$table}')");

            return collect($rows)->contains(fn ($row) => ($row->name ?? '') === $indexName);
        }

        $database = $connection->getDatabaseName();
        $row = $connection->selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName],
        );

        return $row !== null;
    }

    private function backfillSeasonSiteIds(): void
    {
        $orphanSeasons = DB::table('tilkit_seasons')->whereNull('site_id')->orderBy('id')->get();

        foreach ($orphanSeasons as $season) {
            $siteId = DB::table('tilkit_sites')
                ->where('program_id', $season->program_id)
                ->orderBy('id')
                ->value('id');

            if ($siteId === null) {
                continue;
            }

            DB::table('tilkit_seasons')->where('id', $season->id)->update([
                'site_id' => $siteId,
            ]);
        }
    }
};
