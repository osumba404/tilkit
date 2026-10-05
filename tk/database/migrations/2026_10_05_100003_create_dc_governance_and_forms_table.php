<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dc_projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 160);
            $table->string('module', 40);
            $table->text('purpose');
            $table->string('lawful_basis', 30);
            $table->integer('retention_months')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->auditColumns(userForeignKeys: false);
        });

        Schema::create('dc_field_types', function (Blueprint $table) {
            $table->string('code', 40)->primary();
            $table->string('family', 10);
            $table->string('catalogue_id', 12)->nullable();
            $table->string('value_kind', 12);
            $table->boolean('is_input')->default(true);
            $table->boolean('is_container')->default(false);
        });

        Schema::create('dc_forms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained('dc_projects');
            $table->string('code', 60)->unique();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('record_kind', 20)->default('record');
            $table->string('subject_type', 60)->nullable();
            $table->boolean('allow_anonymous')->default(false);
            $table->boolean('allow_offline')->default(true);
            $table->boolean('is_encrypted')->default(false);
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->auditColumns(userForeignKeys: false);
        });

        Schema::create('dc_form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('dc_forms');
            $table->integer('version_no');
            $table->string('status', 12)->default('draft');
            $table->json('compiled_json')->nullable();
            $table->foreignId('source_media_id')->nullable()->constrained('media_attachments')->nullOnDelete();
            $table->char('checksum', 64)->nullable();
            $table->text('change_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unique(['form_id', 'version_no']);
        });

        Schema::table('dc_forms', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('dc_form_versions')->nullOnDelete();
        });

        Schema::create('dc_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 160);
            $table->string('source_type', 12);
            $table->string('source_ref', 120)->nullable();
            $table->string('refresh_cron', 40)->nullable();
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamp('last_built_at')->nullable();
        });

        Schema::create('dc_choice_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_version_id')->nullable()->constrained('dc_form_versions')->nullOnDelete();
            $table->string('name', 80);
            $table->string('source_type', 12)->default('static');
            $table->foreignId('dataset_id')->nullable()->constrained('dc_datasets')->nullOnDelete();
            $table->unique(['form_version_id', 'name']);
        });

        Schema::create('dc_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('dc_choice_lists')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('label');
            $table->integer('position')->default(0);
            $table->string('parent_name', 100)->nullable();
            $table->json('filter_attrs')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media_attachments')->nullOnDelete();
            $table->string('concept_code', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['list_id', 'name']);
        });

        Schema::create('dc_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_version_id')->constrained('dc_form_versions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('dc_form_fields')->nullOnDelete();
            $table->string('field_type', 40);
            $table->string('name', 80);
            $table->text('label')->nullable();
            $table->text('hint')->nullable();
            $table->integer('position')->default(0);
            $table->foreignId('choice_list_id')->nullable()->constrained('dc_choice_lists')->nullOnDelete();
            $table->boolean('is_required')->default(false);
            $table->text('required_message')->nullable();
            $table->text('relevant_expr')->nullable();
            $table->text('constraint_expr')->nullable();
            $table->text('constraint_msg')->nullable();
            $table->text('calculation_expr')->nullable();
            $table->text('default_value')->nullable();
            $table->boolean('is_read_only')->default(false);
            $table->string('appearance', 120)->nullable();
            $table->json('parameters')->nullable();
            $table->text('repeat_count_expr')->nullable();
            $table->string('sensitivity', 12)->default('none');
            $table->boolean('is_identifier')->default(false);
            $table->unique(['form_version_id', 'name']);
            $table->index(['form_version_id', 'parent_id', 'position'], 'ix_dc_fields_version');
            $table->foreign('field_type')->references('code')->on('dc_field_types');
        });

        Schema::create('dc_translations', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 12);
            $table->unsignedBigInteger('entity_id');
            $table->string('lang', 8);
            $table->string('attribute', 20);
            $table->text('text');
            $table->unique(['entity_type', 'entity_id', 'lang', 'attribute']);
        });

        Schema::create('dc_form_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('dc_forms')->cascadeOnDelete();
            $table->string('grantee_type', 10);
            $table->unsignedBigInteger('grantee_id');
            $table->string('permission', 10);
            $table->unique(['form_id', 'grantee_type', 'grantee_id', 'permission']);
        });

        Schema::create('dc_dataset_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('dc_datasets')->cascadeOnDelete();
            $table->string('record_key', 80);
            $table->string('label');
            $table->json('attributes')->nullable();
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('row_version');
            $table->boolean('is_deleted')->default(false);
            $table->unique(['dataset_id', 'record_key']);
            $table->index(['dataset_id', 'row_version'], 'ix_dc_dsrec_delta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dc_dataset_records');
        Schema::dropIfExists('dc_form_access');
        Schema::dropIfExists('dc_translations');
        Schema::dropIfExists('dc_form_fields');
        Schema::dropIfExists('dc_choices');
        Schema::dropIfExists('dc_choice_lists');
        Schema::dropIfExists('dc_datasets');
        Schema::table('dc_forms', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('dc_form_versions');
        Schema::dropIfExists('dc_forms');
        Schema::dropIfExists('dc_field_types');
        Schema::dropIfExists('dc_projects');
    }
};
