<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tilkit_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'name']);
        });

        Schema::table('tilkit_sites', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('project_id')->constrained('tilkit_programs')->cascadeOnDelete();
            $table->string('continent', 60)->nullable()->after('name');
            $table->string('country_name', 120)->nullable()->after('country');
        });

        Schema::table('tilkit_seasons', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('project_id')->constrained('tilkit_programs')->cascadeOnDelete();
        });

        $this->backfillPrograms();

        Schema::table('tilkit_sites', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'name']);
            $table->unique(['program_id', 'name']);
        });

        Schema::table('tilkit_seasons', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'label']);
            $table->unique(['program_id', 'label']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tilkit_seasons MODIFY label VARCHAR(120) NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tilkit_seasons MODIFY label VARCHAR(40) NOT NULL');
        }

        Schema::table('tilkit_seasons', function (Blueprint $table) {
            $table->dropUnique(['program_id', 'label']);
            $table->unique(['project_id', 'label']);
            $table->dropConstrainedForeignId('program_id');
        });

        Schema::table('tilkit_sites', function (Blueprint $table) {
            $table->dropUnique(['program_id', 'name']);
            $table->unique(['project_id', 'name']);
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn(['continent', 'country_name']);
        });

        Schema::dropIfExists('tilkit_programs');
    }

    private function backfillPrograms(): void
    {
        $projectIds = DB::table('tilkit_sites')->distinct()->pluck('project_id')
            ->merge(DB::table('tilkit_seasons')->distinct()->pluck('project_id'))
            ->unique();

        foreach ($projectIds as $projectId) {
            $toolName = DB::table('dc_projects')->where('id', $projectId)->value('name') ?? 'Default program';
            $programId = DB::table('tilkit_programs')->insertGetId([
                'project_id' => $projectId,
                'name' => $toolName,
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('tilkit_sites')->where('project_id', $projectId)->whereNull('program_id')->update([
                'program_id' => $programId,
            ]);
            DB::table('tilkit_sites')->where('project_id', $projectId)->whereNull('continent')->update([
                'continent' => 'Africa',
            ]);

            DB::table('tilkit_seasons')->where('project_id', $projectId)->whereNull('program_id')->update([
                'program_id' => $programId,
            ]);
        }
    }
};
