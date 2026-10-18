<?php

namespace App\Http\Controllers;

use App\Models\Charm;
use App\Models\Giveaway;
use App\Models\Hunt;
use App\Models\Imbuement;
use App\Models\Task;
use App\Models\UserTaskProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(): View
    {
        $giveaways = Giveaway::query()
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->get();

        $popularHunts = Hunt::query()
            ->published()
            ->latest('updated_at')
            ->limit(3)
            ->get();

        $links = [
            'twitch' => 'https://www.twitch.tv/lethalmyke',
            'youtube' => 'https://www.youtube.com/@lethalmyke',
            'discord' => 'https://discord.com',
        ];

        return view('home', compact('giveaways', 'popularHunts', 'links'));
    }

    public function mapTask(): View
    {
        $tasks = Task::query()
            ->where('is_active', true)
            ->with(['progress' => fn ($query) => $query->where('user_id', auth()->id())])
            ->orderBy('difficulty')
            ->orderBy('name')
            ->get();

        return view('tools.map-task', compact('tasks'));
    }

    public function updateTaskProgress(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->is_active, 404);

        $validated = $request->validate([
            'kills' => ['required', 'integer', 'min:0', 'max:'.$task->required_quantity],
        ]);

        $kills = (int) $validated['kills'];

        UserTaskProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'task_id' => $task->id],
            ['kills' => $kills, 'completed' => $kills >= $task->required_quantity],
        );

        return redirect()->route('tools.map-task')->with('success', 'Progresso da task atualizado.');
    }

    public function charmCalculator(): View
    {
        $charms = Charm::query()->orderBy('cost')->get();

        return view('tools.charm-calculator', compact('charms'));
    }

    public function imbuementCalculator(): View
    {
        $imbuements = Imbuement::query()->orderBy('level')->get();

        return view('tools.imbuement-calculator', compact('imbuements'));
    }
}
