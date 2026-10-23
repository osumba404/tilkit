<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tilkit_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->string('label', 40);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('protocol_version', 32);
            $table->timestamps();
            $table->unique(['project_id', 'label']);
        });

        Schema::create('tilkit_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('ward', 120)->nullable();
            $table->string('county', 120)->nullable();
            $table->string('country', 2)->default('KE');
            $table->string('primary_language', 40)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'name']);
        });

        Schema::create('tilkit_indicator_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('category', 8);
            $table->string('type', 8)->nullable();
            $table->string('sub_type', 8)->nullable();
            $table->string('label', 200)->nullable();
            $table->text('usage_notes')->nullable();
            $table->decimal('prior_multiplier', 5, 3)->nullable();
            $table->string('preferred_code', 32)->nullable();
            $table->timestamp('deprecated_at')->nullable();
            $table->string('protocol_version', 32);
            $table->timestamps();
        });

        Schema::create('tilkit_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('indicator_uid', 32)->unique();
            $table->foreignId('indicator_code_id')->constrained('tilkit_indicator_codes')->restrictOnDelete();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->string('local_name', 200)->nullable();
            $table->text('local_description')->nullable();
            $table->text('local_prediction')->nullable();
            $table->string('locality', 200)->nullable();
            $table->string('ethnic_community', 120)->nullable();
            $table->string('language', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('tilkit_indicator_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('indicator_id')->constrained('tilkit_indicators')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('tilkit_sites')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('tilkit_seasons')->cascadeOnDelete();
            $table->string('protocol_version', 32);

            $table->boolean('sighted')->default(false);
            $table->boolean('confirmed')->default(false);
            $table->boolean('approved')->default(false);
            $table->boolean('verified')->default(false);
            $table->string('validation_status', 40)->default('provisional_emerging');
            $table->unsignedTinyInteger('tercile_forecast')->default(0);
            $table->unsignedTinyInteger('current_reliability')->default(0);
            $table->unsignedTinyInteger('decision_relevance')->default(0);

            $table->date('onset_earliest')->nullable();
            $table->date('onset_latest')->nullable();
            $table->date('cessation_earliest')->nullable();
            $table->date('cessation_latest')->nullable();
            $table->unsignedTinyInteger('distribution_forecast')->default(0);

            $table->date('actual_onset')->nullable();
            $table->date('actual_cessation')->nullable();
            $table->unsignedTinyInteger('actual_distribution')->nullable();
            $table->unsignedTinyInteger('actual_tercile')->nullable();
            $table->string('season_outcome', 20)->nullable();

            $table->boolean('do_no_harm')->default(false);
            $table->unsignedTinyInteger('consent_level')->nullable();
            $table->string('consent_credit', 200)->nullable();

            $table->json('demographics')->nullable();
            $table->json('extended_attributes')->nullable();

            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['indicator_id', 'site_id', 'season_id']);
        });

        Schema::create('tilkit_sightings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('indicator_record_id')->constrained('tilkit_indicator_records')->cascadeOnDelete();
            $table->timestamp('sighted_at');
            $table->string('place_text', 500)->nullable();
            $table->string('landmark', 200)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('observed_by', 200)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tilkit_season_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('tilkit_sites')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('tilkit_seasons')->cascadeOnDelete();
            $table->boolean('use_prior_multiplier')->default(false);
            $table->json('settings')->nullable();
            $table->string('protocol_version', 32);
            $table->timestamps();
            $table->unique(['site_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tilkit_season_settings');
        Schema::dropIfExists('tilkit_sightings');
        Schema::dropIfExists('tilkit_indicator_records');
        Schema::dropIfExists('tilkit_indicators');
        Schema::dropIfExists('tilkit_indicator_codes');
        Schema::dropIfExists('tilkit_sites');
        Schema::dropIfExists('tilkit_seasons');
    }
};
