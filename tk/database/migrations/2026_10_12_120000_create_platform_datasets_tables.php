<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_datasets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('original_filename', 255);
            $table->string('storage_path', 500);
            $table->string('workspace_path', 500);
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('column_count')->default(0);
            $table->string('status', 30)->default('ready');
            $table->timestamps();
        });

        Schema::create('platform_dataset_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_dataset_id')->constrained('platform_datasets')->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('position');
            $table->string('inferred_type', 30);
            $table->json('sample_values')->nullable();
            $table->unsignedInteger('null_count')->default(0);
        });

        Schema::create('platform_analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_dataset_id')->constrained('platform_datasets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('prompt');
            $table->longText('generated_code')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('status', 30)->default('pending');
            $table->text('error_message')->nullable();
            $table->text('summary')->nullable();
            $table->json('chart_spec')->nullable();
            $table->string('chart_image_path', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_analysis_runs');
        Schema::dropIfExists('platform_dataset_columns');
        Schema::dropIfExists('platform_datasets');
    }
};
