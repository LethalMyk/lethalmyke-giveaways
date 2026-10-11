<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('twitch_id')->nullable()->unique();
            $table->string('twitch_username')->nullable();
            $table->string('twitch_avatar', 2048)->nullable();

            // O login será feito pela Twitch.
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['twitch_id']);
            $table->dropColumn([
                'twitch_id',
                'twitch_username',
                'twitch_avatar',
            ]);

            $table->string('email')->nullable(false)->change();
        });
    }
};