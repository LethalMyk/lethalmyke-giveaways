<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hunt_creatures', function (Blueprint $table) {
            $table->unsignedInteger('spawn_count')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('hunt_creatures', function (Blueprint $table) {
            $table->dropColumn('spawn_count');
        });
    }
};
