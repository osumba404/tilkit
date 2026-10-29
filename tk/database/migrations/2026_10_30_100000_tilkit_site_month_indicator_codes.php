<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tilkit_site_month_indicator_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('tilkit_programs')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('tilkit_sites')->cascadeOnDelete();
            $table->unsignedTinyInteger('calendar_month');
            $table->foreignId('indicator_code_id')->constrained('tilkit_indicator_codes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['site_id', 'calendar_month', 'indicator_code_id'],
                'tilkit_site_month_indicator_code_unique'
            );
            $table->index(['program_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tilkit_site_month_indicator_codes');
    }
};
