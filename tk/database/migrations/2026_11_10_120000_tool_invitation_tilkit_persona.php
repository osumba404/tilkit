<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_invitations', function (Blueprint $table) {
            $table->string('tilkit_persona', 40)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('tool_invitations', function (Blueprint $table) {
            $table->dropColumn('tilkit_persona');
        });
    }
};
