<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dc_projects', function (Blueprint $table) {
            $table->text('consent_text')->nullable()->after('public_collection_enabled');
            $table->string('consent_ack_label', 500)->nullable()->after('consent_text');
            $table->boolean('consent_require_signature')->default(false)->after('consent_ack_label');
            $table->string('brand_logo_path', 500)->nullable()->after('consent_require_signature');
            $table->string('brand_footer', 1000)->nullable()->after('brand_logo_path');
            $table->string('default_locale', 10)->default('en')->after('brand_footer');
        });

        Schema::table('tool_members', function (Blueprint $table) {
            $table->boolean('can_export')->nullable()->after('role');
            $table->boolean('can_publish_forms')->nullable()->after('can_export');
        });

        Schema::table('dc_submissions', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable()->after('reviewed_by');
        });

        Schema::create('submission_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->foreignId('form_id')->nullable()->constrained('dc_forms')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('device_token', 64)->nullable();
            $table->json('payload');
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
            $table->unique(['project_id', 'device_token']);
        });

        Schema::create('tool_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['project_id', 'created_at']);
        });

        Schema::create('tool_scheduled_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->string('recipient_emails', 1000);
            $table->string('frequency', 20)->default('weekly');
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_scheduled_exports');
        Schema::dropIfExists('tool_audit_logs');
        Schema::dropIfExists('submission_drafts');
        Schema::table('dc_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'review_notes']);
        });
        Schema::table('tool_members', function (Blueprint $table) {
            $table->dropColumn(['can_export', 'can_publish_forms']);
        });
        Schema::table('dc_projects', function (Blueprint $table) {
            $table->dropColumn([
                'consent_text',
                'consent_ack_label',
                'consent_require_signature',
                'brand_logo_path',
                'brand_footer',
                'default_locale',
            ]);
        });
    }
};
