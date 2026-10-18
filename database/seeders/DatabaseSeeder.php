<?php

namespace Database\Seeders;

use App\Models\Charm;
use App\Models\Hunt;
use App\Models\Imbuement;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        Task::query()->create([
            'name' => 'Apex Predator',
            'description' => 'Elimine as criaturas da rota da fazenda para cumprir os objetivos da task.',
            'difficulty' => 'Médio',
            'category' => 'Hunt',
            'creatures' => 'Giant Spider, Dwarf',
            'required_quantity' => 20,
            'requirements' => 'Nível 80+',
            'rewards' => 'Gold e experiência',
            'is_active' => true,
        ]);

        Hunt::query()->create([
            'name' => 'Demon Jungle',
            'level_min' => 60,
            'level_max' => 100,
            'vocation' => 'Any',
            'mode' => 'solo',
            'creatures' => 'Demons and hybrids',
            'xp_per_hour' => 220000,
            'profit_per_hour' => 3500,
            'difficulty' => 'Médio',
            'notes' => 'Ajuste a rota conforme sua vocação e disponibilidade de potions.',
        ]);

        Charm::query()->create([
            'name' => 'Vicious Hunger',
            'cost' => 40,
            'group_name' => 'Combat',
            'description' => 'Exemplo demonstrativo de charm.',
        ]);

        Charm::query()->create([
            'name' => 'Double Strike',
            'cost' => 25,
            'group_name' => 'Combat',
            'description' => 'Exemplo demonstrativo de charm.',
        ]);

        Imbuement::query()->create([
            'name' => 'Strike',
            'level' => 'I',
            'materials' => [
                ['name' => 'Crystal', 'quantity' => 2, 'unit_price' => 150],
                ['name' => 'Ore', 'quantity' => 1, 'unit_price' => 250],
            ],
        ]);
    }
}
