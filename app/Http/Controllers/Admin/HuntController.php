<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hunt;
use App\Models\HuntSuggestion;
use App\Models\Vocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HuntController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,published'],
        ]);

        $hunts = Hunt::query()
            ->withCount(['vocations', 'creatureEntries'])
            ->when($validated['name'] ?? null, fn (Builder $query, string $name) => $query->where('name', 'like', '%'.trim($name).'%'))
            ->when(isset($validated['status']), fn (Builder $query) => $query->where('is_published', $validated['status'] === 'published'))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();
        $suggestions = HuntSuggestion::query()
            ->where('status', 'pending')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.hunts.index', compact('hunts', 'suggestions', 'validated'));
    }

    public function create(): View
    {
        $hunt = new Hunt([
            'party_type' => 'solo',
            'minimum_party_size' => 2,
            'maximum_party_size' => 4,
        ]);
        $vocations = Vocation::query()->orderBy('display_order')->orderBy('name')->get();

        return view('admin.hunts.form', compact('hunt', 'vocations'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$attributes, $relations] = $this->validatedData($request);

        $hunt = DB::transaction(function () use ($attributes, $relations) {
            $hunt = Hunt::create($attributes);
            $this->syncRelations($hunt, $relations);

            return $hunt;
        });

        return redirect()->route('admin.hunts.edit', $hunt)->with('success', 'Hunt cadastrada. Revise os dados antes de publicar.');
    }

    public function edit(Hunt $hunt): View
    {
        $hunt->load(['vocations', 'creatureEntries', 'requirements', 'supplies', 'loots']);
        $vocations = Vocation::query()->orderBy('display_order')->orderBy('name')->get();

        return view('admin.hunts.form', compact('hunt', 'vocations'));
    }

    public function update(Request $request, Hunt $hunt): RedirectResponse
    {
        [$attributes, $relations] = $this->validatedData($request, $hunt);

        DB::transaction(function () use ($hunt, $attributes, $relations) {
            $hunt->update($attributes);
            $this->syncRelations($hunt, $relations);
        });

        return redirect()->route('admin.hunts.edit', $hunt)->with('success', 'Hunt atualizada.');
    }

    public function destroy(Hunt $hunt): RedirectResponse
    {
        $hunt->delete();

        return redirect()->route('admin.hunts.index')->with('success', 'Hunt removida do catálogo.');
    }

    public function reviewSuggestion(Request $request, HuntSuggestion $suggestion): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:reviewed,rejected'],
        ]);
        $suggestion->update(['status' => $validated['status']]);

        return redirect()->route('admin.hunts.index')->with('success', 'Sugestão atualizada sem modificar a hunt.');
    }

    private function validatedData(Request $request, ?Hunt $hunt = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'alpha_dash', 'max:255', Rule::unique('hunts', 'slug')->ignore($hunt?->id)],
            'level_min' => ['required', 'integer', 'min:1', 'max:2000'],
            'level_max' => ['nullable', 'integer', 'gte:level_min', 'max:2000'],
            'party_type' => ['required', 'in:solo,duo,team'],
            'minimum_party_size' => ['nullable', 'required_if:party_type,team', 'integer', 'min:2', 'max:10'],
            'maximum_party_size' => ['nullable', 'required_if:party_type,team', 'integer', 'gte:minimum_party_size', 'max:10'],
            'vocations' => ['nullable', 'array', 'max:10'],
            'vocations.*' => ['integer', 'distinct', 'exists:vocations,id'],
            'xp_min_per_hour' => ['nullable', 'integer', 'min:0'],
            'xp_max_per_hour' => ['nullable', 'integer', 'gte:xp_min_per_hour'],
            'profit_min_per_hour' => ['nullable', 'integer'],
            'profit_max_per_hour' => ['nullable', 'integer', 'gte:profit_min_per_hour'],
            'difficulty' => ['nullable', 'string', 'max:80'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'access_description' => ['nullable', 'string', 'max:3000'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'location_description' => ['nullable', 'string', 'max:5000'],
            'map_image_url' => ['nullable', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_published' => ['boolean'],
            'is_outdated' => ['boolean'],
            'outdated_reason' => ['nullable', 'string', 'max:2000'],
            'last_reviewed_at' => ['nullable', 'date', 'before_or_equal:now'],
            'creatures_json' => ['nullable', 'json'],
            'requirements_json' => ['nullable', 'json'],
            'supplies_json' => ['nullable', 'json'],
            'loots_json' => ['nullable', 'json'],
        ]);

        if ($request->boolean('is_published') && blank($validated['last_reviewed_at'] ?? null)) {
            throw ValidationException::withMessages([
                'last_reviewed_at' => 'Informe a data da revisão antes de publicar a hunt.',
            ]);
        }

        $relations = [
            'vocations' => array_map('intval', $validated['vocations'] ?? []),
            'creatures' => $this->decodeRows($validated['creatures_json'] ?? null, [
                'rows' => ['array', 'max:30'],
                'rows.*.name' => ['required', 'string', 'max:255'],
                'rows.*.image_url' => ['nullable', 'url', 'max:2048'],
                'rows.*.source_url' => ['nullable', 'url', 'max:2048'],
                'rows.*.notes' => ['nullable', 'string', 'max:2000'],
            ], 'creatures_json'),
            'requirements' => $this->decodeRows($validated['requirements_json'] ?? null, [
                'rows' => ['array', 'max:30'],
                'rows.*.type' => ['required', 'in:quest,level,item,access,other'],
                'rows.*.name' => ['required', 'string', 'max:255'],
                'rows.*.description' => ['nullable', 'string', 'max:2000'],
                'rows.*.url' => ['nullable', 'url', 'max:2048'],
            ], 'requirements_json'),
            'supplies' => $this->decodeRows($validated['supplies_json'] ?? null, [
                'rows' => ['array', 'max:30'],
                'rows.*.name' => ['required', 'string', 'max:255'],
                'rows.*.quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
                'rows.*.image_url' => ['nullable', 'url', 'max:2048'],
                'rows.*.notes' => ['nullable', 'string', 'max:1000'],
            ], 'supplies_json'),
            'loots' => $this->decodeRows($validated['loots_json'] ?? null, [
                'rows' => ['array', 'max:30'],
                'rows.*.name' => ['required', 'string', 'max:255'],
                'rows.*.image_url' => ['nullable', 'url', 'max:2048'],
                'rows.*.estimated_value' => ['nullable', 'integer', 'min:0'],
                'rows.*.notes' => ['nullable', 'string', 'max:1000'],
            ], 'loots_json'),
        ];

        $attributes = collect($validated)->except([
            'vocations', 'creatures_json', 'requirements_json', 'supplies_json', 'loots_json',
        ])->all();
        $attributes['slug'] = filled($attributes['slug'] ?? null)
            ? Str::slug($attributes['slug'])
            : Str::slug($attributes['name']);
        $attributes['party_type'] = $validated['party_type'];
        $attributes['mode'] = $validated['party_type'];
        $attributes['is_published'] = $request->boolean('is_published');
        $attributes['published_at'] = $attributes['is_published'] ? ($hunt?->published_at ?? now()) : null;
        $attributes['is_outdated'] = $request->boolean('is_outdated');

        if ($validated['party_type'] !== 'team') {
            $attributes['minimum_party_size'] = null;
            $attributes['maximum_party_size'] = null;
        }

        if ($attributes['is_outdated'] && empty($attributes['outdated_reason'])) {
            $attributes['outdated_reason'] = 'Dados marcados para revisão pela administração.';
        }
        if (!$attributes['is_outdated']) {
            $attributes['outdated_reason'] = null;
        }

        return [$attributes, $relations];
    }

    private function decodeRows(?string $json, array $rules, string $field): array
    {
        if (blank($json)) {
            return [];
        }

        $rows = json_decode($json, true);
        if (!is_array($rows) || !array_is_list($rows)) {
            throw ValidationException::withMessages([
                $field => 'Informe uma lista JSON válida.',
            ]);
        }

        Validator::make(['rows' => $rows], $rules)->validate();

        return array_map(fn (array $row): array => array_filter($row, fn ($value) => $value !== '' && $value !== null), $rows);
    }

    private function syncRelations(Hunt $hunt, array $relations): void
    {
        $hunt->vocations()->sync($relations['vocations']);

        foreach (['creatureEntries' => 'creatures', 'requirements' => 'requirements', 'supplies' => 'supplies', 'loots' => 'loots'] as $relation => $input) {
            $hunt->{$relation}()->delete();
            $hunt->{$relation}()->createMany(array_map(
                fn (int $index, array $row): array => $row + ['display_order' => $index],
                array_keys($relations[$input]),
                $relations[$input],
            ));
        }
    }
}