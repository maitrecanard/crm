<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BugStatusMail;
use App\Models\Bug;
use App\Models\Prospect;
use App\Services\SupportTicketImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * API d'assistance consommée par le SITE du client (jamais par le CRM).
 * Cloisonnée par le token client (middleware ResolveSupportClient) : un client
 * ne voit et ne crée que ses propres tickets.
 */
class SupportApiController extends Controller
{
    public function __construct(private SupportTicketImages $images) {}

    private function client(Request $request): Prospect
    {
        return $request->attributes->get('support_client');
    }

    /** Liste des motifs proposés au client. */
    public function motifs()
    {
        return response()->json([
            'motifs' => collect(Bug::MOTIFS)->map(fn ($m, $cle) => [
                'cle'   => $cle,
                'label' => $m['label'],
            ])->values(),
        ]);
    }

    /** Tickets du client (cloisonnés par token). */
    public function index(Request $request)
    {
        $tickets = $this->client($request)->supportTickets()
            ->with('images:id,bug_id')
            ->latest()
            ->take(100)
            ->get()
            ->map(fn (Bug $b) => $this->present($b));

        return response()->json(['tickets' => $tickets]);
    }

    /** Statut d'un ticket précis (réf.), 404 si ce n'est pas celui du client. */
    public function show(Request $request, string $reference)
    {
        $bug = $this->client($request)->supportTickets()
            ->where('reference', $reference)
            ->with(['images:id,bug_id', 'messages' => fn ($q) => $q->where('interne', false)])
            ->firstOrFail();

        return response()->json([
            'ticket'   => $this->present($bug),
            'messages' => $bug->messages->map(fn ($m) => [
                'corps' => $m->corps,
                'date'  => $m->created_at->toIso8601String(),
            ]),
        ]);
    }

    /** Le client déclare un incident depuis son site. */
    public function store(Request $request)
    {
        $max = (int) config('crm.support_api.max_images', 4);

        $data = $request->validate([
            'motif'       => ['required', 'in:'.implode(',', array_keys(Bug::MOTIFS))],
            'titre'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'images'      => ['nullable', 'array', "max:{$max}"],
        ]);

        $client  = $this->client($request);
        $project = $request->attributes->get('support_project');   // résolu par le middleware (site = projet)

        $bug = DB::transaction(function () use ($project, $data) {
            return $project->bugs()->create([
                'type'        => Bug::typePourMotif($data['motif']),
                'source'      => 'client_site',
                'motif'       => $data['motif'],
                'titre'       => $data['titre'],
                'description' => $data['description'] ?? null,
                'gravite'     => 'majeur',
                'statut'      => 'nouveau',
            ]);
        });

        // Images : en cas d'échec, on nettoie le ticket pour ne rien laisser d'orphelin.
        try {
            $this->images->store($bug, $request->input('images', []));
        } catch (\Throwable $e) {
            Storage::disk('local')->deleteDirectory("support/{$bug->id}");
            $bug->delete();
            throw $e;
        }

        $this->envoyerAccuse($bug, $client);

        return response()->json([
            'message' => 'Demande d’assistance enregistrée.',
            'ticket'  => $this->present($bug->fresh('images')),
        ], 201);
    }

    /** Représentation publique d'un ticket (rien d'interne). */
    private function present(Bug $bug): array
    {
        return [
            'reference'    => $bug->reference,
            'titre'        => $bug->titre,
            'motif'        => $bug->motif,
            'motif_label'  => $bug->motifLabel(),
            'statut'       => $bug->statut,
            'statut_label' => $bug->statutLabel(),
            'gravite'      => $bug->gravite,
            'images'       => $bug->relationLoaded('images') ? $bug->images->count() : null,
            'cree_le'      => $bug->created_at?->toIso8601String(),
            'maj_le'       => $bug->updated_at?->toIso8601String(),
        ];
    }

    /** Accusé de réception au client (réutilise l'e-mail de statut existant). */
    private function envoyerAccuse(Bug $bug, Prospect $client): void
    {
        if (! $client->email) {
            return;
        }

        try {
            Mail::to($client->email)->send(new BugStatusMail($bug));
            $bug->forceFill(['notifie_le' => now()])->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
