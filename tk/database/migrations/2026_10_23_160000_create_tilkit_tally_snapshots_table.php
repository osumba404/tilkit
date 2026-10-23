<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tilkit_tally_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('tilkit_sites')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('tilkit_seasons')->cascadeOnDelete();
            $table->string('protocol_version', 32);
            $table->boolean('use_prior_multiplier')->default(false);
            $table->json('settings_payload')->nullable();
            $table->json('results');
            $table->string('reconciliation', 16);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['site_id', 'season_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tilkit_tally_snapshots');
    }
};
