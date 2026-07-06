<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\ReponseTachePartenaire;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
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

    /** Le partenaire accepte ou refuse (avec motif) une tâche qui lui est assignée. */
    public function respondTask(Request $request, ProjectTask $task)
    {
        $partenaire = $request->user()->partenaire;
        abort_unless($task->partenaire_id === $partenaire?->id && $task->estAProposer(), 403);

        $data = $request->validate([
            'action'      => ['required', 'in:accepter,refuser'],
            'motif_refus' => ['required_if:action,refuser', 'nullable', 'string', 'max:2000'],
        ]);

        if ($data['action'] === 'accepter') {
            $task->update(['statut' => 'a_faire', 'motif_refus' => null]);
            $this->notifierAdmins(new ReponseTachePartenaire($task, 'acceptee'));

            return back()->with('success', 'Tâche acceptée — merci !');
        }

        $task->update(['statut' => 'refusee', 'motif_refus' => $data['motif_refus']]);
        $this->notifierAdmins(new ReponseTachePartenaire($task, 'refusee'));

        return back()->with('success', 'Tâche refusée. Le donneur d’ordre est prévenu.');
    }

    /** Le partenaire fait progresser une tâche acceptée (à faire -> en cours -> fait). */
    public function updateTask(Request $request, ProjectTask $task)
    {
        $partenaire = $request->user()->partenaire;
        abort_unless($task->partenaire_id === $partenaire?->id && $task->source === 'assignee', 403);
        // La tâche doit d'abord avoir été acceptée (pas encore proposée/refusée).
        abort_if(in_array($task->statut, ['proposee', 'refusee'], true), 403);

        $data = $request->validate([
            'statut' => ['required', 'in:'.implode(',', ProjectTask::STATUTS_PARTENAIRE)],
        ]);

        $task->update(['statut' => $data['statut']]);

        if ($data['statut'] === 'fait') {
            $this->notifierAdmins(new ReponseTachePartenaire($task, 'fait'));
        }

        return back()->with('success', 'Statut mis à jour.');
    }

    /** Notifie tous les administrateurs (comptes non-partenaires). */
    private function notifierAdmins(Notification $notification): void
    {
        User::where('role', '!=', 'partenaire')->get()->each->notify($notification);
    }
}
