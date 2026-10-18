<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VocationController extends Controller
{
    public function index(): View
    {
        $vocations = Vocation::query()->withCount('hunts')->orderBy('display_order')->orderBy('name')->get();

        return view('admin.vocations.index', compact('vocations'));
    }

    public function create(): View
    {
        $vocation = new Vocation(['is_active' => true]);

        return view('admin.vocations.form', compact('vocation'));
    }

    public function store(Request $request): RedirectResponse
    {
        Vocation::create($this->validatedData($request));

        return redirect()->route('admin.vocations.index')->with('success', 'Vocação cadastrada.');
    }

    public function edit(Vocation $vocation): View
    {
        return view('admin.vocations.form', compact('vocation'));
    }

    public function update(Request $request, Vocation $vocation): RedirectResponse
    {
        $vocation->update($this->validatedData($request, $vocation));

        return redirect()->route('admin.vocations.index')->with('success', 'Vocação atualizada.');
    }

    public function destroy(Vocation $vocation): RedirectResponse
    {
        $vocation->delete();

        return redirect()->route('admin.vocations.index')->with('success', 'Vocação removida.');
    }

    private function validatedData(Request $request, ?Vocation $vocation = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('vocations', 'slug')->ignore($vocation?->id)],
            'display_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
        ]);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}