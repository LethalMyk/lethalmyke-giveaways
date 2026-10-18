<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charm_definitions', function (Blueprint $table) {
            $table->string('charm_type', 20)->default('major')->after('name');
        });

        Schema::table('charm_tiers', function (Blueprint $table) {
            $table->string('cost_currency', 40)->nullable()->after('cost');
        });
    }

    public function down(): void
    {
        Schema::table('charm_tiers', function (Blueprint $table) {
            $table->dropColumn('cost_currency');
        });

        Schema::table('charm_definitions', function (Blueprint $table) {
            $table->dropColumn('charm_type');
        });
    }
};
