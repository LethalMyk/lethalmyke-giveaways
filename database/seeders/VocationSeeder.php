<?php

namespace Database\Seeders;

use App\Models\Vocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VocationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Knight', 'Paladin', 'Sorcerer', 'Druid', 'Monk'] as $order => $name) {
            Vocation::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'display_order' => $order],
            );
        }
    }
}