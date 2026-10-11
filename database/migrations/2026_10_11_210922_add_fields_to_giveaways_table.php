<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('giveaways', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('draft');

            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('giveaways', function (Blueprint $table) {
            $table->dropIndex(['status', 'ends_at']);
            $table->dropConstrainedForeignId('created_by');

            $table->dropColumn([
                'title',
                'description',
                'ends_at',
                'status',
            ]);
        });
    }
};

