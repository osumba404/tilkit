<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dc_projects', function (Blueprint $table) {
            $table->string('data_controller_name', 200)->nullable()->after('lawful_basis');
            $table->string('data_controller_email', 190)->nullable()->after('data_controller_name');
            $table->string('data_protection_contact_email', 190)->nullable()->after('data_controller_email');
            $table->string('odpc_registration_number', 80)->nullable()->after('data_protection_contact_email');
            $table->string('privacy_policy_url', 500)->nullable()->after('odpc_registration_number');
            $table->text('personal_data_notice')->nullable()->after('privacy_policy_url');
            $table->text('retention_policy_summary')->nullable()->after('retention_months');
            $table->boolean('cross_border_transfers_allowed')->default(false)->after('retention_policy_summary');
            $table->text('cross_border_safeguards')->nullable()->after('cross_border_transfers_allowed');
        });

        Schema::table('tilkit_indicator_records', function (Blueprint $table) {
            $table->boolean('consent_to_share')->nullable()->after('consent_credit');
            $table->timestamp('consent_withdrawn_at')->nullable()->after('consent_to_share');
            $table->boolean('processing_restricted')->default(false)->after('consent_withdrawn_at');
            $table->timestamp('anonymized_at')->nullable()->after('processing_restricted');
        });

        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->string('request_type', 20);
            $table->string('requester_name', 200);
            $table->string('requester_email', 190);
            $table->string('requester_phone', 40)->nullable();
            $table->string('record_reference', 120)->nullable();
            $table->text('details')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_subject_requests');

        Schema::table('tilkit_indicator_records', function (Blueprint $table) {
            $table->dropColumn([
                'consent_to_share',
                'consent_withdrawn_at',
                'processing_restricted',
                'anonymized_at',
            ]);
        });

        Schema::table('dc_projects', function (Blueprint $table) {
            $table->dropColumn([
                'data_controller_name',
                'data_controller_email',
                'data_protection_contact_email',
                'odpc_registration_number',
                'privacy_policy_url',
                'personal_data_notice',
                'retention_policy_summary',
                'cross_border_transfers_allowed',
                'cross_border_safeguards',
            ]);
        });
    }
};
