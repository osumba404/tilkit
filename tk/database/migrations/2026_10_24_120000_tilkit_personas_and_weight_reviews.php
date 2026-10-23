<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_members', function (Blueprint $table) {
            $table->string('tilkit_persona', 40)->nullable()->after('role');
        });

        Schema::create('tilkit_weight_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_record_id')->constrained('tilkit_indicator_records')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('climate_flag', 20)->default('STRONG');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_lead_itk_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_technical_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signed_off_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_off_at')->nullable();
            $table->timestamps();
            $table->unique(['indicator_record_id', 'climate_flag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tilkit_weight_reviews');
        Schema::table('tool_members', function (Blueprint $table) {
            $table->dropColumn('tilkit_persona');
        });
    }
};
