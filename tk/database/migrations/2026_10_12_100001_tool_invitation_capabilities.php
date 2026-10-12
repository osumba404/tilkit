<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_invitations', function (Blueprint $table) {
            $table->boolean('can_export')->nullable()->after('role');
            $table->boolean('can_publish_forms')->nullable()->after('can_export');
        });
    }

    public function down(): void
    {
        Schema::table('tool_invitations', function (Blueprint $table) {
            $table->dropColumn(['can_export', 'can_publish_forms']);
        });
    }
};
