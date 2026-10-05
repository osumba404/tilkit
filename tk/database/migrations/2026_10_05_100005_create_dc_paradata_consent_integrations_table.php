<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dc_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('dc_submissions')->cascadeOnDelete();
            $table->string('field_name', 80)->nullable();
            $table->string('event_type', 16);
            $table->timestamp('occurred_at');
            $table->integer('elapsed_ms')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->index(['submission_id', 'occurred_at'], 'ix_dc_audit_sub');
        });

        Schema::create('dc_submission_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('dc_submissions');
            $table->integer('revision_no');
            $table->json('data_before');
            $table->json('data_after');
            $table->text('reason');
            $table->foreignId('edited_by')->constrained('users');
            $table->timestamp('edited_at')->useCurrent();
            $table->unique(['submission_id', 'revision_no']);
        });

        Schema::create('dc_submission_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('dc_submissions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users');
            $table->smallInteger('level_no')->default(1);
            $table->string('decision', 16);
            $table->text('comment')->nullable();
            $table->foreignId('signature_media_id')->nullable()->constrained('media_attachments')->nullOnDelete();
            $table->timestamp('decided_at')->useCurrent();
        });

        Schema::create('dc_quality_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->nullable()->constrained('dc_forms')->nullOnDelete();
            $table->string('name', 120);
            $table->string('rule_type', 20);
            $table->json('config');
            $table->string('severity', 8)->default('warn');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('dc_submission_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('dc_submissions')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('dc_quality_rules')->nullOnDelete();
            $table->string('field_name', 80)->nullable();
            $table->string('severity', 8);
            $table->text('message');
            $table->timestamp('raised_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX ix_dc_flags_open ON dc_submission_flags (submission_id) WHERE resolved_at IS NULL');
        } else {
            Schema::table('dc_submission_flags', function (Blueprint $table) {
                $table->index(['submission_id', 'resolved_at'], 'ix_dc_flags_open');
            });
        }

        Schema::create('dc_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects');
            $table->foreignId('submission_id')->nullable()->constrained('dc_submissions')->nullOnDelete();
            $table->string('subject_type', 60);
            $table->unsignedBigInteger('subject_id');
            $table->string('consent_text_version', 30);
            $table->text('scope');
            $table->string('method', 16);
            $table->boolean('given');
            $table->timestamp('given_at');
            $table->foreignId('obtained_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('witness_name', 160)->nullable();
            $table->string('guardian_name', 160)->nullable();
            $table->string('guardian_relationship', 40)->nullable();
            $table->foreignId('signature_media_id')->nullable()->constrained('media_attachments')->nullOnDelete();
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('withdrawal_note')->nullable();
            $table->index(['subject_type', 'subject_id', 'project_id'], 'ix_dc_consent_subject');
        });

        Schema::create('dc_access_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action', 12);
            $table->foreignId('form_id')->nullable()->constrained('dc_forms')->nullOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('dc_submissions')->nullOnDelete();
            $table->integer('record_count')->nullable();
            $table->json('fields_included')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['user_id', 'occurred_at'], 'ix_dc_access_user');
        });

        Schema::create('dc_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);
            $table->string('name', 120);
            $table->string('base_url')->nullable();
            $table->text('credentials')->nullable();
            $table->string('status', 10)->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_cursor', 120)->nullable();
        });

        Schema::create('dc_external_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('dc_integrations');
            $table->foreignId('form_id')->constrained('dc_forms');
            $table->string('external_uid', 120);
            $table->string('external_version', 60)->nullable();
            $table->json('field_name_map')->nullable();
            $table->unique(['integration_id', 'external_uid']);
        });

        Schema::create('dc_external_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_form_id')->constrained('dc_external_forms');
            $table->string('external_id', 120);
            $table->foreignId('submission_id')->nullable()->constrained('dc_submissions')->nullOnDelete();
            $table->json('raw_payload');
            $table->timestamp('pulled_at')->useCurrent();
            $table->string('status', 10)->default('pending');
            $table->text('error_detail')->nullable();
            $table->unique(['external_form_id', 'external_id']);
        });

        Schema::create('dc_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_version_id')->constrained('dc_form_versions');
            $table->foreignId('field_id')->constrained('dc_form_fields');
            $table->string('target_table', 80);
            $table->string('target_column', 80);
            $table->string('transform', 40)->nullable();
            $table->unique(['form_version_id', 'field_id', 'target_table', 'target_column'], 'dc_field_mappings_unique');
        });

        Schema::create('dc_promotion_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('dc_submissions');
            $table->string('target_table', 80);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('action', 8);
            $table->string('status', 8);
            $table->text('error_detail')->nullable();
            $table->timestamp('promoted_at')->useCurrent();
            $table->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('dc_import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->nullable()->constrained('dc_forms')->nullOnDelete();
            $table->foreignId('dataset_id')->nullable()->constrained('dc_datasets')->nullOnDelete();
            $table->foreignId('media_id')->constrained('media_attachments');
            $table->string('status', 10)->default('queued');
            $table->integer('rows_total')->nullable();
            $table->integer('rows_ok')->nullable();
            $table->integer('rows_failed')->nullable();
            $table->json('error_report')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('dc_data_elements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 200);
            $table->string('value_type', 12);
            $table->json('category_combo')->nullable();
        });

        Schema::create('dc_aggregate_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_element_id')->constrained('dc_data_elements');
            $table->string('org_unit_type', 40);
            $table->unsignedBigInteger('org_unit_id');
            $table->string('period', 12);
            $table->string('category_key', 80)->default('default');
            $table->decimal('value_number', 20, 4)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unique(
                ['data_element_id', 'org_unit_type', 'org_unit_id', 'period', 'category_key'],
                'dc_aggregate_values_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dc_aggregate_values');
        Schema::dropIfExists('dc_data_elements');
        Schema::dropIfExists('dc_import_jobs');
        Schema::dropIfExists('dc_promotion_log');
        Schema::dropIfExists('dc_field_mappings');
        Schema::dropIfExists('dc_external_submissions');
        Schema::dropIfExists('dc_external_forms');
        Schema::dropIfExists('dc_integrations');
        Schema::dropIfExists('dc_access_log');
        Schema::dropIfExists('dc_consents');
        Schema::dropIfExists('dc_submission_flags');
        Schema::dropIfExists('dc_quality_rules');
        Schema::dropIfExists('dc_submission_reviews');
        Schema::dropIfExists('dc_submission_revisions');
        Schema::dropIfExists('dc_audit_events');
    }
};
