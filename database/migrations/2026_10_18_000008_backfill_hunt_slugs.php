<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('hunts')
            ->whereNull('slug')
            ->orWhere('slug', '')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $hunt): void {
                $base = Str::slug($hunt->name) ?: 'hunt-'.$hunt->id;
                $slug = $base;
                $suffix = 2;

                while (DB::table('hunts')->where('slug', $slug)->where('id', '!=', $hunt->id)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }

                DB::table('hunts')->where('id', $hunt->id)->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
    }
};