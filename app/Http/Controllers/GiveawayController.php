<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Giveaway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GiveawayController extends Controller
{
    public function index(): View
    {
        $giveaways = Giveaway::with('creator')
            ->latest()
            ->paginate(10);

        return view('admin.giveaways.index', compact('giveaways'));
    }

    public function create(): View
    {
        return view('admin.giveaways.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'ends_at' => ['nullable', 'date', 'after:now'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $validated['created_by'] = $request->user()->id;

        Giveaway::create($validated);

        return redirect()
            ->route('admin.giveaways.index')
            ->with('success', 'Sorteio cadastrado com sucesso!');
    }

    public function edit(Giveaway $giveaway): View
    {
        return view('admin.giveaways.edit', compact('giveaway'));
    }

    public function update(Request $request, Giveaway $giveaway): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'ends_at' => ['nullable', 'date', 'after:now'],
            'status' => ['required', 'in:draft,published,ended'],
        ]);

        $giveaway->update($validated);

        return redirect()
            ->route('admin.giveaways.index')
            ->with('success', 'Sorteio atualizado com sucesso!');
    }

    public function destroy(Giveaway $giveaway): RedirectResponse
    {
        $giveaway->delete();

        return redirect()
            ->route('admin.giveaways.index')
            ->with('success', 'Sorteio excluído com sucesso!');
    }
}
