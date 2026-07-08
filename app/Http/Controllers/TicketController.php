<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifieClientTicket;
use App\Models\Bug;
use App\Models\Prospect;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Gestion des tickets (incidents / maintenance / évolutions) côté admin :
 * liste globale, création, et page de consultation avec historique complet.
 * Les tickets peuvent être ouverts par le client (API d'assistance) ou par l'admin.
 */
class TicketController extends Controller
{
    use NotifieClientTicket;

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'statut']);

        $tickets = Bug::query()
            ->with(['project:id,prospect_id,titre', 'project.prospect:id,entreprise'])
            // Par défaut on masque les tickets clôturés ; ils restent accessibles via le filtre « Clôturé ».
            ->when($filters['statut'] ?? null,
                fn ($q, $v) => $q->where('statut', $v),
                fn ($q) => $q->where('statut', '<>', 'ferme'))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(function ($qq) use ($v) {
                $qq->where('titre', 'like', "%{$v}%")
                    ->orWhere('reference', 'like', "%{$v}%")
                    ->orWhereHas('project.prospect', fn ($p) => $p->where('entreprise', 'like', "%{$v}%"));
            }))
            // Tickets ouverts d'abord, puis les plus récents.
            ->orderByRaw("CASE WHEN statut IN ('livre', 'ferme') THEN 1 ELSE 0 END")
            ->orderByDesc('created_at')
            ->limit(200)
            ->get(['id', 'project_id', 'reference', 'titre', 'type', 'statut', 'gravite', 'source', 'interne', 'created_at'])
            ->map(fn ($b) => [
                'id'         => $b->id,
                'reference'  => $b->reference,
                'titre'      => $b->titre,
                'type'       => $b->type,
                'statut'     => $b->statut,
                'gravite'    => $b->gravite,
                'source'     => $b->source,
                'client'     => $b->project?->prospect?->entreprise,
                'interne'    => (bool) $b->interne,
                'created_at' => $b->created_at,
                'url'        => route('tickets.show', $b->id),
            ]);

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'statuts' => Bug::STATUTS,
            'types'   => Bug::TYPES,
            'stats'   => Bug::selectRaw('statut, count(*) n')->groupBy('statut')->pluck('n', 'statut'),
        ]);
    }

    public function create()
    {
        $clients = Prospect::where('est_client', true)
            ->with('projects:id,prospect_id,titre')
            ->orderBy('entreprise')
            ->get(['id', 'entreprise'])
            ->map(fn ($c) => [
                'id'         => $c->id,
                'entreprise' => $c->entreprise,
                'projects'   => $c->projects->map(fn ($p) => ['id' => $p->id, 'titre' => $p->titre])->values(),
            ]);

        return Inertia::render('Tickets/Create', [
            'clients'          => $clients,
            'internalProjects' => \App\Models\Project::where('interne', true)
                ->orderBy('titre')->get(['id', 'titre']),
            'types'            => Bug::TYPES,
            'gravites'         => Bug::GRAVITES,
            'recurrences'      => Bug::RECURRENCES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'interne'            => ['boolean'],
            'project_id'         => ['nullable', 'integer', 'exists:projects,id'],
            'type'               => ['required', 'in:'.implode(',', array_keys(Bug::TYPES))],
            'titre'              => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'gravite'            => ['required', 'in:'.implode(',', array_keys(Bug::GRAVITES))],
            'recurrence'         => ['nullable', 'in:'.implode(',', array_keys(Bug::RECURRENCES))],
            'prochaine_echeance' => ['nullable', 'date'],
            'issue_git'          => ['nullable', 'string', 'max:255'],
        ]);

        $interne = (bool) ($data['interne'] ?? false);

        // Un ticket client (non interne) doit être rattaché à un projet.
        // Un ticket interne peut en avoir un (optionnel) ou non.
        if (! $interne && empty($data['project_id'])) {
            throw ValidationException::withMessages([
                'project_id' => 'Choisissez un client/projet, ou cochez « Ticket interne ».',
            ]);
        }

        $fields = collect($data)->except(['interne', 'project_id'])->all()
            + ['statut' => 'nouveau', 'source' => 'interne', 'interne' => $interne, 'project_id' => $data['project_id'] ?? null];

        $bug = Bug::create($fields);

        // Ticket interne : aucune notification, même s'il est rattaché à un projet/client.
        if ($interne) {
            return redirect()->route('tickets.show', $bug)->with('success', 'Ticket interne créé.');
        }

        $res = $this->notifierStatut($bug);

        return redirect()->route('tickets.show', $bug)->with(
            $res['sent'] ? 'success' : 'error',
            $res['sent']
                ? 'Ticket créé — accusé de réception envoyé au client.'
                : 'Ticket créé, mais e-mail NON envoyé : '.$res['reason']
        );
    }

    public function show(Bug $bug)
    {
        $bug->load([
            'project:id,prospect_id,titre',
            'project.prospect:id,entreprise,email,telephone',
            'project.prospect.contacts',
            'messages',
            'images:id,bug_id,nom',
            'events',
        ]);

        // Timeline chronologique : événements (création + statuts) et messages fusionnés.
        $historique = collect()
            ->concat($bug->events->map(fn ($e) => [
                'kind'    => $e->type,          // creation | statut
                'date'    => $e->created_at,
                'ancien'  => $e->ancien_statut,
                'nouveau' => $e->nouveau_statut,
                'auteur'  => $e->auteur,
            ]))
            ->concat($bug->messages->map(fn ($m) => [
                'kind'       => 'message',
                'date'       => $m->created_at,
                'corps'      => $m->corps,
                'interne'    => $m->interne,
                'notifie_le' => $m->notifie_le,
            ]))
            ->sortBy('date')
            ->values();

        return Inertia::render('Tickets/Show', [
            'ticket' => [
                'id'                 => $bug->id,
                'reference'          => $bug->reference,
                'titre'              => $bug->titre,
                'description'        => $bug->description,
                'type'               => $bug->type,
                'statut'             => $bug->statut,
                'statut_label'       => $bug->statutLabel(),
                'interne'            => $bug->estInterne(),
                'gravite'            => $bug->gravite,
                'recurrence'         => $bug->recurrence,
                'prochaine_echeance' => $bug->prochaine_echeance?->toDateString(),
                'issue_git'          => $bug->issue_git,
                'motif'              => $bug->motifLabel(),
                'source'             => $bug->source,
                'created_at'         => $bug->created_at,
                'resolved_at'        => $bug->resolved_at,
                'project'            => $bug->project ? ['id' => $bug->project->id, 'titre' => $bug->project->titre] : null,
                'client'             => $bug->project?->prospect ? [
                    'id'         => $bug->project->prospect->id,
                    'entreprise' => $bug->project->prospect->entreprise,
                    'email'      => $bug->project->prospect->email,
                    'telephone'  => $bug->project->prospect->telephone,
                ] : null,
                'images' => $bug->images->map(fn ($i) => [
                    'id'  => $i->id,
                    'nom' => $i->nom,
                    'url' => route('bugs.images.show', $i->id),
                ])->values(),
            ],
            'historique'   => $historique,
            'destinataires' => $bug->project?->prospect?->destinatairesTickets() ?? [],
            'statuts'     => Bug::STATUTS,
            'types'       => Bug::TYPES,
            'gravites'    => Bug::GRAVITES,
            'recurrences' => Bug::RECURRENCES,
        ]);
    }
}
