<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dc_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('device_uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('platform', 12)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('os_version', 30)->nullable();
            $table->string('app_version', 30)->nullable();
            $table->text('push_token')->nullable();
            $table->string('status', 12)->default('active');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('last_seen_at')->nullable();
        });

        Schema::create('dc_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('dc_devices')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction', 4);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->integer('records_total')->nullable();
            $table->integer('records_ok')->nullable();
            $table->integer('records_failed')->nullable();
            $table->string('status', 12)->default('running');
            $table->json('error_detail')->nullable();
        });

        Schema::create('dc_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('case_type', 60);
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('parent_case_id')->nullable()->constrained('dc_cases')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 12)->default('open');
            $table->json('properties')->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->auditColumns(userForeignKeys: false);
            $table->index(['subject_type', 'subject_id', 'case_type'], 'ix_dc_cases_subject');
        });

        Schema::create('dc_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('dc_forms');
            $table->foreignId('assignee_id')->constrained('users');
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('prefill')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status', 12)->default('open');
            $table->unsignedBigInteger('submission_id')->nullable();
            $table->auditColumns(userForeignKeys: false);
        });

        Schema::create('dc_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->uuid('supersedes_uuid')->nullable();
            $table->foreignId('form_id')->constrained('dc_forms');
            $table->foreignId('form_version_id')->constrained('dc_form_versions');
            $table->foreignId('project_id')->constrained('dc_projects');
            $table->foreignId('parent_submission_id')->nullable()->constrained('dc_submissions')->nullOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('dc_cases')->nullOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('dc_assignments')->nullOnDelete();
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('collector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('dc_devices')->nullOnDelete();
            $table->foreignId('sync_batch_id')->nullable()->constrained('dc_sync_batches')->nullOnDelete();
            $table->string('channel', 16);
            $table->string('status', 16)->default('submitted');
            $table->string('language', 8)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->integer('duration_sec')->nullable();
            $table->decimal('geo_lat', 9, 6)->nullable();
            $table->decimal('geo_lng', 9, 6)->nullable();
            $table->decimal('geo_accuracy_m', 8, 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('app_version', 30)->nullable();
            $table->json('data');
            $table->char('data_hash', 64)->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->date('retain_until')->nullable();
            $table->auditColumns(userForeignKeys: false);
            $table->index(['form_id', 'status', 'received_at'], 'ix_dc_sub_form');
            $table->index(['subject_type', 'subject_id'], 'ix_dc_sub_subject');
            $table->index(['collector_id', 'received_at'], 'ix_dc_sub_collector');
        });

        Schema::table('dc_assignments', function (Blueprint $table) {
            $table->foreign('submission_id')->references('id')->on('dc_submissions')->nullOnDelete();
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX ix_dc_sub_data ON dc_submissions USING GIN (data jsonb_path_ops)');
        }

        Schema::create('dc_repeat_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('dc_submissions')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('dc_form_fields');
            $table->foreignId('parent_instance_id')->nullable()->constrained('dc_repeat_instances')->nullOnDelete();
            $table->integer('index_no');
            $table->unique(['submission_id', 'field_id', 'parent_instance_id', 'index_no'], 'dc_repeat_instances_unique');
        });

        Schema::create('dc_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('dc_submissions')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('dc_form_fields');
            $table->foreignId('repeat_id')->nullable()->constrained('dc_repeat_instances')->nullOnDelete();
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 6)->nullable();
            $table->boolean('value_bool')->nullable();
            $table->date('value_date')->nullable();
            $table->timestamp('value_datetime')->nullable();
            $table->json('value_json')->nullable();
            $table->boolean('is_missing')->default(false);
            $table->string('missing_code', 8)->nullable();
            $table->unique(['submission_id', 'field_id', 'repeat_id']);
            $table->index(['field_id', 'value_number'], 'ix_dc_ans_field_num');
            $table->index(['field_id', 'value_date'], 'ix_dc_ans_field_date');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('CREATE INDEX ix_dc_ans_field_text ON dc_answers (field_id, value_text(191))');
        } else {
            Schema::table('dc_answers', function (Blueprint $table) {
                $table->index(['field_id', 'value_text'], 'ix_dc_ans_field_text');
            });
        }

        Schema::create('dc_answer_choices', function (Blueprint $table) {
            $table->foreignId('answer_id')->constrained('dc_answers')->cascadeOnDelete();
            $table->foreignId('choice_id')->nullable()->constrained('dc_choices')->nullOnDelete();
            $table->string('choice_name', 100);
            $table->integer('rank_no')->nullable();
            $table->text('other_text')->nullable();
            $table->primary(['answer_id', 'choice_name']);
        });

        Schema::create('dc_geo_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained('dc_answers')->cascadeOnDelete();
            $table->string('geom_type', 8);
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->decimal('altitude_m', 8, 2)->nullable();
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->json('gnss_meta')->nullable();
            $table->json('geojson');
        });

        Schema::create('dc_submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('dc_submissions')->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->constrained('dc_answers')->nullOnDelete();
            $table->foreignId('media_id')->constrained('media_attachments');
            $table->string('kind', 12);
            $table->json('exif')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->timestamp('captured_at')->nullable();
        });

        Schema::create('dc_sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->nullable()->constrained('dc_submissions')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('dc_devices')->nullOnDelete();
            $table->string('sensor_type', 30);
            $table->decimal('value_number', 14, 4)->nullable();
            $table->string('unit', 12)->nullable();
            $table->timestamp('taken_at');
            $table->index(['sensor_type', 'taken_at'], 'ix_dc_sensor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dc_sensor_readings');
        Schema::dropIfExists('dc_submission_files');
        Schema::dropIfExists('dc_geo_values');
        Schema::dropIfExists('dc_answer_choices');
        Schema::dropIfExists('dc_answers');
        Schema::dropIfExists('dc_repeat_instances');
        Schema::table('dc_assignments', function (Blueprint $table) {
            $table->dropForeign(['submission_id']);
        });
        Schema::dropIfExists('dc_submissions');
        Schema::dropIfExists('dc_assignments');
        Schema::dropIfExists('dc_cases');
        Schema::dropIfExists('dc_sync_batches');
        Schema::dropIfExists('dc_devices');
    }
};
