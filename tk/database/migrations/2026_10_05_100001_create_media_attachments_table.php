<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('attachable_type', 60)->nullable();
            $table->unsignedBigInteger('attachable_id')->nullable();
            $table->string('collection', 40)->default('default');
            $table->string('disk', 20)->default('private');
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->string('extension', 12)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->decimal('duration_sec', 10, 2)->nullable();
            $table->string('alt_text')->nullable();
            $table->string('caption', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('visibility', 10)->default('private');
            $table->boolean('is_encrypted')->default(false);
            $table->string('scan_status', 10)->default('pending');
            $table->json('metadata')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->auditColumns();
            $table->index(['attachable_type', 'attachable_id', 'collection', 'sort_order'], 'ix_media_attachable');
            $table->index('sha256', 'ix_media_sha256');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX ix_media_orphans ON media_attachments (created_at) WHERE attachable_id IS NULL');
        } else {
            Schema::table('media_attachments', function (Blueprint $table) {
                $table->index('created_at', 'ix_media_orphans');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_attachments');
    }
};
