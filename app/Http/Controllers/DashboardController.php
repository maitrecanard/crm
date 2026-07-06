<?php

namespace App\Http\Controllers;

use App\Models\Bug;
use App\Models\ProjectTask;
use App\Models\Prospect;
use App\Models\Tender;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $prospectStats = Prospect::select('statut', DB::raw('count(*) as n'))
            ->groupBy('statut')->pluck('n', 'statut');
        $totalP = Prospect::count();

        $tenderStats = Tender::select('statut', DB::raw('count(*) as n'))
            ->groupBy('statut')->pluck('n', 'statut');

        $aoOuverts = ['a_etudier', 'go', 'dossier', 'depose'];

        // --- Tickets en cours : incidents d'assistance non livrés/clôturés ---
        $ticketsOuverts = ['nouveau', 'en_cours', 'en_test'];
        $graviteRang = ['bloquant' => 0, 'majeur' => 1, 'mineur' => 2];

        $ticketsEnCours = Bug::whereIn('statut', $ticketsOuverts)
            ->with(['project:id,prospect_id,titre', 'project.prospect:id,entreprise'])
            ->get(['id', 'project_id', 'reference', 'titre', 'type', 'statut', 'gravite', 'prochaine_echeance', 'created_at'])
            // Tri par gravité (bloquant d'abord) puis par ancienneté.
            ->sortBy(fn ($b) => [$graviteRang[$b->gravite] ?? 9, $b->created_at?->timestamp ?? 0])
            ->values()
            ->map(fn ($b) => [
                'id'         => $b->id,
                'reference'  => $b->reference,
                'titre'      => $b->titre,
                'type'       => $b->type,
                'statut'     => $b->statut,
                'gravite'    => $b->gravite,
                'client'     => $b->project?->prospect?->entreprise,
                'project_id' => $b->project_id,
                'url'        => $b->project_id ? route('projects.show', $b->project_id) : null,
            ]);

        // --- Rappels : agrégat trié par échéance (maintenance + partenaires + relances) ---
        $horizon = now()->addDays(14)->toDateString();

        $rappelsMaintenance = Bug::whereNotNull('prochaine_echeance')
            ->where('statut', '<>', 'ferme')
            ->whereDate('prochaine_echeance', '<=', $horizon)
            ->with(['project:id,prospect_id', 'project.prospect:id,entreprise'])
            ->get(['id', 'project_id', 'titre', 'prochaine_echeance'])
            ->map(fn ($b) => [
                'type'  => 'maintenance',
                'label' => $b->titre,
                'meta'  => $b->project?->prospect?->entreprise,
                'date'  => $b->prochaine_echeance?->toDateString(),
                'url'   => $b->project_id ? route('projects.show', $b->project_id) : null,
            ]);

        $rappelsPartenaire = ProjectTask::enAttentePartenaire()
            ->whereNotNull('echeance')
            ->whereDate('echeance', '<=', $horizon)
            ->with(['partenaire:id,nom', 'project:id'])
            ->get(['id', 'project_id', 'partenaire_id', 'titre', 'echeance'])
            ->map(fn ($t) => [
                'type'  => 'partenaire',
                'label' => $t->titre,
                'meta'  => $t->partenaire?->nom,
                'date'  => $t->echeance?->toDateString(),
                'url'   => $t->project_id ? route('projects.show', $t->project_id) : null,
            ]);

        $rappelsRelance = Prospect::whereNotNull('prochaine_relance')
            ->whereNotIn('statut', ['gagne', 'perdu'])
            ->whereDate('prochaine_relance', '<=', $horizon)
            ->get(['id', 'entreprise', 'localite', 'prochaine_relance'])
            ->map(fn ($p) => [
                'type'  => 'prospect',
                'label' => $p->entreprise,
                'meta'  => $p->localite,
                'date'  => $p->prochaine_relance?->toDateString(),
                'url'   => route('prospects.show', $p->id),
            ]);

        $rappels = $rappelsMaintenance
            ->concat($rappelsPartenaire)
            ->concat($rappelsRelance)
            ->sortBy('date')
            ->values()
            ->take(15);

        return Inertia::render('Dashboard', [
            'statutsProspect' => Prospect::STATUTS,
            'statutsTender'   => Tender::STATUTS,
            'prospectStats'   => $prospectStats,
            'tenderStats'     => $tenderStats,
            'kpis' => [
                'total'       => $totalP,
                'a_contacter' => $prospectStats['a_contacter'] ?? 0,
                'en_cours'    => $totalP - ($prospectStats['a_contacter'] ?? 0)
                                  - ($prospectStats['perdu'] ?? 0) - ($prospectStats['gagne'] ?? 0),
                'rdv'         => $prospectStats['rdv'] ?? 0,
                'gagne'       => $prospectStats['gagne'] ?? 0,
                'ao_ouverts'  => Tender::whereIn('statut', $aoOuverts)->count(),
            ],
            'aContacterEmail' => Prospect::where('statut', 'a_contacter')
                ->whereNotNull('email')->where('email', '<>', '')
                ->orderBy('entreprise')
                ->limit(20)
                ->get(['id', 'entreprise', 'localite', 'secteur', 'email']),
            'avecEmailTotal' => Prospect::where('statut', 'a_contacter')
                ->whereNotNull('email')->where('email', '<>', '')->count(),
            'ticketsEnCours' => $ticketsEnCours,
            'ticketsCount'   => $ticketsEnCours->count(),
            'rappels'        => $rappels,
            'aoUrgents' => Tender::whereIn('statut', $aoOuverts)
                ->whereNotNull('date_limite')
                ->whereDate('date_limite', '>=', now())
                ->orderBy('date_limite')
                ->limit(8)
                ->get(),
            // Factures mensuelles en retard : mois sans référence passé l'échéance.
            'facturesEnRetard' => Prospect::alertesFacturation(),
        ]);
    }
}
