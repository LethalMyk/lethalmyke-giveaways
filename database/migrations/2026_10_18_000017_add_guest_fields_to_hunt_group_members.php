<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hunt_group_members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('character_id')->nullable()->change();
            $table->string('guest_name', 40)->nullable();
            $table->string('guest_vocation', 30)->nullable();
            $table->unsignedSmallInteger('guest_level')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hunt_group_members', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_vocation', 'guest_level']);
        });
    }
};
