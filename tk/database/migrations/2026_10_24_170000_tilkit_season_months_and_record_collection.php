<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tilkit_seasons', function (Blueprint $table) {
            $table->unsignedTinyInteger('start_month')->nullable()->after('label');
            $table->unsignedTinyInteger('end_month')->nullable()->after('start_month');
        });

        Schema::table('tilkit_indicator_records', function (Blueprint $table) {
            $table->date('collection_date')->nullable()->after('protocol_version');
            $table->unsignedSmallInteger('collection_year')->nullable()->after('collection_date');
        });
    }

    public function down(): void
    {
        Schema::table('tilkit_indicator_records', function (Blueprint $table) {
            $table->dropColumn(['collection_date', 'collection_year']);
        });

        Schema::table('tilkit_seasons', function (Blueprint $table) {
            $table->dropColumn(['start_month', 'end_month']);
        });
    }
};
