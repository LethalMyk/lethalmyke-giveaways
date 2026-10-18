<?php

namespace Database\Seeders;

use App\Models\Hunt;
use App\Models\Monster;
use App\Models\Vocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HuntCreatureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (glob(database_path('data/hunts/*.json')) as $file) {
            $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);

            DB::transaction(function () use ($data): void {
                $hunt = Hunt::query()->where('slug', $data['hunt_slug'])->first();

                if (! $hunt && isset($data['hunt'])) {
                    $hunt = Hunt::query()->create($data['hunt'] + [
                        'slug' => $data['hunt_slug'],
                        'is_published' => true,
                        'published_at' => now(),
                        'last_reviewed_at' => now(),
                    ]);
                }

                if ($hunt && ! empty($data['vocations'])) {
                    $ids = [];
                    foreach ($data['vocations'] as $order => $name) {
                        $ids[] = Vocation::query()->firstOrCreate(
                            ['slug' => Str::slug($name)],
                            ['name' => $name, 'is_active' => true, 'display_order' => $order + 1],
                        )->id;
                    }
                    $hunt->vocations()->syncWithoutDetaching($ids);
                }

                if ($hunt) {
                    foreach ($data['loots'] ?? [] as $order => $loot) {
                        $hunt->loots()->updateOrCreate(
                            ['name' => $loot['name']],
                            ['image_url' => $loot['image_url'] ?? null, 'display_order' => $loot['display_order'] ?? $order + 1],
                        );
                    }
                }

                foreach ($data['creatures'] as $order => $creature) {
                    $slug = Str::slug($creature['name']);

                    Monster::query()->updateOrCreate(['slug' => $slug], [
                        'name' => $creature['name'],
                        'health' => $creature['health'],
                        'resistances' => $creature['resistances'],
                        'source_url' => $creature['source_url'],
                        'source_version' => $data['source_version'],
                        'source_updated_at' => now(),
                    ]);

                    $entry = ['source_url' => $creature['source_url'], 'spawn_count' => $creature['spawn_count'] ?? null, 'display_order' => $order + 1];
                    if (! empty($creature['image_url'])) {
                        $entry['image_url'] = $creature['image_url'];
                    }

                    $hunt?->creatureEntries()->updateOrCreate(['name' => $creature['name']], $entry);
                }
            });
        }
    }
}
