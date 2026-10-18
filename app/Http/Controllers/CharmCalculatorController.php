<?php

namespace App\Http\Controllers;

use App\Models\Charm;
use App\Models\CharmDefinition;
use App\Models\CharmPreset;
use App\Models\CharmTier;
use App\Models\Hunt;
use App\Models\HuntCreature;
use App\Models\Monster;
use App\Services\CharmOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CharmCalculatorController extends Controller
{
    public function index(): View
    {
        return $this->calculatorView();
    }

    public function shared(string $shareToken): View
    {
        $preset = CharmPreset::query()
            ->where('share_token', $shareToken)
            ->firstOrFail();

        return $this->calculatorView($preset->configuration);
    }

    public function creatures(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'required_without:slugs', 'string', 'min:2', 'max:100'],
            'slugs' => ['required_without:q', 'array', 'min:1', 'max:20'],
            'slugs.*' => ['required', 'string', 'distinct', 'exists:monsters,slug'],
        ]);

        $query = Monster::query()->orderBy('name');
        $creatures = isset($validated['slugs'])
            ? $query->whereIn('slug', $validated['slugs'])->get(['slug', 'name', 'health', 'resistances', 'source_url'])
            : $query->where('name', 'like', '%'.$validated['q'].'%')
                ->limit(10)
                ->get(['slug', 'name', 'health', 'resistances', 'source_url']);

        $images = HuntCreature::query()
            ->whereIn('name', $creatures->pluck('name'))
            ->whereNotNull('image_url')
            ->pluck('image_url', 'name');

        return response()->json($creatures->map(fn ($monster) => [...$monster->toArray(), 'image_url' => $images->get($monster->name)]));
    }

    public function huntCreatures(Hunt $hunt): JsonResponse
    {
        abort_unless($hunt->is_published, 404);

        $entries = $hunt->creatureEntries()->get(['name', 'spawn_count', 'image_url'])
            ->sortByDesc(fn ($entry) => $entry->spawn_count ?? 0)
            ->values();
        $names = $entries->pluck('name');
        $monsters = Monster::query()
            ->whereIn('slug', $names->map(fn (string $name) => Str::slug($name)))
            ->get(['slug', 'name', 'health', 'resistances', 'source_url'])
            ->keyBy('slug');

        return response()->json([
            'monsters' => $entries
                ->map(fn ($entry) => ($monster = $monsters->get(Str::slug($entry->name)))
                    ? [...$monster->toArray(), 'spawn_count' => $entry->spawn_count, 'image_url' => $entry->image_url]
                    : null)
                ->filter()
                ->values(),
            'missing' => $names->reject(fn (string $name) => $monsters->has(Str::slug($name)))->values(),
        ]);
    }

    public function calculate(
        Request $request,
        CharmOptimizationService $optimizer,
    ): JsonResponse {
        $validated = $request->validate($this->configurationRules());
        $this->validateCharmTiers($validated['charms']);

        return response()->json($this->optimizeConfiguration($validated, $optimizer));
    }

    public function storePreset(Request $request, CharmOptimizationService $optimizer): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'configuration' => ['required', 'array:character,monsters,charms,criterion,inventory_confirmed'],
            ...$this->configurationRules('configuration.'),
        ]);
        abort_unless($validated['configuration']['inventory_confirmed'], 422, 'Confirme o inventário antes de salvar o preset.');
        $this->validateCharmTiers($validated['configuration']['charms'], 'configuration.');
        $configuration = $validated['configuration'];
        $configuration['hunt_preset'] = $this->optimizeConfiguration($configuration, $optimizer)['hunt_preset'];

        $preset = $request->user()->charmPresets()->create([
            'name' => $validated['name'],
            'configuration' => $configuration,
        ]);

        return response()->json([
            'preset' => ['id' => $preset->id, 'name' => $preset->name],
        ], 201);
    }

    public function updatePreset(Request $request, int $preset, CharmOptimizationService $optimizer): JsonResponse
    {
        $ownedPreset = $this->ownedPreset($request, $preset);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'configuration' => ['required', 'array:character,monsters,charms,criterion,inventory_confirmed'],
            ...$this->configurationRules('configuration.'),
        ]);
        abort_unless($validated['configuration']['inventory_confirmed'], 422, 'Confirme o inventário antes de salvar o preset.');
        $this->validateCharmTiers($validated['configuration']['charms'], 'configuration.');
        $configuration = $validated['configuration'];
        $configuration['hunt_preset'] = $this->optimizeConfiguration($configuration, $optimizer)['hunt_preset'];

        $ownedPreset->update([
            'name' => $validated['name'],
            'configuration' => $configuration,
        ]);

        return response()->json([
            'preset' => ['id' => $ownedPreset->id, 'name' => $ownedPreset->name],
        ]);
    }

    public function showPreset(Request $request, int $preset): JsonResponse
    {
        $ownedPreset = $this->ownedPreset($request, $preset);

        return response()->json([
            'preset' => [
                'id' => $ownedPreset->id,
                'name' => $ownedPreset->name,
                'configuration' => $ownedPreset->configuration,
            ],
        ]);
    }

    public function destroyPreset(Request $request, int $preset): JsonResponse
    {
        $this->ownedPreset($request, $preset)->delete();

        return response()->json(['deleted' => true]);
    }

    public function createShare(Request $request, int $preset): JsonResponse
    {
        $ownedPreset = $this->ownedPreset($request, $preset);
        $ownedPreset->update(['share_token' => $ownedPreset->share_token ?: Str::random(48)]);

        return response()->json([
            'url' => route('tools.charm-calculator.shared', $ownedPreset->share_token),
        ]);
    }

    public function deleteShare(Request $request, int $preset): JsonResponse
    {
        $this->ownedPreset($request, $preset)->update(['share_token' => null]);

        return response()->json(['revoked' => true]);
    }

    private function calculatorView(?array $sharedConfiguration = null): View
    {
        $charms = Charm::query()->orderBy('cost')->get();
        $charmDefinitions = CharmDefinition::query()
            ->where('is_active', true)
            ->with('tiers')
            ->orderBy('name')
            ->get();
        $sharedMonsterSlugs = collect($sharedConfiguration['monsters'] ?? [])
            ->pluck('monster_slug')
            ->filter()
            ->all();
        $monsterQuery = Monster::query()->orderBy('name');
        $monsters = $sharedMonsterSlugs
            ? $monsterQuery->whereIn('slug', $sharedMonsterSlugs)->get()
            : $monsterQuery->limit(20)->get();
        $presets = auth()->user()?->charmPresets()->orderBy('name')->get(['id', 'name']) ?? collect();
        $hunts = Hunt::query()
            ->published()
            ->whereHas('creatureEntries')
            ->withCount('creatureEntries')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('tools.charm-calculator', compact(
            'hunts',
            'charms',
            'charmDefinitions',
            'monsters',
            'presets',
            'sharedConfiguration',
        ));
    }

    private function ownedPreset(Request $request, int $id): CharmPreset
    {
        return CharmPreset::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    private function configurationRules(string $prefix = ''): array
    {
        $field = fn (string $name): string => $prefix.$name;

        return [
            $field('character') => ['required', 'array:hp,mana,account_type'],
            $field('character.hp') => ['required', 'integer', 'min:1', 'max:100000'],
            $field('character.mana') => ['required', 'integer', 'min:0', 'max:100000'],
            $field('character.account_type') => ['required', Rule::in(['free', 'premium', 'expansion'])],
            $field('monsters') => ['required', 'array', 'min:1', 'max:20'],
            $field('monsters.*.monster_slug') => ['required', 'string', 'distinct', 'exists:monsters,slug'],
            $field('monsters.*.kills') => ['required', 'integer', 'min:0', 'max:1000000'],
            $field('charms') => ['present', 'array', 'max:25'],
            $field('charms.*.charm_slug') => [
                'required',
                'string',
                'distinct',
                Rule::exists('charm_definitions', 'slug')->where('is_active', true),
            ],
            $field('charms.*.tier') => ['required', 'integer', 'between:1,3'],
            $field('criterion') => ['required', Rule::in(['individual', 'hunt'])],
            $field('inventory_confirmed') => ['required', 'accepted'],
        ];
    }

    private function validateCharmTiers(array $charms, string $prefix = ''): void
    {
        $availableTiers = CharmTier::query()
            ->whereHas('charm', fn ($query) => $query->whereIn('slug', collect($charms)->pluck('charm_slug')))
            ->with('charm:id,slug')
            ->get()
            ->mapWithKeys(fn (CharmTier $tier): array => [
                $tier->charm->slug.':'.$tier->tier => true,
            ]);

        foreach ($charms as $index => $selection) {
            if (! $availableTiers->has($selection['charm_slug'].':'.$selection['tier'])) {
                throw ValidationException::withMessages([
                    $prefix."charms.$index.tier" => 'O tier selecionado não está disponível para este charm.',
                ]);
            }
        }
    }

    private function optimizeConfiguration(array $configuration, CharmOptimizationService $optimizer): array
    {
        $monsterEntries = $configuration['monsters'];
        $monsters = Monster::query()
            ->whereIn('slug', collect($monsterEntries)->pluck('monster_slug'))
            ->get()
            ->keyBy('slug');
        abort_if($monsters->count() !== count($monsterEntries), 404);
        $entries = collect($monsterEntries)->map(fn (array $entry): array => [
            'monster' => $monsters->get($entry['monster_slug']),
            'kills' => $entry['kills'],
        ])->all();

        return $optimizer->optimize(
            $entries,
            $configuration['charms'],
            $configuration['criterion'],
            $configuration['character'],
        );
    }
}
