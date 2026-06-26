<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Portail partenaire : un partenaire connecté consulte ses projets et transmet
 * les tâches à réaliser. Accès limité à ses propres projets/tâches.
 */
class PortailController extends Controller
{
    public function index(Request $request)
    {
        $partenaire = $request->user()->partenaire;

        $projects = $partenaire->projects()
            ->with('prospect:id,entreprise')
            ->orderByDesc('id')
            ->get(['id', 'partenaire_id', 'prospect_id', 'titre', 'statut']);

        $taches = ProjectTask::where('partenaire_id', $partenaire->id)
            ->with('project:id,titre')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Portail/Index', [
            'partenaire'   => $partenaire->only('id', 'nom'),
            'projects'     => $projects,
            'taches'       => $taches,
            'statutsTache' => ProjectTask::STATUTS,
        ]);
    }

    /** Le partenaire transmet une tâche sur l'un de ses projets. */
    public function storeTask(Request $request)
    {
        $partenaire = $request->user()->partenaire;

        $data = $request->validate([
            'project_id'  => ['required', 'integer'],
            'titre'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'echeance'    => ['nullable', 'date'],
        ]);

        // Le projet doit appartenir au partenaire.
        $project = Project::where('id', $data['project_id'])
            ->where('partenaire_id', $partenaire->id)
            ->firstOrFail();

        $project->tasks()->create([
            'titre'         => $data['titre'],
            'description'   => $data['description'] ?? null,
            'echeance'      => $data['echeance'] ?? null,
            'source'        => 'partenaire',
            'partenaire_id' => $partenaire->id,
            'ordre'         => (int) $project->tasks()->max('ordre') + 1,
        ]);

        return back()->with('success', 'Tâche transmise. Merci !');
    }
}
