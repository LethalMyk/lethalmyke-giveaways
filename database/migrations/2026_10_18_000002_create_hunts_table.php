<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hunts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('level_min')->nullable();
            $table->unsignedInteger('level_max')->nullable();
            $table->string('vocation')->nullable();
            $table->string('mode')->default('solo');
            $table->string('creatures')->nullable();
            $table->unsignedInteger('xp_per_hour')->nullable();
            $table->integer('profit_per_hour')->nullable();
            $table->string('difficulty')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['difficulty', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hunts');
    }
};
