<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('user_type', 12)->default('staff');
            $table->string('person_type', 60)->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('name', 160);
            $table->string('username', 60)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('status', 12)->default('pending');
            $table->boolean('mfa_enabled')->default(false);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('password_changed_at')->nullable();
            $table->smallInteger('failed_login_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('locale', 8)->default('en');
            $table->string('timezone', 40)->default('Africa/Nairobi');
            $table->rememberToken();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->index(['person_type', 'person_id'], 'ix_users_person');
            $table->index(['status', 'user_type'], 'ix_users_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_user_type_check CHECK (user_type IN ('student','staff','alumni','external','system'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('pending','active','suspended','locked','deactivated'))");
            DB::statement('CREATE UNIQUE INDEX ux_users_email ON users (lower(email)) WHERE email IS NOT NULL AND deleted_at IS NULL');
            DB::statement('CREATE UNIQUE INDEX ux_users_username ON users (lower(username)) WHERE username IS NOT NULL AND deleted_at IS NULL');
            DB::statement('CREATE UNIQUE INDEX ux_users_phone ON users (phone) WHERE phone IS NOT NULL AND deleted_at IS NULL');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email', 'ux_users_email');
                $table->unique('username', 'ux_users_username');
                $table->unique('phone', 'ux_users_phone');
            });
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
