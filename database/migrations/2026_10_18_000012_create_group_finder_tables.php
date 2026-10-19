<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('level');
            $table->string('vocation', 40);
            $table->string('world', 60);
            $table->string('source', 20)->default('manual');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('hunt_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('max_members');
            $table->unsignedSmallInteger('level_min')->nullable();
            $table->unsignedSmallInteger('level_max')->nullable();
            $table->json('wanted_vocations')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at');
            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('hunt_group_hunt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunt_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hunt_id')->constrained()->cascadeOnDelete();
            $table->unique(['hunt_group_id', 'hunt_id']);
        });

        Schema::create('hunt_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunt_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['hunt_group_id', 'user_id']);
            $table->unique(['hunt_group_id', 'character_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hunt_group_members');
        Schema::dropIfExists('hunt_group_hunt');
        Schema::dropIfExists('hunt_groups');
        Schema::dropIfExists('characters');
    }
};
