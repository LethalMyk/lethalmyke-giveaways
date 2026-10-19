<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twitch_broadcaster_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('twitch_id')->unique();
            $table->string('login');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('viewer_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('lifetime')->default(0);
            $table->unsignedBigInteger('minutes')->default(0);
            $table->timestamp('last_awarded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('description', 300)->nullable();
            $table->unsignedInteger('cost');
            $table->unsignedInteger('stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reward_name', 80);
            $table->unsignedInteger('cost');
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('viewer_points');
        Schema::dropIfExists('twitch_broadcaster_tokens');
    }
};
