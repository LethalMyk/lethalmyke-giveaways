<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('cost');
            $table->string('group_name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('group_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charms');
    }
};
