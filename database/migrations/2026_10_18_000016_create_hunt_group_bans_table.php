<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hunt_group_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunt_group_id')->constrained('hunt_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['hunt_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hunt_group_bans');
    }
};
