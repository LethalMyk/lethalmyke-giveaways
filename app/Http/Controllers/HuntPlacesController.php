<?php

namespace App\Http\Controllers;

use App\Models\Hunt;
use App\Models\HuntSuggestion;
use App\Models\Vocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HuntPlacesController extends Controller
{
    public function index(Request $request): View
    {
        $filters = Validator::make($request->query(), [
            'name' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'party_type' => ['nullable', 'in:solo,duo,team'],
            'party_size' => ['nullable', 'integer', 'min:2', 'max:10'],
            'vocation' => ['nullable', 'exists:vocations,slug'],
            'show_outdated' => ['sometimes', 'boolean'],
            'sort' => ['nullable', 'in:xp,profit,popular,recent'],
        ])->validate();

        $query = Hunt::query()
            ->published()
            ->with(['vocations', 'creatureEntries'])
            ->withCount('favorites');

        if (auth()->check()) {
            $query->with(['favorites' => fn ($relation) => $relation->where('users.id', auth()->id())]);
        }

        if (!empty($filters['name'])) {
            $name = trim($filters['name']);
            $query->where(function ($builder) use ($name) {
                $builder->where('hunts.name', 'like', '%'.$name.'%')
                    ->orWhereHas('creatureEntries', fn ($creatures) => $creatures->where('name', 'like', '%'.$name.'%'));
            });
        }

        if (!empty($filters['level'])) {
            $level = (int) $filters['level'];
            $query->whereNotNull('level_min')
                ->where('level_min', '<=', $level)
                ->where(fn ($builder) => $builder->whereNull('level_max')->orWhere('level_max', '>=', $level));
        }

        if (!empty($filters['party_type'])) {
            $query->where('party_type', $filters['party_type']);
        }

        if (($filters['party_type'] ?? null) === 'team' && !empty($filters['party_size'])) {
            $partySize = (int) $filters['party_size'];
            $query->where(fn ($builder) => $builder->whereNull('minimum_party_size')->orWhere('minimum_party_size', '<=', $partySize))
                ->where(fn ($builder) => $builder->whereNull('maximum_party_size')->orWhere('maximum_party_size', '>=', $partySize));
        }

        if (!empty($filters['vocation'])) {
            $query->whereHas('vocations', fn ($builder) => $builder->where('vocations.slug', $filters['vocation']));
        }

        if (!($filters['show_outdated'] ?? false)) {
            $query->where('is_outdated', false)
                ->whereNotNull('last_reviewed_at')
                ->where('last_reviewed_at', '>=', now()->subDays(config('hunt_places.review_stale_days')));
        }

        match ($filters['sort'] ?? 'xp') {
            'profit' => $query->orderByRaw('COALESCE(profit_max_per_hour, profit_per_hour) DESC'),
            'popular' => $query->orderByDesc('views_count'),
            'recent' => $query->orderByDesc('published_at')->orderByDesc('updated_at'),
            default => $query->orderByRaw('COALESCE(xp_max_per_hour, xp_per_hour) DESC'),
        };

        $hunts = $query->orderBy('name')
            ->paginate(config('hunt_places.per_page'))
            ->withQueryString();
        $vocations = Vocation::query()->where('is_active', true)->orderBy('display_order')->orderBy('name')->get();

        return view('tools.hunt-places.index', compact('hunts', 'vocations', 'filters'));
    }

    public function show(Request $request, Hunt $hunt): View
    {
        abort_unless($hunt->is_published, 404);

        $hunt->load(['vocations', 'creatureEntries', 'requirements', 'supplies', 'loots']);
        if (auth()->check()) {
            $hunt->load(['favorites' => fn ($relation) => $relation->where('users.id', auth()->id())]);
        }

        $viewedHunts = $request->session()->get('viewed_hunts', []);
        if (!in_array($hunt->id, $viewedHunts, true)) {
            $hunt->increment('views_count');
            $viewedHunts[] = $hunt->id;
            $request->session()->put('viewed_hunts', array_slice($viewedHunts, -100));
        }

        return view('tools.hunt-places.show', compact('hunt'));
    }

    public function toggleFavorite(Request $request, Hunt $hunt): RedirectResponse
    {
        abort_unless($hunt->is_published, 404);

        $favorite = $hunt->favorites()->where('users.id', $request->user()->id)->exists();
        if ($favorite) {
            $hunt->favorites()->detach($request->user()->id);
        } else {
            $hunt->favorites()->attach($request->user()->id);
        }

        return back()->with('success', $favorite ? 'Hunt removida dos favoritos.' : 'Hunt adicionada aos favoritos.');
    }

    public function suggest(Request $request, Hunt $hunt): RedirectResponse
    {
        abort_unless($hunt->is_published, 404);

        $validated = $request->validate([
            'submitter_name' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        HuntSuggestion::create([
            'hunt_id' => $hunt->id,
            'hunt_name' => $hunt->name,
            'submitter_name' => $validated['submitter_name'] ?? null,
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Sugestão enviada para análise da equipe.');
    }
}