<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tilkit_indicator_records', function (Blueprint $table) {
            $table->foreignId('confirmed_by_user_id')->nullable()->after('confirmed')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->after('approved')->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->after('verified')->constrained('users')->nullOnDelete();
        });

        Schema::table('tilkit_season_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('kmsa_outlook_tercile')->nullable()->after('settings');
            $table->string('ndma_drought_phase', 40)->nullable()->after('kmsa_outlook_tercile');
            $table->decimal('ndma_vci3m', 5, 2)->nullable()->after('ndma_drought_phase');
        });

        Schema::create('tilkit_consensus_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('tilkit_sites')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('tilkit_seasons')->cascadeOnDelete();
            $table->string('label', 120);
            $table->unsignedTinyInteger('community_tercile')->nullable();
            $table->unsignedTinyInteger('kmsa_tercile')->nullable();
            $table->unsignedTinyInteger('integrated_tercile')->nullable();
            $table->text('advisory_message')->nullable();
            $table->foreignId('tally_snapshot_id')->nullable()->constrained('tilkit_tally_snapshots')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tilkit_consensus_forecasts');
        Schema::table('tilkit_season_settings', function (Blueprint $table) {
            $table->dropColumn(['kmsa_outlook_tercile', 'ndma_drought_phase', 'ndma_vci3m']);
        });
        Schema::table('tilkit_indicator_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by_user_id');
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropConstrainedForeignId('verified_by_user_id');
        });
    }
};
