<?php

namespace App\Http\Controllers;

use App\Models\Partenaire;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Prospect;
use App\Services\ClientPromotion;
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

    public function create(Request $request)
    {
        return Inertia::render('Projects/Create', [
            'clients'     => Prospect::where('est_client', true)
                ->orderBy('entreprise')->get(['id', 'entreprise']),
            'partenaires' => Partenaire::orderBy('nom')->get(['id', 'nom']),
            'statuts'     => Project::STATUTS,
            'preselect'   => (int) $request->input('client') ?: null,
        ]);
    }

    /** Création manuelle d'un projet pour un client. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'interne'         => ['boolean'],
            'prospect_id'     => ['nullable', 'exists:prospects,id'],
            'partenaire_id'   => ['nullable', 'exists:partenaires,id'],
            'titre'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'url_prod'        => ['nullable', 'string', 'max:255'],
            'url_preprod'     => ['nullable', 'string', 'max:255'],
            'repo_git'        => ['nullable', 'string', 'max:255'],
            'hebergeur'       => ['nullable', 'string', 'max:255'],
            'statut'          => ['required', 'in:'.implode(',', array_keys(Project::STATUTS))],
            'budget'          => ['nullable', 'integer', 'min:0'],
            'date_debut'      => ['nullable', 'date'],
            'date_fin_prevue' => ['nullable', 'date'],
        ]);

        $interne = (bool) ($data['interne'] ?? false);

        // Un projet client exige un prospect ; un projet interne n'en a pas.
        if (! $interne && empty($data['prospect_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'prospect_id' => 'Choisissez un client, ou cochez « Projet interne ».',
            ]);
        }

        // Le prospect rattaché devient client s'il ne l'est pas déjà.
        if (! $interne) {
            $client = Prospect::findOrFail($data['prospect_id']);
            if (! $client->est_client) {
                $client->forceFill([
                    'est_client'    => true,
                    'client_depuis' => now()->toDateString(),
                ])->saveQuietly();
            }
        }

        $data['interne']     = $interne;
        $data['prospect_id'] = $interne ? null : $data['prospect_id'];
        $data['date_debut']  = $data['date_debut'] ?? now()->toDateString();

        // Le partenaire éventuel est géré via le pivot (plusieurs possibles).
        $partenaireId = $data['partenaire_id'] ?? null;
        unset($data['partenaire_id']);

        $project = Project::create($data);

        if ($partenaireId) {
            $project->partenaires()->attach($partenaireId);
        }

        foreach (ClientPromotion::standardPhases() as $i => $titre) {
            $project->tasks()->create(['titre' => $titre, 'ordre' => $i]);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Projet créé.');
    }

    public function show(Project $project)
    {
        $project->load([
            'prospect:id,entreprise,localite,telephone,email',
            'tender:id,idweb,objet',
            'partenaires:id,nom',
            'tasks.partenaire:id,nom',
            'bugs.messages',
            'bugs.images:id,bug_id,nom',
        ]);

        return Inertia::render('Projects/Show', [
            'project'      => $project,
            'statuts'      => Project::STATUTS,
            'statutsTache' => ProjectTask::STATUTS,
            'statutsBug'   => \App\Models\Bug::STATUTS,
            'gravites'     => \App\Models\Bug::GRAVITES,
            'typesBug'     => \App\Models\Bug::TYPES,
            'recurrences'  => \App\Models\Bug::RECURRENCES,
            // Partenaires assignables.
            'partenaires'  => Partenaire::orderBy('nom')->get(['id', 'nom']),
            // Clients disponibles pour corriger le rattachement du projet.
            'clients'      => Prospect::where('est_client', true)->orderBy('entreprise')->get(['id', 'entreprise']),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'titre'           => ['required', 'string', 'max:255'],
            'partenaire_id'   => ['nullable', 'exists:partenaires,id'],
            'description'     => ['nullable', 'string'],
            'url_prod'        => ['nullable', 'string', 'max:255'],
            'url_preprod'     => ['nullable', 'string', 'max:255'],
            'repo_git'        => ['nullable', 'string', 'max:255'],
            'hebergeur'       => ['nullable', 'string', 'max:255'],
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

    /** Corrige le rattachement du projet : autre client, ou passage en interne. */
    public function updateClient(Request $request, Project $project)
    {
        $data = $request->validate([
            'interne'     => ['boolean'],
            'prospect_id' => ['nullable', 'exists:prospects,id'],
        ]);

        $interne = (bool) ($data['interne'] ?? false);

        // Passage en projet interne : plus aucun client rattaché.
        if ($interne) {
            $project->update(['interne' => true, 'prospect_id' => null]);

            return back()->with('success', 'Projet passé en interne.');
        }

        if (empty($data['prospect_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'prospect_id' => 'Choisissez un client, ou cochez « Projet interne ».',
            ]);
        }

        $client = Prospect::findOrFail($data['prospect_id']);
        if (! $client->est_client) {
            $client->forceFill([
                'est_client'    => true,
                'client_depuis' => now()->toDateString(),
            ])->saveQuietly();
        }

        $project->update(['interne' => false, 'prospect_id' => $client->id]);

        return back()->with('success', 'Client du projet mis à jour.');
    }

    /** Lie un partenaire au projet. */
    public function attachPartenaire(Request $request, Project $project)
    {
        $data = $request->validate([
            'partenaire_id' => ['required', 'exists:partenaires,id'],
        ]);

        $project->partenaires()->syncWithoutDetaching([$data['partenaire_id']]);

        return back()->with('success', 'Partenaire lié au projet.');
    }

    /** Retire un partenaire du projet. */
    public function detachPartenaire(Project $project, Partenaire $partenaire)
    {
        $project->partenaires()->detach($partenaire->id);

        return back()->with('success', 'Partenaire retiré du projet.');
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

        $ancienStatut = $task->statut;
        $task->update($data);

        $this->notifierPartenaire($task, $ancienStatut);

        return back(fallback: route('projects.show', $task->project_id));
    }

    /**
     * Prévient le partenaire émetteur quand sa tâche passe en « en cours » (prise
     * en charge) ou « fait » (terminée). Uniquement sur transition réelle de statut.
     */
    private function notifierPartenaire(ProjectTask $task, string $ancienStatut): void
    {
        if ($task->source !== 'partenaire'
            || $task->statut === $ancienStatut
            || ! in_array($task->statut, ['en_cours', 'fait'], true)) {
            return;
        }

        $task->loadMissing('partenaire.user', 'project:id,titre');
        $task->partenaire?->user?->notify(
            new \App\Notifications\TacheStatutPartenaire($task, $task->statut)
        );
    }

    public function destroyTask(ProjectTask $task)
    {
        $project = $task->project_id;
        $task->delete();

        return back(fallback: route('projects.show', $project));
    }

    /** J'assigne une tâche à un partenaire : il devra l'accepter ou la refuser. */
    public function assignTask(Request $request, Project $project)
    {
        $data = $request->validate([
            'partenaire_id' => ['required', 'exists:partenaires,id'],
            'titre'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'echeance'      => ['nullable', 'date'],
        ]);

        $task = $project->tasks()->create([
            'titre'         => $data['titre'],
            'description'   => $data['description'] ?? null,
            'echeance'      => $data['echeance'] ?? null,
            'statut'        => 'proposee',
            'source'        => 'assignee',
            'partenaire_id' => $data['partenaire_id'],
            'ordre'         => (int) $project->tasks()->max('ordre') + 1,
        ]);

        $this->notifierAssignation($task);

        return back(fallback: route('projects.show', $project))
            ->with('success', 'Tâche assignée — le partenaire doit l’accepter.');
    }

    /** Réassigner une tâche (typiquement refusée) à un autre partenaire. */
    public function reassignTask(Request $request, ProjectTask $task)
    {
        $data = $request->validate([
            'partenaire_id' => ['required', 'exists:partenaires,id'],
        ]);

        $task->update([
            'partenaire_id' => $data['partenaire_id'],
            'statut'        => 'proposee',
            'source'        => 'assignee',
            'motif_refus'   => null,
        ]);

        $this->notifierAssignation($task);

        return back(fallback: route('projects.show', $task->project_id))
            ->with('success', 'Tâche réassignée — le nouveau partenaire doit l’accepter.');
    }

    private function notifierAssignation(ProjectTask $task): void
    {
        $task->loadMissing('partenaire.user', 'project:id,titre');
        $task->partenaire?->user?->notify(new \App\Notifications\TacheAssigneePartenaire($task));
    }
}
