<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tilkit_workbook_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_record_id')->constrained('tilkit_indicator_records')->cascadeOnDelete();
            $table->string('column_letter', 3);
            $table->string('field_key', 80);
            $table->boolean('is_derived')->default(false);
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 16, 6)->nullable();
            $table->date('value_date')->nullable();
            $table->json('value_json')->nullable();
            $table->string('protocol_version', 32);
            $table->timestamps();

            $table->unique(['indicator_record_id', 'column_letter']);
            $table->index(['indicator_record_id', 'is_derived']);
        });

        Schema::create('tilkit_workbook_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_record_id')->constrained('tilkit_indicator_records')->cascadeOnDelete();
            $table->string('column_letter', 3)->default('Y');
            $table->string('storage_path');
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tilkit_workbook_media');
        Schema::dropIfExists('tilkit_workbook_values');
    }
};
