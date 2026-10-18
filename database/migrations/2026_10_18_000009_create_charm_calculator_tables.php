<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monsters', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('health');
            $table->json('resistances')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('source_version')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamps();
            $table->index('name');
        });

        Schema::create('charm_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category', 40);
            $table->string('effect_type', 60);
            $table->string('element', 20)->nullable();
            $table->text('description')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('source_version')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['category', 'effect_type']);
        });

        Schema::create('charm_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charm_definition_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tier');
            $table->decimal('activation_chance', 7, 6)->nullable();
            $table->decimal('effect_value', 8, 6)->nullable();
            $table->unsignedInteger('cost')->nullable();
            $table->json('effect_config')->nullable();
            $table->timestamps();
            $table->unique(['charm_definition_id', 'tier']);
        });

        Schema::create('charm_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->json('configuration');
            $table->string('share_token', 64)->nullable()->unique();
            $table->timestamps();
            $table->index(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charm_presets');
        Schema::dropIfExists('charm_tiers');
        Schema::dropIfExists('charm_definitions');
        Schema::dropIfExists('monsters');
    }
};
