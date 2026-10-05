<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('dc_projects')->cascadeOnDelete();
            $table->string('email', 190);
            $table->string('role', 20)->default('collector');
            $table->uuid('token')->unique();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['project_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_invitations');
    }
};
