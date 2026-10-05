<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->timestamp('occurred_at')->useCurrent();
            $table->string('actor_type', 10)->default('user');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 30);
            $table->string('auditable_type', 60)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('module', 40)->nullable();
            $table->uuid('request_id')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('http_method', 8)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->uuid('device_uuid')->nullable();
            $table->string('tags', 120)->nullable();
            $table->text('note')->nullable();
            $table->index(['auditable_type', 'auditable_id', 'occurred_at'], 'ix_audit_auditable');
            $table->index(['user_id', 'occurred_at'], 'ix_audit_user');
            $table->index(['event', 'occurred_at'], 'ix_audit_event');
            $table->index('request_id', 'ix_audit_request');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE FUNCTION audit_logs_block_change() RETURNS trigger AS $$
BEGIN
  RAISE EXCEPTION 'audit_logs is append-only';
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_audit_logs_immutable
  BEFORE UPDATE OR DELETE ON audit_logs
  FOR EACH ROW EXECUTE FUNCTION audit_logs_block_change();
SQL);
        } elseif ($driver === 'mysql') {
            DB::unprepared('
CREATE TRIGGER trg_audit_logs_no_update BEFORE UPDATE ON audit_logs
FOR EACH ROW SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'audit_logs is append-only\';
CREATE TRIGGER trg_audit_logs_no_delete BEFORE DELETE ON audit_logs
FOR EACH ROW SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'audit_logs is append-only\';
            ');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_logs_immutable ON audit_logs');
            DB::unprepared('DROP FUNCTION IF EXISTS audit_logs_block_change()');
        } elseif ($driver === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_logs_no_update');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_logs_no_delete');
        }
        Schema::dropIfExists('audit_logs');
    }
};
