<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Charm;
use App\Models\Hunt;
use App\Models\Imbuement;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    private const CATALOGS = [
        'tasks' => [
            'model' => Task::class,
            'title' => 'Map Tasks',
            'fields' => [
                'name' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|string|max:255'],
                'description' => ['label' => 'Descrição', 'type' => 'textarea', 'rules' => 'nullable|string|max:5000'],
                'difficulty' => ['label' => 'Dificuldade', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                'category' => ['label' => 'Categoria', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                'creatures' => ['label' => 'Criaturas', 'type' => 'text', 'rules' => 'nullable|string|max:1000'],
                'required_quantity' => ['label' => 'Quantidade necessária', 'type' => 'number', 'rules' => 'required|integer|min:0'],
                'requirements' => ['label' => 'Requisitos', 'type' => 'textarea', 'rules' => 'nullable|string|max:5000'],
                'rewards' => ['label' => 'Recompensas', 'type' => 'textarea', 'rules' => 'nullable|string|max:5000'],
                'is_active' => ['label' => 'Ativa', 'type' => 'checkbox', 'rules' => 'boolean'],
            ],
        ],
        'hunts' => [
            'model' => Hunt::class,
            'title' => 'Hunt Places',
            'fields' => [
                'name' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|string|max:255'],
                'level_min' => ['label' => 'Level mínimo', 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
                'level_max' => ['label' => 'Level máximo', 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
                'vocation' => ['label' => 'Vocação', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                'mode' => ['label' => 'Modo', 'type' => 'text', 'rules' => 'required|string|max:100'],
                'creatures' => ['label' => 'Criaturas', 'type' => 'text', 'rules' => 'nullable|string|max:1000'],
                'xp_per_hour' => ['label' => 'XP por hora', 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
                'profit_per_hour' => ['label' => 'Lucro por hora', 'type' => 'number', 'rules' => 'nullable|integer'],
                'difficulty' => ['label' => 'Dificuldade', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                'notes' => ['label' => 'Observações', 'type' => 'textarea', 'rules' => 'nullable|string|max:5000'],
            ],
        ],
        'charms' => [
            'model' => Charm::class,
            'title' => 'Charms',
            'fields' => [
                'name' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|string|max:255'],
                'cost' => ['label' => 'Custo em pontos', 'type' => 'number', 'rules' => 'required|integer|min:0'],
                'group_name' => ['label' => 'Grupo', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
                'description' => ['label' => 'Descrição', 'type' => 'textarea', 'rules' => 'nullable|string|max:5000'],
            ],
        ],
        'imbuements' => [
            'model' => Imbuement::class,
            'title' => 'Imbuements',
            'fields' => [
                'name' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|string|max:255'],
                'level' => ['label' => 'Nível', 'type' => 'text', 'rules' => 'required|string|max:100'],
                'materials' => ['label' => 'Materiais (JSON)', 'type' => 'json', 'rules' => 'nullable|json'],
            ],
        ],
    ];

    public function index(string $catalog): View
    {
        $definition = $this->definition($catalog);
        $model = $definition['model'];
        $items = $model::query()->orderBy('name')->paginate(15);

        return view('admin.catalogs.index', compact('catalog', 'definition', 'items'));
    }

    public function create(string $catalog): View
    {
        $definition = $this->definition($catalog);
        $item = null;

        return view('admin.catalogs.form', compact('catalog', 'definition', 'item'));
    }

    public function store(Request $request, string $catalog): RedirectResponse
    {
        $definition = $this->definition($catalog);
        $data = $this->validatedData($request, $definition);
        $model = $definition['model'];
        $model::create($data);

        return redirect()->route('admin.catalogs.index', $catalog)
            ->with('success', $definition['title'].' cadastrado com sucesso.');
    }

    public function edit(string $catalog, int $item): View
    {
        $definition = $this->definition($catalog);
        $model = $definition['model'];
        $item = $model::query()->findOrFail($item);

        return view('admin.catalogs.form', compact('catalog', 'definition', 'item'));
    }

    public function update(Request $request, string $catalog, int $item): RedirectResponse
    {
        $definition = $this->definition($catalog);
        $model = $definition['model'];
        $record = $model::query()->findOrFail($item);
        $record->update($this->validatedData($request, $definition));

        return redirect()->route('admin.catalogs.index', $catalog)
            ->with('success', $definition['title'].' atualizado com sucesso.');
    }

    public function destroy(string $catalog, int $item): RedirectResponse
    {
        $definition = $this->definition($catalog);
        $model = $definition['model'];
        $record = $model::query()->findOrFail($item);
        $record->delete();

        return redirect()->route('admin.catalogs.index', $catalog)
            ->with('success', $definition['title'].' excluído com sucesso.');
    }

    private function definition(string $catalog): array
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);

        return self::CATALOGS[$catalog];
    }

    private function validatedData(Request $request, array $definition): array
    {
        $rules = [];
        foreach ($definition['fields'] as $name => $field) {
            $rules[$name] = $field['rules'];
        }

        $data = $request->validate($rules);

        if (isset($definition['fields']['is_active'])) {
            $data['is_active'] = $request->boolean('is_active');
        }

        if (isset($definition['fields']['materials'])) {
            $data['materials'] = isset($data['materials']) ? json_decode($data['materials'], true) : null;
        }

        return $data;
    }
}