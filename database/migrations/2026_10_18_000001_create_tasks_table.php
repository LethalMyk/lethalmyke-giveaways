<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('difficulty')->nullable();
            $table->string('category')->nullable();
            $table->string('creatures')->nullable();
            $table->unsignedInteger('required_quantity')->default(0);
            $table->text('requirements')->nullable();
            $table->text('rewards')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['difficulty', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
