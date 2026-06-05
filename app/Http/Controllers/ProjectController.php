<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'statut']);

        $projects = Project::query()
            ->with('prospect:id,entreprise,localite')
            ->withCount(['tasks', 'tasks as tasks_faites_count' => fn ($q) => $q->where('statut', 'fait')])
            ->when($filters['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) =>
                $w->where('titre', 'like', "%{$v}%")
                  ->orWhereHas('prospect', fn ($p) => $p->where('entreprise', 'like', "%{$v}%"))))
            ->orderByRaw("CASE statut WHEN 'en_cours' THEN 0 WHEN 'recette' THEN 1
                WHEN 'cadrage' THEN 2 WHEN 'suspendu' THEN 3 WHEN 'livre' THEN 4 ELSE 5 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters'  => $filters,
            'statuts'  => Project::STATUTS,
            'stats'    => Project::selectRaw('statut, count(*) n')->groupBy('statut')->pluck('n', 'statut'),
        ]);
    }

    public function show(Project $project)
    {
        $project->load([
            'prospect:id,entreprise,localite,telephone,email',
            'tender:id,idweb,objet',
            'tasks',
        ]);

        return Inertia::render('Projects/Show', [
            'project'      => $project,
            'statuts'      => Project::STATUTS,
            'statutsTache' => ProjectTask::STATUTS,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'titre'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'statut'          => ['required', 'in:'.implode(',', array_keys(Project::STATUTS))],
            'budget'          => ['nullable', 'integer', 'min:0'],
            'date_debut'      => ['nullable', 'date'],
            'date_fin_prevue' => ['nullable', 'date'],
            'date_livraison'  => ['nullable', 'date'],
            'notes'           => ['nullable', 'string'],
        ]);

        $project->update($data);

        return back()->with('success', 'Projet mis à jour.');
    }

    public function storeTask(Request $request, Project $project)
    {
        $data = $request->validate([
            'titre'    => ['required', 'string', 'max:255'],
            'echeance' => ['nullable', 'date'],
        ]);

        $project->tasks()->create([
            'titre'    => $data['titre'],
            'echeance' => $data['echeance'] ?? null,
            'ordre'    => (int) $project->tasks()->max('ordre') + 1,
        ]);

        return back(fallback: route('projects.show', $project));
    }

    public function updateTask(Request $request, ProjectTask $task)
    {
        $data = $request->validate([
            'titre'    => ['sometimes', 'required', 'string', 'max:255'],
            'statut'   => ['sometimes', 'required', 'in:'.implode(',', array_keys(ProjectTask::STATUTS))],
            'echeance' => ['sometimes', 'nullable', 'date'],
        ]);

        $task->update($data);

        return back(fallback: route('projects.show', $task->project_id));
    }

    public function destroyTask(ProjectTask $task)
    {
        $project = $task->project_id;
        $task->delete();

        return back(fallback: route('projects.show', $project));
    }
}
