<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hunts', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->string('party_type')->default('solo')->index();
            $table->unsignedTinyInteger('minimum_party_size')->nullable();
            $table->unsignedTinyInteger('maximum_party_size')->nullable();
            $table->unsignedBigInteger('xp_min_per_hour')->nullable();
            $table->unsignedBigInteger('xp_max_per_hour')->nullable();
            $table->bigInteger('profit_min_per_hour')->nullable();
            $table->bigInteger('profit_max_per_hour')->nullable();
            $table->string('cover_image_url', 2048)->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->text('access_description')->nullable();
            $table->string('location_name')->nullable();
            $table->text('location_description')->nullable();
            $table->string('map_image_url', 2048)->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_outdated')->default(false)->index();
            $table->text('outdated_reason')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
        });

        Schema::create('vocations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('hunt_vocation', function (Blueprint $table) {
            $table->foreignId('hunt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vocation_id')->constrained()->cascadeOnDelete();
            $table->primary(['hunt_id', 'vocation_id']);
        });

        Schema::create('hunt_creatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunt_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('image_url', 2048)->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['hunt_id', 'display_order']);
        });

        Schema::create('hunt_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunt_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->default('other');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('url', 2048)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['hunt_id', 'type']);
        });

        Schema::create('hunt_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunt_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('quantity')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['hunt_id', 'display_order']);
        });

        Schema::create('hunt_loots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunt_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('image_url', 2048)->nullable();
            $table->unsignedBigInteger('estimated_value')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['hunt_id', 'display_order']);
        });

        Schema::create('hunt_favorites', function (Blueprint $table) {
            $table->foreignId('hunt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['hunt_id', 'user_id']);
        });

        Schema::create('hunt_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunt_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hunt_name');
            $table->string('submitter_name', 100)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hunt_suggestions');
        Schema::dropIfExists('hunt_favorites');
        Schema::dropIfExists('hunt_loots');
        Schema::dropIfExists('hunt_supplies');
        Schema::dropIfExists('hunt_requirements');
        Schema::dropIfExists('hunt_creatures');
        Schema::dropIfExists('hunt_vocation');
        Schema::dropIfExists('vocations');

        Schema::table('hunts', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['party_type']);
            $table->dropIndex(['is_published']);
            $table->dropIndex(['is_outdated']);
            $table->dropColumn([
                'slug',
                'party_type',
                'minimum_party_size',
                'maximum_party_size',
                'xp_min_per_hour',
                'xp_max_per_hour',
                'profit_min_per_hour',
                'profit_max_per_hour',
                'cover_image_url',
                'video_url',
                'access_description',
                'location_name',
                'location_description',
                'map_image_url',
                'is_published',
                'is_outdated',
                'outdated_reason',
                'last_reviewed_at',
                'published_at',
                'views_count',
            ]);
        });
    }
};